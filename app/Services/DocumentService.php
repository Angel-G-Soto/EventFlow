<?php

namespace App\Services;

use App\Exceptions\StorageException;
use App\Models\Document;
use App\Jobs\ProcessFileUpload;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
     * Process a single file upload end-to-end:
     *  1) Save to a temp location
     *  2) Create DB record
     *  3) Queue virus scan & move to final storage
     *
     * @throws StorageException
     */
    public function handleUpload(UploadedFile $file, int $userId, int $eventId): Document
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

        // 2) Create DB record
        $doc = Document::create([
            'event_id' => $eventId,
            'name' => $tmpRelativePath,
            'file_path' => '',
        ]);

        // 3) Queue virus scan & move to final storage
        ProcessFileUpload::dispatch(Document::whereIn('id', [$doc->id])->get());

        return $doc;
    }

    /**
     * Permanently delete a document (file first, then DB row).
     *
     * @throws StorageException
     */
    public function deleteDocument(Document $document): bool
    {
        $path = $document->file_path;

        // 1) Remove physical file
        try {
            // If file does not exist, treat as deleted (idempotent delete)
            if ($path && Storage::disk($this->finalDisk())->exists($path)) {
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
                path: ($path ? $path : '(null)'),
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
        $path = $document->file_path;

        if (!$path || !Storage::disk($this->finalDisk())->exists($path)) {
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

    /*private function safeTempDelete(string $tmpRelativePath): void
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
    }*/
}
