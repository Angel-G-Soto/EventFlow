<?php

namespace App\Jobs;

use App\Models\Document;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
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
     * Execute the job.
     */
    public function handle(): void
    {
        foreach ($this->documents as $document) {
            // Validate that the collection only has Document elements.
            if (!$document instanceof Document) throw new InvalidArgumentException();

            // Get full path to where the document is located.
            $fullPath = Storage::disk('uploads_temp')->path($document->d_file_path);

            // Create scanning process
            $scan = new Process(['clamdscan file', $fullPath]);

            // Run process
            $scan->run();

            if (! $scan->isSuccessful()) {
                throw new ProcessFailedException($scan);
            }

            // Examine output and take decision (move to public folder or delete)
            if ($scan->getOutput() == '0')
            {
                // Move file
                $contents = Storage::disk('uploads_temp')->get($this->d_file_path);
                Storage::disk('documents')->put($this->d_file_path, $contents);
                Storage::disk('uploads_temp')->delete($this->d_file_path);
            }
            elseif ($scan->getOutput() == '1')
            {
                // Delete file
                Storage::disk('uploads_temp')->delete($document->d_file_path);
            }
            else throw new ProcessFailedException($scan);
        }
    }
}
