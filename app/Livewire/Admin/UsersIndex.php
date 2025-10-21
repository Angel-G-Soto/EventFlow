<?php

namespace App\Livewire\Admin;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')] // loads your Bootstrap layout
class UsersIndex extends Component
{
    // Filters & paging
    public string $search = '';
    public string $role   = '';
    public int $page      = 1;
    public int $pageSize  = 25;

    // Keep filters in the URL (optional but handy)
    protected $queryString = [
        'search'   => ['except' => ''],
        'role'     => ['except' => ''],
        'page'     => ['except' => 1],
        'pageSize' => ['except' => 25],
    ];

    // Selection state
    /** @var array<int,bool> userId => true */
    public array $selected = [];

    // Edit modal state
    public ?int $editId = null;
    public string $editName = '';
    public string $editEmail = '';
    public string $editRole = 'Student';

    // ===== Dummy dataset (replace with Eloquent later) =====
    protected function allUsers(): Collection
    {
        return collect([
            ['id' => 1, 'name' => 'Jane Doe',   'email' => 'jane@upr.edu',   'role' => 'Approver'],
            ['id' => 2, 'name' => 'Juan Pérez', 'email' => 'juan@upr.edu',   'role' => 'Student'],
            ['id' => 3, 'name' => 'María Ruiz', 'email' => 'mruiz@upr.edu',  'role' => 'Admin'],
            ['id' => 4, 'name' => 'Leo Ortiz',  'email' => 'leo@upr.edu',    'role' => 'Space Manager'],
            ['id' => 5, 'name' => 'Ana Díaz',   'email' => 'adiaz@upr.edu',  'role' => 'Approver'],
        ]);
    }

    // Badge helpers (Bootstrap contextual classes)
    public function roleClass(string $role): string
    {
        return match ($role) {
            'Admin'         => 'text-bg-danger',
            'Approver'      => 'text-bg-success',
            'Space Manager' => 'text-bg-info',
            'Student'       => 'text-bg-secondary',
            default         => 'text-bg-secondary',
        };
    }

    // Keep page in range if filters change
    public function updatedSearch()
    {
        $this->page = 1;
    }
    public function updatedRole()
    {
        $this->page = 1;
    }
    public function updatedPageSize()
    {
        $this->page = 1;
    }

    // Toggle a single row checkbox
    public function toggleSelect(int $userId, bool $checked): void
    {
        if ($checked) {
            $this->selected[$userId] = true;
        } else {
            unset($this->selected[$userId]);
        }
    }

    // Check/uncheck all rows visible on current page
    public function selectAllOnPage(bool $checked, array $ids): void
    {
        foreach ($ids as $id) {
            if ($checked) $this->selected[$id] = true;
            else unset($this->selected[$id]);
        }
    }

    // Edit flow
    public function openEdit(int $id): void
    {
        $u = $this->filtered()->firstWhere('id', $id);
        if (!$u) return;

        $this->editId     = $u['id'];
        $this->editName   = $u['name'];
        $this->editEmail  = $u['email'];
        $this->editRole   = $u['role'];

        $this->dispatch('bs:open', id: 'editUserModal');
    }

    public function save(): void
    {
        if (!$this->editId) return;

        // NOTE: no persistence in demo — just simulate success
        $this->dispatch('bs:close', id: 'editUserModal');
        $this->dispatch('toast', message: 'Changes saved');
    }

    public function delete(int $id): void
    {
        // NOTE: no persistence in demo
        unset($this->selected[$id]);
        $this->dispatch('toast', message: 'User deleted');
    }

    // Bulk actions (demo only)
    public function bulkActivate(): void
    {
        if (empty($this->selected)) return;
        $this->dispatch('toast', message: 'Selected users activated');
        $this->selected = [];
    }

    public function bulkSuspend(): void
    {
        if (empty($this->selected)) return;
        $this->dispatch('toast', message: 'Selected users suspended');
        $this->selected = [];
    }

    public function bulkDelete(): void
    {
        if (empty($this->selected)) return;
        $this->dispatch('toast', message: 'Selected users deleted');
        $this->selected = [];
    }

    // CSV export used by the header button
    public function exportCsv()
    {
        $rows = $this->filtered();

        $csv = implode(",", ['id', 'name', 'email', 'role']) . "\n";
        foreach ($rows as $u) {
            $csv .= implode(",", [
                $u['id'],
                '"' . str_replace('"', '""', $u['name']) . '"',
                $u['email'],
                $u['role'],
            ]) . "\n";
        }

        return Response::streamDownload(function () use ($csv) {
            echo $csv;
        }, 'users-export.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    // Filtering + pagination (on a Collection for demo)
    protected function filtered(): Collection
    {
        $s = mb_strtolower(trim($this->search));

        return $this->allUsers()
            ->filter(function ($u) use ($s) {
                $hit = $s === '' ||
                    str_contains(mb_strtolower($u['name']), $s) ||
                    str_contains(mb_strtolower($u['email']), $s);

                $roleOk   = $this->role === ''   || $u['role']   === $this->role;

                return $hit && $roleOk;
            })
            ->values();
    }

    protected function paginated(): LengthAwarePaginator
    {
        $data  = $this->filtered();
        $total = $data->count();

        // Keep page within bounds
        $lastPage = max(1, (int) ceil($total / max(1, $this->pageSize)));
        if ($this->page > $lastPage) $this->page = $lastPage;
        if ($this->page < 1) $this->page = 1;

        $items = $data
            ->slice(($this->page - 1) * $this->pageSize, $this->pageSize)
            ->values();

        return new LengthAwarePaginator(
            items: $items,
            total: $total,
            perPage: $this->pageSize,
            currentPage: $this->page,
            options: ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    public function render()
    {
        $paginator  = $this->paginated();
        $visibleIds = $paginator->pluck('id')->all();

        return view('livewire.admin.users-index', [
            'rows'       => $paginator,
            'visibleIds' => $visibleIds,
        ]);
    }
}
