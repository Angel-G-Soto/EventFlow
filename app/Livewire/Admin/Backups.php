<?php

namespace App\Livewire\Admin;

use App\Services\DocumentService;
use Illuminate\Support\Facades\File;
use Livewire\Component;
use Livewire\Attributes\Layout;

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
    /** @var string|null Error message shown when backups cannot be read. */
    public ?string $errorMessage = null;

    /** @var array<int,mixed> Cached recent backup entries. */
    public array $recentBackups = [];

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
}
