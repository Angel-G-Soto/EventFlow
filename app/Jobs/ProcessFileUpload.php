<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ProcessFileUpload implements ShouldQueue
{
    use Queueable;

    protected Collection $documents;

    /**
     * Create a new job instance.
     */
    public function __construct(Collection $documents)
    {
        $this->documents = $documents;
    }

    /**
     * Handles scanning and processing of uploaded documents.
     *
     * This method iterates through each document in the `$documents` collection,
     * validates that it is an instance of the `Document` class, and scans it for viruses
     * using `clamdscan`. Based on the scan result, the document is either moved from
     * the temporary uploads storage to the permanent documents storage or deleted if infected.
     *
     */
    public function handle(): void
    {
        foreach ($this->documents as $document) {
            // Validate that the collection only has Document elements.
            if (!$document instanceof Document) throw new InvalidArgumentException();

            // Get path to where the document is located.

            $tempRelative = basename($document->getFilePath());
            $path = Storage::disk('uploads_temp')->path($tempRelative);

            if (!file_exists(env('CLAMDSCAN_PATH'))) {
                throw new \Exception("clamdscan executable not found at: ".env('CLAMDSCAN_PATH'));
            }

            // Create scanning process
            $scan = new Process([env('CLAMDSCAN_PATH'), '--fdpass', $path]);

            // Run process
            $scan->run();
            // Examine output and take decision (move to documents folder or delete)
            if (Str::contains($scan->getOutput(), 'OK'))
            {
                // Move file
                $contents = Storage::disk('uploads_temp')->get($tempRelative);
                Storage::disk('documents')->put($tempRelative, $contents);
                Storage::disk('uploads_temp')->delete($tempRelative);
//                $document->file_path = 'documents/'.$tempRelative;
                $document->file_path = $tempRelative;
                $document->save();
            }
            elseif(Str::contains($scan->getOutput(), 'FOUND'))
            {
                // Delete file
                Storage::disk('uploads_temp')->delete($tempRelative);
                app(DocumentService::class)->deleteDocument($document);
            }
            else throw new ProcessFailedException($scan);
        }
    }
}
