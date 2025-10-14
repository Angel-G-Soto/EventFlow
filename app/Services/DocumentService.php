<?php

namespace App\Services;

use App\Exceptions\FileInfectedException;
use App\Exceptions\StorageException;
use App\Models\Document;
use App\Models\User;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * DocumentService
 *
 * Responsibilities:
 *  - Validate & scan uploads
 *  - Safely store files (tmp -> final)
 *  - Create/Delete document records
 *  - Provide read streams for downloads
 *
 * User-agnostic: receives a concrete User instance from controllers.
 */
class DocumentService
{
    /**
     * Virus scanner adapter contract.
     * Your ClamAV adapter should implement this.
     */
    public function __construct(
        private readonly FileVirusScanner $scanner
    ) {}

    /**
     * Process a single file upload end-to-end:
     *  1) Save to a temp location
     *  2) Virus scan the temp file
     *  3) Move to the final storage disk
     *  4) Create DB record
     *
     * @throws FileInfectedException
     * @throws StorageException
     */
    public function handleUpload(UploadedFile $file, int $uploaderId, int $eventId): Document
    {
        // 1) Save to quarantine (uploads_temp)
        $tmpRelativePath = $file->store('', ['disk' => $this->tempDisk()]);
        if (!$tmpRelativePath) {
            throw new StorageException(
                message: 'Failed to write temporary file.',
                operation: 'write',
                path: $this->tempDisk() . '://',
                retryable: true
            );
        }
        $tmpAbsolutePath = Storage::disk($this->tempDisk())->path($tmpRelativePath);

        try {
            // 2) Virus scan
            try {
                $clean = $this->scanner->scan($tmpAbsolutePath);
            } catch (\Throwable $scanErr) {
                $this->safeTempDelete($tmpRelativePath);
                $engineMsg = method_exists($this->scanner, 'lastReport')
                    ? (string) $this->scanner->lastReport()
                    : $scanErr->getMessage();

                throw new FileInfectedException(
                    message: 'Security scan unavailable.',
                    reason: 'scanner_error',
                    signature: null,
                    engineMessage: $engineMsg ?: null
                );
            }

            if (!$clean) {
                // Optional: get signature/why from adapter
                $signature = method_exists($this->scanner, 'lastReport')
                    ? (string) $this->scanner->lastReport()
                    : null;

                $this->safeTempDelete($tmpRelativePath);

                throw new FileInfectedException(
                    message: 'Upload blocked. File is infected.',
                    reason: 'infected',
                    signature: $signature
                );
            }

            // 3) Move to final disk
            $finalName = $file->hashName();
            $finalPath = "events/{$eventId}/{$finalName}";
            $stream = @fopen($tmpAbsolutePath, 'rb');
            if (!$stream) {
                $this->safeTempDelete($tmpRelativePath);
                throw new StorageException(
                    message: 'Failed to open temp file for streaming.',
                    operation: 'write',
                    path: $tmpAbsolutePath,
                    retryable: true
                );
            }

            $ok = Storage::disk($this->finalDisk())->put($finalPath, $stream);
            @fclose($stream);
            $this->safeTempDelete($tmpRelativePath);


            if (!$ok) {
                throw new StorageException(
                    message: 'Failed to persist file to documents disk.',
                    operation: 'write',
                    path: $this->finalDisk() . '://' . $finalPath,
                    retryable: true
                );
            }

            // 4) Create DB record
            return Document::create([
                'event_id'    => $eventId,
                'd_name'      => $file->getClientOriginalName(),
                'd_file_path' => $finalName,
            ]);
        } catch (\Throwable $e) {
            // Best-effort cleanup if something else blew up mid-flight
            $this->safeTempDelete($tmpRelativePath);
            throw $e;
        }
    }

    /**
     * Permanently delete a document (file first, then DB row).
     *
     * @throws StorageException
     */
    public function deleteDocument(Document $document): bool
    {
        $path = $document->doc_path;

        // 1) Remove physical file
        try {
            // If file does not exist, treat as deleted (idempotent delete)
            if (Storage::disk($this->finalDisk())->exists($path)) {
                $deleted = Storage::disk($this->finalDisk())->delete($path);
                if (!$deleted) {
                    throw new StorageException(
                        message: 'Failed to delete document from storage.',
                        operation: 'delete',
                        path: $this->finalDisk() . '://' . $path,
                        retryable: true
                    );
                }
            }
        } catch (\Throwable $e) {
            throw new StorageException(
                message: 'Storage driver error while deleting file.',
                operation: 'delete',
                path: $this->finalDisk() . '://' . $path,
                retryable: true
            );
        }

        // 2) Remove DB record
        return (bool) $document->delete();
    }

    /**
     * Get a binary stream for the document's file.
     * Controller builds the actual response() around it.
     *
     * @return resource
     * @throws FileNotFoundException
     * @throws StorageException
     */
    public function getDocumentStream(Document $document)
    {
        $path = $document->doc_path;

        if (!Storage::disk($this->finalDisk())->exists($path)) {
            // Let caller decide how to present this (404 vs soft message)
            throw new FileNotFoundException("File not found on disk: {$path}");
        }

        try {
            $stream = Storage::disk($this->finalDisk())->readStream($path);
        } catch (\Throwable $e) {
            throw new StorageException(
                message: 'Failed to open document stream.',
                operation: 'read',
                path: $this->finalDisk() . '://' . $path,
                retryable: true
            );
        }

        if (!\is_resource($stream)) {
            throw new StorageException(
                message: 'Storage driver returned an invalid stream.',
                operation: 'read',
                path: $this->finalDisk() . '://' . $path,
                retryable: true
            );
        }

        return $stream;
    }

    private function tempDisk(): string
    {
        return 'uploads_temp';
    }

    private function finalDisk(): string
    {
        return 'documents';
    }

    private function safeTempDelete(string $tmpRelativePath): void
    {
        try {
            Storage::disk($this->tempDisk())->delete($tmpRelativePath);
        } catch (\Throwable $e) {
            Log::warning('Temp file cleanup failed', [
                'disk' => $this->tempDisk(),
                'path' => $tmpRelativePath,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

/**
 * Minimal scanner contract the service relies on.
 * Your ClamAV adapter should implement this interface.
 */
interface FileVirusScanner
{
    /**
     * Scan a local, readable file path.
     * Return true if clean; false if infected.
     * Throw on scanner/engine unavailability.
     */
    public function scan(string $absolutePath): bool;

    /**
     * Optional: last engine report/signature string.
     */
    public function lastReport(): ?string;
}
