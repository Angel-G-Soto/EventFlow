<?php

namespace App\Livewire\Admin;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Livewire\Concerns\TableSelection;

#[Layout('layouts.app')]
class VenuesIndex extends Component
{
    use TableSelection;

    // Filters
    public string $search = '';
    public string $department = '';
    public ?int $capMin = null;
    public ?int $capMax = null;

    // Paging
    public int $page = 1;
    public int $pageSize = 10;

    // Selection + edit modal
    public ?int $editId = null;
    public string $vName = '';
    public string $vDepartment = '';
    public string $vRoom = '';
    public ?int $vCapacity = 0;
    public string $vManager = '';
    public string $vStatus = 'Active';
    public array  $vFeatures = [];   // ['Allow Teaching Online','Allow Teaching With Multimedia','Allow Teaching wiht computer','Allow Teaching']
    public ?string $vNotes = null;

    // Inline availability/blackouts (inside edit modal)
    /** @var array<int,array{from:string,to:string,reason:string}> */
    public array $blackouts = [];

    public string $justification = '';
    public string $actionType = '';
    public string $deleteType = 'soft'; // 'soft' or 'hard'

    public function getIsDeletingProperty(): bool
    {
        return $this->actionType === 'delete';
    }

    public function getIsBulkDeletingProperty(): bool
    {
        return $this->actionType === 'bulkDelete';
    }

    private static array $venues = [
        [
            'id' => 1,
            'name' => 'Auditorium A',
            'department' => 'Arts',
            'room' => '101',
            'capacity' => 300,
            'manager' => 'jdoe',
            'status' => 'Active',
            'features' => ['Allow Teaching', 'Allow Teaching With Multimedia'],
            'availability' => 'Most weekdays 8–18'
        ],
        [
            'id' => 2,
            'name' => 'Lab West',
            'department' => 'Biology',
            'room' => 'B12',
            'capacity' => 32,
            'manager' => 'mruiz',
            'status' => 'Inactive',
            'features' => ['Allow Teaching wiht computer', 'Allow Teaching'],
            'availability' => 'Contact dept.'
        ],
        [
            'id' => 3,
            'name' => 'Courtyard',
            'department' => 'Facilities',
            'room' => 'OUT',
            'capacity' => 120,
            'manager' => 'lortiz',
            'status' => 'Active',
            'features' => ['Allow Teaching Online'],
            'availability' => 'Evenings only'
        ],
    ];

    protected function allVenues(): Collection
    {
        $deletedIndex = array_flip(array_unique(array_merge(
            array_map('intval', session('soft_deleted_venue_ids', [])),
            array_map('intval', session('hard_deleted_venue_ids', []))
        )));

        $combined = array_filter(
            self::$venues,
            function (array $v) use ($deletedIndex) {
                return !isset($deletedIndex[(int) $v['id']]);
            }
        );

        return collect($combined);
    }

    public function updatedSearch()
    {
        $this->page = 1;
        $this->selected = [];
    }

    public function updatedPageSize()
    {
        $this->page = 1;
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->department = '';
        $this->capMin = null;
        $this->capMax = null;
        $this->selected = [];
        $this->page = 1;
    }

    public function openCreate(): void
    {
        $this->resetEdit();
        $this->dispatch('bs:open', id: 'venueModal');
    }

    public function openEdit(int $id): void
    {
        $v = $this->filtered()->firstWhere('id', $id);
        if (!$v) return;
        $this->editId    = $v['id'];
        $this->vName     = $v['name'];
        $this->vRoom     = $v['room'];
        $this->vCapacity = $v['capacity'];
        $this->vDepartment = $v['department'];
        $this->vManager  = $v['manager'];
        $this->vStatus   = $v['status'];
        $this->vFeatures = $v['features'];
        $this->blackouts = []; // load blackouts from DB in real impl
        $this->vNotes    = null;

        $this->dispatch('bs:open', id: 'venueModal');
    }

    protected function rules(): array
    {
        return [
            'vName'      => ['required', 'string', 'max:150'],
            'vRoom'      => ['required', 'string', 'max:50'],
            'vDepartment' => ['required', 'string', 'max:120'],
            'vCapacity'  => ['required', 'integer', 'min:0'], // >= 0
            'vManager'   => ['nullable', 'string', 'max:120'],
            'vStatus'    => ['required', 'in:Active,Inactive'],
            'vFeatures'  => ['array'],
            'vNotes'     => ['nullable', 'string', 'max:2000'],
            'justification' => ['required', 'string', 'min:3'],
            // blackout rows (light validation)
            'blackouts.*.from'   => ['required', 'date'],
            'blackouts.*.to'     => ['required', 'date', 'after_or_equal:blackouts.*.from'],
            'blackouts.*.reason' => ['nullable', 'string', 'max:300'],
        ];
    }

    protected function validateJustification(): void
    {
        $this->validateOnly('justification');
    }

    public function save(): void
    {
        $this->validate();
        $this->actionType = 'save';
        $this->dispatch('bs:open', id: 'venueJustify');
    }

    public function confirmSave(): void
    {
        $this->validateJustification();
        $isCreating = !$this->editId;
        $this->dispatch('bs:close', id: 'venueJustify');
        $this->dispatch('bs:close', id: 'venueModal');
        $this->dispatch('toast', message: 'Venue saved');
        $this->reset(['justification', 'actionType']);
        if ($isCreating) {
            $this->jumpToLastPageAfterCreate();
        }
    }

    protected function jumpToLastPageAfterCreate(): void
    {
        $total = $this->filtered()->count();
        $last = max(1, (int) ceil($total / max(1, $this->pageSize)));
        $this->page = $last;
        $this->selected = [];
    }

    public function delete(int $id): void
    {
        $this->editId = $id;
        $this->actionType = 'delete';
        $this->dispatch('bs:open', id: 'venueJustify');
    }

    public function confirmDelete(): void
    {
        if ($this->editId) {
            $this->validateJustification();
            session()->push($this->deleteType === 'hard' ? 'hard_deleted_venue_ids' : 'soft_deleted_venue_ids', $this->editId);
            unset($this->selected[$this->editId]);
        }
        $this->dispatch('bs:close', id: 'venueJustify');
        $this->dispatch('toast', message: 'Venue ' . ($this->deleteType === 'hard' ? 'permanently deleted' : 'deleted'));
        $this->reset(['editId', 'justification', 'actionType']);
    }

    public function bulkDelete(): void
    {
        if (empty($this->selected)) return;
        $this->actionType = 'bulkDelete';
        $this->dispatch('bs:open', id: 'venueJustify');
    }

    public function confirmBulkDelete(): void
    {
        $selectedIds = array_keys($this->selected);
        if (empty($selectedIds)) return;
        $this->validateJustification();
        $sessionKey = $this->deleteType === 'hard' ? 'hard_deleted_venue_ids' : 'soft_deleted_venue_ids';
        $existingIds = session($sessionKey, []);
        $newIds = array_merge($existingIds, $selectedIds);
        session([$sessionKey => array_values(array_unique($newIds))]);
        $this->selected = [];
        $this->dispatch('bs:close', id: 'venueJustify');
        $this->dispatch('toast', message: count($selectedIds) . ' venues ' . ($this->deleteType === 'hard' ? 'permanently deleted' : 'deleted'));
        $this->reset(['justification', 'actionType']);
    }

    public function restoreUsers(): void
    {
        session(['soft_deleted_venue_ids' => []]);
        $this->dispatch('toast', message: 'All deleted venues restored');
    }

    public function addBlackout(): void
    {
        $this->blackouts[] = ['from' => '', 'to' => '', 'reason' => ''];
    }

    public function removeBlackout(int $i): void
    {
        unset($this->blackouts[$i]);
        $this->blackouts = array_values($this->blackouts);
    }

    protected function filtered(): Collection
    {
        $needle = mb_strtolower(trim($this->search));
        return $this->allVenues()->filter(function ($v) use ($needle) {
            $hit = $needle === '' ||
                str_contains(mb_strtolower($v['name']), $needle) ||
                str_contains(mb_strtolower($v['manager']), $needle);

            $deptOk = $this->department === '' || $v['department'] === $this->department;

            $capOk = true;
            if ($this->capMin !== null) $capOk = $capOk && ($v['capacity'] >= $this->capMin);
            if ($this->capMax !== null) $capOk = $capOk && ($v['capacity'] <= $this->capMax);

            return $hit && $deptOk && $capOk;
        })->values();
    }

    protected function paginated(): LengthAwarePaginator
    {
        $data = $this->filtered();
        $items = $data->slice(($this->page - 1) * $this->pageSize, $this->pageSize)->values();
        return new LengthAwarePaginator($items, $data->count(), $this->pageSize, $this->page, [
            'path' => request()->url(),
            'query' => request()->query()
        ]);
    }

    protected function resetEdit(): void
    {
        $this->reset([
            'editId',
            'vName',
            'vDepartment',
            'vRoom',
            'vCapacity',
            'vManager',
            'vStatus',
            'vFeatures',
            'vNotes',
            'blackouts'
        ]);
        $this->vCapacity = 0;
        $this->vStatus   = 'Active';
        $this->vFeatures = [];
        $this->blackouts = [];
    }

    public function render()
    {
        $rows = $this->paginated();
        $visibleIds = $rows->pluck('id')->all();
        return view('livewire.admin.venues-index', [
            'rows' => $rows,
            'visibleIds' => $visibleIds,
        ]);
    }
}