<?php

namespace App\Services;

use App\Exceptions\StorageException;
use App\Models\Document;
use App\Jobs\ProcessFileUpload;
use App\Services\AuditService;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

        // Original name
        $originalName = $file->getClientOriginalName();

        // 1) Trim whitespace
        $cleanName = trim($originalName);

        // 2) Remove control characters
        $cleanName = preg_replace('/[\x00-\x1F\x7F]/u', '', $cleanName);

        // 3) Hard limit length (avoid DB truncation)
        $maxLen = 150;
        if (mb_strlen($cleanName) > $maxLen) {
            $ext = pathinfo($cleanName, PATHINFO_EXTENSION);
            $base = mb_substr(pathinfo($cleanName, PATHINFO_FILENAME), 0, $maxLen - 5);
            $cleanName = $ext ? "{$base}.{$ext}" : $base;
        }

        // 2) Create DB record
        $doc = Document::create([
            'event_id' => $eventId,
            'name' => $cleanName,
            'file_path' => $tmpRelativePath,
        ]);

        // 3) Queue virus scan & move to final storage
        ProcessFileUpload::dispatch(Document::whereIn('id', [$doc->id])->get());

        // AUDIT: document uploaded (best-effort)
        try {
            /** @var AuditService $audit */
            $audit = app(AuditService::class);

            $meta = [
                'document_id' => (int) ($doc->id ?? 0),
                'event_id'    => (int) $eventId,
                'name'        => (string) $cleanName,
                'source'      => 'document_upload',
            ];
            $ctx = ['meta' => $meta];
            if (function_exists('request') && request()) {
                $ctx = $audit->buildContextFromRequest(request(), $meta);
            }

            $audit->logAction(
                $userId,
                'document',
                'DOCUMENT_UPLOADED',
                (string) ($doc->id ?? 0),
                $ctx
            );
        } catch (\Throwable) {
            // best-effort
        }

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
        $deleted = (bool) $document->delete();

        // AUDIT: document deleted (best-effort)
        if ($deleted) {
            try {
                /** @var AuditService $audit */
                $audit = app(AuditService::class);

                $userId = auth()->id() ?? 0;
                if ($userId) {
                    $meta = [
                        'document_id' => (int) ($document->id ?? 0),
                        'event_id'    => (int) ($document->event_id ?? 0),
                        'name'        => (string) ($document->name ?? ''),
                        'source'      => 'document_delete',
                    ];
                    $ctx = ['meta' => $meta];
                    if (function_exists('request') && request()) {
                        $ctx = $audit->buildContextFromRequest(request(), $meta);
                    }

                    $audit->logAction(
                        (int) $userId,
                        'document',
                        'DOCUMENT_DELETED',
                        (string) ($document->id ?? 0),
                        $ctx
                    );
                }
            } catch (\Throwable) {
                // best-effort
            }
        }

        return $deleted;
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

    public function showPDF(Document $document): BinaryFileResponse
    {
        //

        // (Optional) enforce policies
        // $this->authorize('view', $document);

        // Use your existing accessor if you prefer:
        // $filePath = $document->getFilePath();


        $filePath = $document->file_path;

        // Make sure the file exists on the "documents" disk
        abort_unless(Storage::disk('documents')->exists($filePath), 404);

        $path = Storage::disk('documents')->path($filePath);

        // Use ?name=... if provided, otherwise use document name or basename
        $downloadName = $document->name ?? 'requestFile'.$document->id;

        return Response::file($path, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $downloadName . '"',
            'Cache-Control'       => 'private, max-age=3600',
        ]);
    }

    private function finalDisk(): string
    {
        return 'documents';
    }

    public function getDocument(int $id): Document
    {
        return Document::findOrFail($id);
    }

    /**
     * Attach documents to the provided event by updating event_id.
     *
     * @param array<int> $documentIds
     */
    public function assignDocumentsToEvent(array $documentIds, int $eventId): void
    {
        if (empty($documentIds)) {
            return;
        }

        Document::whereIn('id', $documentIds)
            ->update(['event_id' => $eventId]);

        // AUDIT: documents assigned to event (best-effort)
        try {
            /** @var AuditService $audit */
            $audit = app(AuditService::class);

            $userId = auth()->id() ?? 0;
            if ($userId) {
                $meta = [
                    'event_id'     => (int) $eventId,
                    'document_ids' => array_values($documentIds),
                    'source'       => 'document_assign',
                ];
                $ctx = ['meta' => $meta];
                if (function_exists('request') && request()) {
                    $ctx = $audit->buildContextFromRequest(request(), $meta);
                }

                // Target is the event whose documents were updated
                $audit->logAction(
                    (int) $userId,
                    'event',
                    'DOCUMENTS_ASSIGNED_TO_EVENT',
                    (string) $eventId,
                    $ctx
                );
            }
        } catch (\Throwable) {
            // best-effort
        }
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
