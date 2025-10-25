<?php

namespace App\Jobs;

use App\Models\Document;
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
     * Execute the job.
     */
    public function handle(): void
    {
        foreach ($this->documents as $document) {
            // Validate that the collection only has Document elements.
            if (!$document instanceof Document) throw new InvalidArgumentException();

            // Get path to where the document is located.
            $path = Storage::disk('uploads_temp')->path($document->getNameOfFile());

            // Create scanning process
            $scan = new Process(['clamscan', $path]);

            // Run process
            $scan->run();

            // Examine output and take decision (move to public folder or delete)
            if (Str::contains($scan->getOutput(), 'OK'))
            {
                // Move file
                $contents = Storage::disk('uploads_temp')->get($document->getNameOfFile());
                Storage::disk('documents')->put($document->getNameOfFile(), $contents);
                Storage::disk('uploads_temp')->delete($document->getNameOfFile());
            }
            elseif(Str::contains($scan->getOutput(), 'FOUND'))
            {
                // Delete file
                Storage::disk('uploads_temp')->delete($document->getNameOfFile());
            }
            else throw new ProcessFailedException($scan);
        }
    }
}
