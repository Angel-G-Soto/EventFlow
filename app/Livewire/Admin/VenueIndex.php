<?php

namespace App\Livewire\Admin;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class VenueIndex extends Component
{
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
    public string $vBuilding = '';
    public string $vRoom = '';
    public ?int $vCapacity = 0;
    public string $vDepartment = '';
    public string $vManager = '';
    public string $vStatus = 'Active';
    public array  $vFeatures = [];   // ['A/V','Wheelchair','Tiered Seating']
    public ?string $vNotes = null;

    // Inline availability/blackouts (inside edit modal)
    /** @var array<int,array{from:string,to:string,reason:string}> */
    public array $blackouts = [];

    public string $justification = '';

    // --- Demo dataset -------------------------------------------------------
    protected function allVenues(): Collection
    {
        return collect([
            [
                'id' => 1,
                'name' => 'Auditorium A',
                'building' => 'Main',
                'room' => '101',
                'capacity' => 300,
                'department' => 'Arts',
                'manager' => 'jdoe',
                'status' => 'Active',
                'features' => ['A/V', 'Wheelchair'],
                'availability' => 'Most weekdays 8–18'
            ],
            [
                'id' => 2,
                'name' => 'Lab West',
                'building' => 'Science',
                'room' => 'B12',
                'capacity' => 32,
                'department' => 'Biology',
                'manager' => 'mruiz',
                'status' => 'Inactive',
                'features' => ['A/V'],
                'availability' => 'Contact dept.'
            ],
            [
                'id' => 3,
                'name' => 'Courtyard',
                'building' => 'North',
                'room' => 'OUT',
                'capacity' => 120,
                'department' => 'Facilities',
                'manager' => 'lortiz',
                'status' => 'Active',
                'features' => ['Open Air'],
                'availability' => 'Evenings only'
            ],
        ]);
    }

    // --- Validation rules (you asked to expand)
    protected function rules(): array
    {
        return [
            'vName'      => ['required', 'string', 'max:150'],
            'vBuilding'  => ['required', 'string', 'max:100'],
            'vRoom'      => ['required', 'string', 'max:50'],
            'vCapacity'  => ['required', 'integer', 'min:0'], // >= 0
            'vDepartment' => ['nullable', 'string', 'max:120'],
            'vManager'   => ['nullable', 'string', 'max:120'],
            'vStatus'    => ['required', 'in:Active,Inactive'],
            'vFeatures'  => ['array'],
            'vNotes'     => ['nullable', 'string', 'max:2000'],
            // blackout rows (light validation)
            'blackouts.*.from'   => ['required', 'date'],
            'blackouts.*.to'     => ['required', 'date', 'after_or_equal:blackouts.*.from'],
            'blackouts.*.reason' => ['nullable', 'string', 'max:300'],
        ];
    }
    // Unique constraint idea for Eloquent later:
    // unique across building+room: add DB unique index (building, room)

    // --- Filtering & pagination --------------------------------------------
    protected function filtered(): Collection
    {
        $needle = mb_strtolower(trim($this->search));
        return $this->allVenues()->filter(function ($v) use ($needle) {
            $hit = $needle === '' ||
                str_contains(mb_strtolower($v['name']), $needle) ||
                str_contains(mb_strtolower($v['building']), $needle) ||
                str_contains(mb_strtolower($v['manager']), $needle) ||
                str_contains(mb_strtolower($v['department']), $needle);

            $depOk = $this->department === '' || $v['department'] === $this->department;

            $capOk = true;
            if ($this->capMin !== null) $capOk = $capOk && ($v['capacity'] >= $this->capMin);
            if ($this->capMax !== null) $capOk = $capOk && ($v['capacity'] <= $this->capMax);

            return $hit && $depOk && $capOk;
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

    // --- UI events ----------------------------------------------------------
    public function updated($name, $value): void
    {
        if (in_array($name, ['search', 'department', 'capMin', 'capMax', 'pageSize'])) {
            $this->page = 1;
        }
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
        $this->vBuilding = $v['building'];
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

    public function addBlackout(): void
    {
        $this->blackouts[] = ['from' => '', 'to' => '', 'reason' => ''];
    }
    public function removeBlackout(int $i): void
    {
        unset($this->blackouts[$i]);
        $this->blackouts = array_values($this->blackouts);
    }

    public function save(): void
    {
        $this->validate();

        // require justification for mutating actions
        $this->dispatch('bs:open', id: 'venueJustify');
    }

    public function confirmSave(): void
    {
        // persist + write AuditTrail with $this->justification
        $this->dispatch('bs:close', id: 'venueJustify');
        $this->dispatch('bs:close', id: 'venueModal');
        $this->dispatch('toast', message: 'Venue saved');
        $this->reset('justification');
    }

    public function delete(int $id): void
    {
        $this->editId = $id;
        $this->dispatch('bs:open', id: 'venueJustify');
    }
    public function confirmDelete(): void
    {
        // hard/soft delete switch could be added later
        $this->dispatch('bs:close', id: 'venueJustify');
        $this->dispatch('toast', message: 'Venue deleted');
        $this->reset(['editId', 'justification']);
    }

    protected function resetEdit(): void
    {
        $this->reset([
            'editId',
            'vName',
            'vBuilding',
            'vRoom',
            'vCapacity',
            'vDepartment',
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
        return view('livewire.admin.venue-index', ['rows' => $rows]);
    }
}
