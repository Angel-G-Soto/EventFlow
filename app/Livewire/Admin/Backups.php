<?php

namespace App\Livewire\Admin;

use App\Services\DocumentService;
use Illuminate\Support\Facades\File;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Backups extends Component
{
    public ?string $errorMessage = null;
    public array $recentBackups = [];

    public function mount(): void
    {
        $this->authorize('access-dashboard');
        $this->loadRecentBackups();
    }

    public function refreshList(): void
    {
        $this->authorize('access-dashboard');

        $this->loadRecentBackups();
    }

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

    private function backupRoot(): string
    {
        return env('BACKUP_PATH', storage_path('app/backups'));
    }


    public function render()
    {
        $this->authorize('access-dashboard');

        return view('livewire.admin.backups');
    }
}
