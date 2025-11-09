<?php

namespace App\Jobs;

use App\Services\UserService;
use App\Services\VenueService;
use App\Services\DepartmentService;
use App\Adapters\VenueCsvParser;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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
        $cacheKey = 'venues_import:' . $this->file_name;
        try {
            Cache::put($cacheKey, 'scanning', 600);

            $filePath = Storage::disk('uploads_temp')->path($this->file_name);

            $infected = false;
            $scanUnavailable = false;

            try {
                $scan = new Process(['clamdscan', $filePath]);
                $scan->run();
                $output = $scan->getOutput() . "\n" . $scan->getErrorOutput();

                if (Str::contains($output, 'FOUND')) {
                    $infected = true;
                } elseif (!Str::contains($output, 'OK')) {
                    // clamd not available or misconfigured on this machine
                    $scanUnavailable = true;
                    Log::warning('clamdscan unavailable or inconclusive; proceeding without AV scan', [
                        'output' => $output,
                        'exit_code' => $scan->getExitCode(),
                    ]);
                }
            } catch (\Throwable $e) {
                $scanUnavailable = true;
                Log::warning('clamdscan failed; proceeding without AV scan', ['error' => $e->getMessage()]);
            }

            if ($infected) {
                Cache::put($cacheKey, 'infected', 600);
                Storage::disk('uploads_temp')->delete($this->file_name);
                return;
            }

            if ($scanUnavailable) {
                Cache::put($cacheKey, 'scan-unavailable', 600);
            }

            Cache::put($cacheKey, 'parsing', 600);

            // Parse CSV and normalize to the VenueService expected schema
            $parsed = new VenueCsvParser()->parse($filePath);
            $normalized = $this->normalizeCsvRows($parsed);

            // Build name=>code map from parsed rows (department_name_raw => department_code_raw)
            $deptMap = collect($parsed)
                ->mapWithKeys(function ($r) {
                    $name = trim((string)($r['department_name_raw'] ?? ''));
                    $code = trim((string)($r['department_code_raw'] ?? ''));
                    return $name !== '' ? [$name => $code] : [];
                })
                ->filter();

            // Ensure departments exist locally (auto-create if missing during import)
            $deptSvc = app(DepartmentService::class);
            $uniqueDepts = collect($deptMap)
                ->map(fn($code, $name) => ['name' => $name, 'code' => $code])
                ->filter(fn($d) => ($d['name'] ?? '') !== '' && ($d['code'] ?? '') !== '')
                ->unique(fn($d) => $d['name'] . '|' . $d['code']);

            foreach ($uniqueDepts as $dept) {
                try {
                    $byName = $deptSvc->findByName($dept['name']);
                    $byCode = $deptSvc->findByCode($dept['code']);
                    if (!$byName && !$byCode) {
                        $deptSvc->updateOrCreateDepartment([
                            ['name' => $dept['name'], 'code' => $dept['code']],
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Unable to ensure/update department during CSV import', [
                        'department' => $dept['name'],
                        'code' => $dept['code'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Cache::put($cacheKey, 'importing', 600);

            // Strictly resolve an admin via provided ID; fail if not found
            if ($this->admin_id > 0) {
                try {
                    $adminUser = app(UserService::class)->findUserById($this->admin_id);
                } catch (\Throwable $e) {
                    Cache::put($cacheKey, 'failed', 600);
                    Cache::put($cacheKey . ':error', 'Admin user required for import', 600);
                    Storage::disk('uploads_temp')->delete($this->file_name);
                    return;
                }
            } else {
                Cache::put($cacheKey, 'failed', 600);
                Cache::put($cacheKey . ':error', 'Admin user required for import', 600);
                Storage::disk('uploads_temp')->delete($this->file_name);
                return;
            }

            // Import via service
            $result = app(VenueService::class)->updateOrCreateFromImportData($normalized, $adminUser);

            // Cleanup and done
            Storage::disk('uploads_temp')->delete($this->file_name);
            $processed = is_array($result) ? count($result) : (is_object($result) && method_exists($result, 'count') ? $result->count() : count($normalized));
            Cache::put($cacheKey . ':count', (int)$processed, 600);
            Cache::put($cacheKey, 'done', 600);
        } catch (\Throwable $e) {
            Log::error('CSV import job failed', [
                'file' => $this->file_name,
                'error' => $e->getMessage(),
            ]);
            Cache::put($cacheKey, 'failed', 600);
            Cache::put($cacheKey . ':error', (string)$e->getMessage(), 600);
            // Try to cleanup uploaded file to avoid stale files
            try {
                Storage::disk('uploads_temp')->delete($this->file_name);
            } catch (\Throwable $e2) {
            }
        }
    }

    /**
     * Normalize adapter rows to the VenueService expected keys and bit order.
     * Adapter keys: v_name, v_code, department_name_raw, v_features_code (multimedia,computers,teaching,online), v_capacity, v_test_capacity
     * Service expects: name, code, department, features (online,multimedia,teaching,computers), capacity, test_capacity
     *
     * @param array $rows
     * @return array
     */
    protected function normalizeCsvRows(array $rows): array
    {
        $out = [];
        foreach ($rows as $r) {
            $name = (string)($r['v_name'] ?? '');
            $code = (string)($r['v_code'] ?? '');
            if ($name === '' || $code === '') continue;

            $bits = (string)($r['v_features_code'] ?? '0000'); // [multimedia,computers,teaching,online]
            $bits = str_pad($bits, 4, '0');
            // Reorder to [online, multimedia, teaching, computers]
            $ordered = $bits[3] . $bits[0] . $bits[2] . $bits[1];

            $out[] = [
                'name' => $name,
                'code' => $code,
                // Only pass department as code for lookup
                'department' => (string)($r['department_code_raw'] ?? ''),
                'features' => $ordered,
                'capacity' => (int)($r['v_capacity'] ?? 0),
                'test_capacity' => (int)($r['v_test_capacity'] ?? ($r['v_capacity'] ?? 0)),
            ];
        }
        return $out;
    }
}
