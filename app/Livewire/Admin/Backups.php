<?php

namespace App\Livewire\Admin;

use App\Services\DocumentService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * Livewire admin component for inspecting application backups.
 *
 * Presents a simple view of recent backup archives and allows admins to refresh
 * the list on demand while keeping filesystem access encapsulated in the
 * DocumentService.
 */
#[Layout('layouts.app')]
class Backups extends Component
{
    use WithFileUploads;
    /** @var string|null Error message shown when backups cannot be read. */
    public ?string $errorMessage = null;

    /** @var array<int,mixed> Cached recent backup entries. */
    public array $recentBackups = [];
    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null Database backup to restore. */
    public $restoreFile;
    /** @var bool Whether a database restore is currently running. */
    public $isRestoring = false;
    /** @var string Status message for database restore operations. */
    public $restoreMessage = '';
    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null Documents archive to restore. */
    public $restoreDocumentsFile;
    /** @var bool Whether a documents restore is currently running. */
    public $isRestoringDocuments = false;
    /** @var string Status message for document restore operations. */
    public $restoreDocumentsMessage = '';


    /**
     * Authorize access and preload the backup list.
     *
     * @return void
     */
    public function mount(): void
    {
        $this->authorize('access-dashboard');
        $this->loadRecentBackups();
    }

    /**
     * Refresh the in-memory list of recent backups.
     *
     * @return void
     */
    public function refreshList(): void
    {
        $this->authorize('access-dashboard');

        $this->loadRecentBackups();
    }

    /**
     * Populate recent backups from the filesystem via the DocumentService.
     *
     * Resets any previous error state and gracefully handles missing backup
     * directories or read failures.
     *
     * @return void
     */
    private function loadRecentBackups(): void
    {
        $this->errorMessage = null;
        $root = $this->backupRoot();

        if (!File::isDirectory($root)) {
            $this->recentBackups = [];
            return;
        }

        try {
            $this->recentBackups = app(DocumentService::class)->getRecentBackups($root);
        } catch (\Throwable $e) {
            $this->recentBackups = [];
            $this->errorMessage = 'Unable to read backups: ' . $e->getMessage();
        }
    }

    /**
     * Resolve the backups root path from configuration or defaults.
     *
     * @return string Absolute filesystem path where backups are stored.
     */
    private function backupRoot(): string
    {
        return env('BACKUP_PATH', storage_path('app/backups'));
    }


    /**
     * Render the backups view for authorized admins.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        $this->authorize('access-dashboard');

        return view('livewire.admin.backups');
    }

    /**
     * Restore the database from an uploaded .sql.gz backup.
     *
     * Validates the uploaded file, streams it to storage, then decompresses and
     * applies the SQL contents.
     *
     * @return void
     */
    public function restoreBackup()
    {
        $this->authorize('access-dashboard');

        // Check if file exists
        if (!$this->restoreFile) {
            $this->restoreMessage = 'Error: Please select a .sql.gz file to restore';
            return;
        }

        // Validate file (5GB = 5,368,709,120 bytes)
        if ($this->restoreFile->getSize() > 5368709120) {
            $this->restoreMessage = 'Error: File size exceeds 5GB limit';
            return;
        }

        if ($this->restoreFile->getClientOriginalExtension() !== 'gz') {
            $this->restoreMessage = 'Error: File must be a .sql.gz file';
            return;
        }

        $this->isRestoring = true;
        $this->restoreMessage = 'Restoring database...';

        try {
            $file = $this->restoreFile->store('temp');
            $filePath = Storage::path($file);

            // Decompress the gz file and restore via mysql
            if (!$this->restoreFromGzFile($filePath)) {
                $this->restoreMessage = 'Error restoring database: Failed to restore from backup file';
            } else {
                Storage::delete($file);
                $this->restoreMessage = '';
                $this->restoreFile = null;
                $this->dispatch('toast', message: 'Database restored successfully!', className: 'text-bg-success');
            }
        } catch (\Exception $e) {
            $this->restoreMessage = 'Error: ' . $e->getMessage();
            \Log::error('Database restore error: ' . $e->getMessage());
        }

        $this->isRestoring = false;
    }

    /**
     * Restore database from a gzip SQL file.
     *
     * @param string $gzFilePath Path to the .sql.gz file
     * @return bool True if successful, false otherwise
     */
    private function restoreFromGzFile(string $gzFilePath): bool
    {
        try {
            // Verify file exists
            if (!file_exists($gzFilePath)) {
                \Log::error('Backup file not found: ' . $gzFilePath);
                return false;
            }

            // Read the gzipped SQL file
            $gzHandle = gzopen($gzFilePath, 'rb');
            if (!$gzHandle) {
                \Log::error('Failed to open gzip file: ' . $gzFilePath);
                return false;
            }

            // Read all content from the gzip file
            $sqlContent = '';
            $chunkSize = 65536; // 64KB chunks
            
            while (!gzeof($gzHandle)) {
                $chunk = gzread($gzHandle, $chunkSize);
                if ($chunk === false) {
                    gzclose($gzHandle);
                    \Log::error('Failed to read from gzip file');
                    return false;
                }
                $sqlContent .= $chunk;
            }
            
            gzclose($gzHandle);

            // Execute the SQL
            if (!empty(trim($sqlContent))) {
                DB::unprepared($sqlContent);
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Error restoring database: ' . $e->getMessage());
            $this->restoreMessage = 'Error: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Execute SQL statements, handling them individually for better error handling.
     *
     * @param string $sql SQL content to execute
     * @return void
     */
    private function executeSqlStatements(string $sql): void
    {
        // Split by semicolons, but be careful about semicolons in strings
        $statements = preg_split('/;(?=(?:[^\']*\'[^\']*\')*[^\']*$)/', $sql);
        
        // preg_split can return false on error, check for it
        if (!is_array($statements)) {
            \Log::warning('Failed to split SQL statements');
            return;
        }
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                try {
                    DB::unprepared($statement . ';');
                } catch (\Exception $e) {
                    // Log but continue - some statements might fail
                    \Log::warning('SQL statement failed during restore: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Restore documents from an uploaded zip file.
     *
     * Validates the archive and extracts it into the documents directory.
     *
     * @return void
     */
    public function restoreDocuments()
    {
        $this->authorize('access-dashboard');
        
        // Check if file exists
        if (!$this->restoreDocumentsFile) {
            $this->restoreDocumentsMessage = 'Error: Please select a ZIP file to restore';
            return;
        }

        // Validate file (5GB = 5,368,709,120 bytes)
        if ($this->restoreDocumentsFile->getSize() > 5368709120) {
            $this->restoreDocumentsMessage = 'Error: File size exceeds 5GB limit';
            return;
        }

        if ($this->restoreDocumentsFile->getClientOriginalExtension() !== 'zip') {
            $this->restoreDocumentsMessage = 'Error: File must be a ZIP file';
            return;
        }

        $this->isRestoringDocuments = true;
        $this->restoreDocumentsMessage = 'Restoring documents...';

        try {
            $file = $this->restoreDocumentsFile->store('temp');
            $filePath = Storage::path($file);

            if (!$this->extractDocumentsZip($filePath)) {
                $this->restoreDocumentsMessage = 'Error restoring documents: Failed to extract backup file';
            } else {
                Storage::delete($file);
                $this->restoreDocumentsMessage = '';
                $this->restoreDocumentsFile = null;
                $this->dispatch('toast', message: 'Documents restored successfully!', className: 'text-bg-success');
            }
        } catch (\Exception $e) {
            $this->restoreDocumentsMessage = 'Error: ' . $e->getMessage();
            \Log::error('Document restore error: ' . $e->getMessage());
        }

        $this->isRestoringDocuments = false;
    }

    /**
     * Extract documents from a zip file into the documents storage directory.
     *
     * @param string $zipFilePath Path to the .zip file
     * @return bool True if successful, false otherwise
     */
    private function extractDocumentsZip(string $zipFilePath): bool
    {
        try {
            $zip = new \ZipArchive();
            if ($zip->open($zipFilePath) !== true) {
                return false;
            }

            $documentsPath = storage_path('app/documents');

            // Create documents directory if it doesn't exist
            if (!File::isDirectory($documentsPath)) {
                File::makeDirectory($documentsPath, 0755, true);
            }

            // Extract all files to the documents directory
            $zip->extractTo($documentsPath);
            $zip->close();

            return true;
        } catch (\Exception $e) {
            $this->restoreDocumentsMessage = 'Error: ' . $e->getMessage();
            return false;
        }
    }

}
