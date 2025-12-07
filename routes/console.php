<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Display a single inspirational quote via the console command.
 *
 * @return void
 */
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Dump the database, archive document storage, and prune stale backups.
 *
 * @return int
 */
Artisan::command('backup:dump {--keep=14}', function () {
    // Prepare timestamped paths and retention window (BACKUP_PATH env overrides default)
    $timestamp = now()->format('Y-m-d_His');
    $backupRoot = env('BACKUP_PATH', storage_path('app/backups'));
    $databaseDump = $backupRoot . "/dbdump_{$timestamp}.sql.gz";
    $documentsArchive = $backupRoot . "/documents_{$timestamp}.zip";
    $keepDays = max((int) $this->option('keep'), 1);

    // Ensure backups directory exists
    if (!File::exists($backupRoot)) {
        File::makeDirectory($backupRoot, 0755, true);
    }

    // Pull connection settings for the active DB connection
    $connection = Config::get('database.default');
    $dbConfig = Config::get("database.connections.{$connection}");

    if (!$dbConfig) {
        $this->error("Database connection [{$connection}] not configured.");
        return 1;
    }

    // Support mysql/mariadb only for this helper
    if (!in_array($dbConfig['driver'], ['mysql', 'mariadb'])) {
        $this->error('backup:dump currently supports mysql/mariadb connections only.');
        return 1;
    }

    // Resolve dump binary (env override, else mariadb-dump/mysqldump)
    $dumpBinary = env('DB_DUMP_BINARY');
    if (!$dumpBinary) {
        $dumpBinary = trim(`command -v mariadb-dump`);
    }
    if (!$dumpBinary) {
        $dumpBinary = trim(`command -v mysqldump`);
    }

    if (!$dumpBinary) {
        $this->error('mysqldump/mariadb-dump not found. Set DB_DUMP_BINARY or install a dump client.');
        return 1;
    }

    $host = $dbConfig['host'] ?? '127.0.0.1';
    $port = $dbConfig['port'] ?? '3306';
    $database = $dbConfig['database'] ?? '';
    $username = $dbConfig['username'] ?? '';
    $password = $dbConfig['password'] ?? '';

    // Logical dump with gzip compression
    $dumpCommand = "{$dumpBinary} --single-transaction --quick --skip-lock-tables --host={$host} --port={$port} --user={$username} {$database}";
    $process = Process::fromShellCommandline($dumpCommand . ' | gzip > ' . escapeshellarg($databaseDump));
    $process->setTimeout(600);
    $process->run(null, array_filter(['MYSQL_PWD' => $password], fn($value) => $value !== null));

    if (!$process->isSuccessful()) {
        $this->error('Database dump failed: ' . $process->getErrorOutput());
        return 1;
    }

    // Compress documents disk contents
    $documentsPath = storage_path('app/documents');

    if (File::isDirectory($documentsPath)) {
        // Zip documents disk contents
        $zip = new ZipArchive();
        if (true !== $zip->open($documentsArchive, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            $this->error('Unable to open documents archive for writing.');
            return 1;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($documentsPath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            /** @var \SplFileInfo $file */
            if ($file->isDir()) {
                continue;
            }

            $filePath = $file->getRealPath();
            $relativePath = ltrim(str_replace($documentsPath, '', $filePath), DIRECTORY_SEPARATOR);
            $zip->addFile($filePath, $relativePath);
        }

        $zip->close();
    } else {
        $this->warn('Documents directory not found; skipping documents archive.');
    }

    // Clean backups older than retention window
    $cutoff = now()->subDays($keepDays)->getTimestamp();

    foreach (File::files($backupRoot) as $file) {
        if ($file->getMTime() < $cutoff) {
            File::delete($file->getRealPath());
        }
    }

    $this->info("Database dump saved to: {$databaseDump}");
    $this->info("Documents archive saved to: {$documentsArchive}");
    $this->info("Old backups older than {$keepDays} day(s) have been cleaned up.");

    return 0;
})->purpose('Dump the database (logical) and archive the documents directory.');
