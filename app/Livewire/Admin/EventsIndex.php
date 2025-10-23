<?php

namespace App\Livewire\Admin;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class EventsIndex extends Component
{
    // Filters
    public string $search = '';
    public string $status = '';
    public string $department = '';
    public string $venue = '';
    public ?string $from = null;
    public ?string $to   = null;
    public string $requestor = '';
    public string $category  = '';

    // Paging
    public int $page = 1;
    public int $pageSize = 25;

    // Edit (admins can edit ALL fields per your confirmation)
    public ?int $editId = null;
    public string $eTitle = '';
    public string $ePurpose = '';
    public string $eNotes = '';
    public string $eDepartment = '';
    public string $eVenue = '';
    public string $eFrom = '';
    public string $eTo = '';
    public int    $eAttendees = 0;
    public string $eCategory = '';
    public bool   $ePolicyAlcohol = false;
    public bool   $ePolicyCurfew  = false;

    public string $actionType = ''; // 'approve','deny','reroute','advance','override','save'
    public string $justification = '';

    protected function allRequests(): Collection
    {
        return collect([
            [
                'id' => 1001,
                'title' => 'Welcome Fair',
                'requestor' => 'jdoe',
                'department' => 'Arts',
                'venue' => 'Courtyard',
                'from' => '2025-10-28 16:00',
                'to' => '2025-10-28 20:00',
                'status' => 'Pending',
                'category' => 'Outdoor',
                'updated' => '2025-10-12 09:31',
                'purpose' => 'Student org fair.',
                'attendees' => 200,
                'notes' => 'Need extra lights.'
            ],
            [
                'id' => 1002,
                'title' => 'Bio Colloquium',
                'requestor' => 'mruiz',
                'department' => 'Biology',
                'venue' => 'Auditorium A',
                'from' => '2025-11-05 09:00',
                'to' => '2025-11-05 12:00',
                'status' => 'Approved',
                'category' => 'Lecture',
                'updated' => '2025-10-10 13:22',
                'purpose' => 'Guest lecture.',
                'attendees' => 280,
                'notes' => 'Record session.'
            ],
        ]);
    }

    protected function filtered(): Collection
    {
        $s = mb_strtolower(trim($this->search));
        return $this->allRequests()->filter(function ($r) use ($s) {
            $hit = $s === '' ||
                str_contains(mb_strtolower($r['title']), $s) ||
                str_contains(mb_strtolower($r['requestor']), $s);
            $statOk  = $this->status === '' || $r['status'] === $this->status;
            $deptOk  = $this->department === '' || $r['department'] === $this->department;
            $venueOk = $this->venue === '' || $r['venue'] === $this->venue;
            $catOk   = $this->category === '' || $r['category'] === $this->category;

            $dateOk = true;
            if ($this->from) $dateOk = $dateOk && ($r['from'] >= $this->from);
            if ($this->to)   $dateOk = $dateOk && ($r['to']   <= $this->to);

            return $hit && $statOk && $deptOk && $venueOk && $dateOk && $catOk;
        })->values();
    }

    protected function paginated(): LengthAwarePaginator
    {
        $data = $this->filtered();
        $items = $data->slice(($this->page - 1) * $this->pageSize, $this->pageSize)->values();
        return new LengthAwarePaginator($items, $data->count(), $this->pageSize, $this->page, ['path' => request()->url(), 'query' => request()->query()]);
    }

    public function updated($name, $value)
    {
        if (in_array($name, ['search', 'status', 'department', 'venue', 'from', 'to', 'category', 'pageSize'])) $this->page = 1;
    }

    public function openEdit(int $id): void
    {
        $r = $this->filtered()->firstWhere('id', $id);
        if (!$r) return;
        $this->editId = $r['id'];
        $this->eTitle = $r['title'];
        $this->ePurpose = $r['purpose'] ?? '';
        $this->eNotes = $r['notes'] ?? '';
        $this->eDepartment = $r['department'];
        $this->eVenue = $r['venue'];
        $this->eFrom = substr($r['from'], 0, 16);
        $this->eTo   = substr($r['to'], 0, 16);
        $this->eAttendees = $r['attendees'] ?? 0;
        $this->eCategory  = $r['category'] ?? '';
        $this->ePolicyAlcohol = false;
        $this->ePolicyCurfew  = false;

        $this->dispatch('bs:open', id: 'oversightEdit');
    }

    public function saveEdits(): void
    {
        $this->actionType = 'save';
        $this->dispatch('bs:open', id: 'oversightJustify');
    }

    public function approve(): void
    {
        $this->actionType = 'approve';
        $this->dispatch('bs:open', id: 'oversightJustify');
    }
    public function deny(): void
    {
        $this->actionType = 'deny';
        $this->dispatch('bs:open', id: 'oversightJustify');
    }
    public function advance(): void
    {
        $this->actionType = 'advance';
        $this->dispatch('bs:open', id: 'oversightJustify');
    }
    public function reroute(): void
    {
        $this->actionType = 'reroute';
        $this->dispatch('bs:open', id: 'oversightJustify');
    }
    public function override(): void
    {
        $this->actionType = 'override';
        $this->dispatch('bs:open', id: 'oversightJustify');
    }

    public function confirmAction(): void
    {
        // Write AuditTrail + EventRequestHistory with $this->actionType and $this->justification
        $this->dispatch('bs:close', id: 'oversightJustify');
        $this->dispatch('bs:close', id: 'oversightEdit');
        $this->dispatch('toast', message: ucfirst($this->actionType) . ' completed');
        $this->reset('actionType', 'justification');
    }

    public function render()
    {
        return view('livewire.admin.events-index', ['rows' => $this->paginated()]);
    }
}
