<?php

namespace App\Jobs;

use App\Services\UserService;
use App\Services\VenueService;
use App\Adapters\VenueCsvParser;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ProcessCsvFileUpload implements ShouldQueue
{
    use Queueable;

    protected string $file_name;
    protected int $admin_id;

    /**
     * Create a new job instance.
     */
    public function __construct(String $file_name, int $admin_id)
    {
        $this->file_name = $file_name;
        $this->admin_id = $admin_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Scan File
        $filePath = Storage::disk('uploads_temp')->path($this->file_name);

        // Create scanning process
        $scan = new Process(['clamdscan', $filePath]);

        // Run process
        $scan->run();

        // Examine output and take decision (move to public folder or delete)
        if (Str::contains($scan->getOutput(), 'OK'))
        {
            // Call CSV parser
            $csv = new VenueCsvParser()->parse($filePath);

            // Call service that reads csv output and stores venues
            app(VenueService::class)->updateOrCreateFromImportData($csv, app(UserService::class)->findUserById($this->admin_id));

            // Delete file
            Storage::disk('uploads_temp')->delete($this->file_name);
        }
        elseif(Str::contains($scan->getOutput(), 'FOUND'))
        {
            // Delete file
            Storage::disk('uploads_temp')->delete($this->file_name);
        }
        else throw new ProcessFailedException($scan);

    }
}
