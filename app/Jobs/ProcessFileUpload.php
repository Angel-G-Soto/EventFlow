<?php

namespace App\Jobs;

use App\Exceptions\FileInfectedException;
use App\Exceptions\StorageException;
use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Config;

class ProcessFileUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param array<int> $documentIds
     */
    public function __construct(
        public array $documentIds
    ) {}

    public function handle(): void
    {

        $tempDisk  = 'uploads_temp';
        $finalDisk = 'documents';
        // Process each document independently
        foreach ($this->documentIds as $id) {
            $doc = Document::query()->find($id);
            if (!$doc) {
                Log::warning('ProcessFileUpload: document missing', ['id' => $id]);
                continue;
            }


            $tempRel = $doc->doc_path; // temporary filename (relative)
            $tempAbs = Storage::disk($tempDisk)->path($tempRel);

            if (!Storage::disk($tempDisk)->exists($tempRel)) {
                Log::warning('ProcessFileUpload: temp file missing', ['id' => $id, 'path' => $tempRel]);
                continue;
            }

            // Scan with clamscan (exit 0 clean, 1 infected, 2 error)
            $process = new Process(['clamscan', '--no-summary', $tempAbs]);
            $process->run();

            $exit = $process->getExitCode();
            $output = trim($process->getOutput() ?: $process->getErrorOutput());

            if ($exit === 0) {
                // CLEAN → move to final storage
                $finalRel = "events/{$doc->event_id}/{$tempRel}";
                $in  = Storage::disk($tempDisk)->readStream($tempRel);
                if ($in === false) {
                    throw new StorageException(
                        message: 'Unable to open temp file for streaming.',
                        operation: 'read',
                        path: $tempAbs,
                        retryable: true
                    );
                }

                $ok = Storage::disk($finalDisk)->writeStream($finalRel, $in);
                if (is_resource($in)) {
                    fclose($in);
                }

                if (!$ok) {
                    throw new StorageException(
                        message: 'Failed to write file to final disk.',
                        operation: 'write',
                        path: $finalDisk . '://' . $finalRel,
                        retryable: true
                    );
                }

                // delete temp
                Storage::disk($tempDisk)->delete($tempRel);

                // update DB to final path
                $doc->update(['doc_path' => $finalRel]);
            } elseif ($exit === 1) {
                // INFECTED → delete temp and (optionally) mark record
                Storage::disk($tempDisk)->delete($tempRel);
                // You could soft-delete the doc or keep a status column; for now just log:
                Log::warning('ProcessFileUpload: infected file removed', [
                    'id' => $id,
                    'path' => $tempRel,
                    'output' => $output,
                ]);
                // Optional: throw if you want the job to fail and be retried
                // throw new FileInfectedException('File infected', reason: 'infected', signature: trim($output));
            } else {
                // SCANNER ERROR → leave temp in place for retry, fail the job
                throw new FileInfectedException(
                    message: 'Scanner error running clamscan.',
                    reason: 'scanner_error',
                    engineMessage: trim($output) ?: $process->getErrorOutput()
                );
            }
        }
    }
}
