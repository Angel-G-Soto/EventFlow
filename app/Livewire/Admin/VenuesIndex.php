<?php

namespace App\Livewire\Admin;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Livewire\Concerns\TableSelection;
use App\Repositories\VenueRepository;
use App\Livewire\Traits\VenueFilters;
use App\Livewire\Traits\VenueEditState;

#[Layout('layouts.app')]
class VenuesIndex extends Component
{
    use TableSelection, VenueFilters, VenueEditState;

    public array $venues = [];
    public $csvFile;
    public function mount()
    {
        $this->venues = VenueRepository::all();
    }

    protected function allVenues(): Collection
    {
        $deletedIndex = array_flip(array_unique(array_merge(
            array_map('intval', session('soft_deleted_venue_ids', [])),
            array_map('intval', session('hard_deleted_venue_ids', []))
        )));

        $combined = array_filter(
            $this->venues,
            function (array $v) use ($deletedIndex) {
                return !isset($deletedIndex[(int) $v['id']]);
            }
        );

        return collect($combined);
    }

    /**
     * Resets all filters to their default values, and resets the current page to 1.
     *
     * This method is called when the user clicks the "Clear" button on the filter form.
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->department = '';
        $this->capMin = null;
        $this->capMax = null;
        $this->selected = [];
        $this->page = 1;
    }

    /**
     * Navigates to a given page number.
     *
     * @param int $target The target page number.
     *
     * This function will compute bounds from the current filters, and then
     * set the page number to the maximum of 1 and the minimum of the
     * target and the last page number. If the class has a 'selected'
     * property, it will be cleared when the page changes.
     */
    public function goToPage(int $target): void
    {
        // compute bounds from current filters
        $total = $this->filtered()->count();
        $last  = max(1, (int) ceil($total / max(1, $this->pageSize)));

        $this->page = max(1, min($target, $last));

        // clear selections when page changes
        if (property_exists($this, 'selected')) {
            $this->selected = [];
        }
    }

    /**
     * Resets the current page to 1 when the search filter is updated.
     *
     * This function will be called whenever the search filter is updated,
     * and will reset the current page to 1.
     */
    public function applySearch()
    {
        $this->page = 1;
    }

    /**
     * Resets the edit fields to their default values and opens the edit venue modal.
     *
     * This function is called when the user clicks the "Add Venue" button.
     */
    public function openCreate(): void
    {
        $this->resetEdit();
        $this->dispatch('bs:open', id: 'venueModal');
    }

    public function openCsvModal(): void
    {
        $this->reset('csvFile');
        $this->dispatch('bs:open', id: 'csvModal');
    }

    public function uploadCsv()
    {
        $this->validate([
            'csvFile' => 'required|file|mimes:csv,txt',
        ]);

        $path = $this->csvFile->getRealPath();
        $rows = array_map('str_getcsv', file($path));
        $header = array_map('trim', array_shift($rows));

        foreach ($rows as $row) {
            $data = array_combine($header, $row);
            if (!$data) continue;
            $venue = [
                'id'         => (int) now()->format('Uu') + rand(1, 9999),
                'name'       => $data['name'] ?? '',
                'room'       => $data['room'] ?? '',
                'capacity'   => (int)($data['capacity'] ?? 0),
                'department' => $data['department'] ?? '',
                'manager'    => $data['manager'] ?? '',
                'status'     => $data['status'] ?? 'Active',
                'features'   => isset($data['features']) ? explode('|', $data['features']) : [],
                'timeRanges' => isset($data['timeRanges']) ? json_decode($data['timeRanges'], true) : [],
            ];
            $this->venues[] = $venue;
        }

        $this->dispatch('bs:close', id: 'csvModal');
        $this->dispatch('toast', message: 'Venues imported from CSV');
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
        $this->timeRanges = $v['timeRanges'] ?? [];

        $this->dispatch('bs:open', id: 'venueModal');
    }

    protected function rules(): array
    {
        return [
            'vName'      => 'required|string|max:150',
            'vRoom'      => 'required|string|max:50',
            'vDepartment' => 'required|string|max:120',
            'vCapacity'  => 'required|integer|min:0', // >= 0
            'vManager'   => 'nullable|string|max:120',
            'vStatus'    => 'required|in:Active,Inactive',
            'vFeatures'  => 'array',
            'justification' => 'nullable|string|max:200',
            'timeRanges'            => 'array',
            'timeRanges.*.from'     => 'required|date_format:H:i',
            'timeRanges.*.to'       => 'required|date_format:H:i',
        ];
    }

    /**
     * Validates only the justification field.
     *
     * This function is a helper to validate only the justification length by calling
     * `validateOnly` with the justification field as the parameter.
     */
    protected function validateJustification(): void
    {
        $this->validateOnly('justification');
    }

    /**
     * Validates the venue modal form and then opens the justification modal.
     *
     * This method is called when the user clicks the "Save" button on the venue modal.
     */
    public function save(): void
    {
        $this->validate();

        // ensure each 'to' is after its 'from'
        foreach ($this->timeRanges as $idx => $tr) {
            $from = $tr['from'] ?? null;
            $to   = $tr['to']   ?? null;

            if ($from && $to && $to <= $from) {
                $this->addError("timeRanges.$idx.to", 'The end time must be after the start time.');
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return; // don’t proceed if there are errors
        }

        $this->actionType = 'save';
        $this->dispatch('bs:open', id: 'venueJustify');
    }

    /**
     * Confirms the save action and updates the session with the new/edited venue data.
     * If the venue is being created, it adds the new venue to the list.
     * If the venue is being edited, it updates the existing venue in the list.
     * Finally, it dispatches events to close the justification modal, edit venue modal, and show a toast message with a success message.
     */
    public function confirmSave(): void
    {
        $this->validateJustification();

        $venue = [
            'id'         => $this->editId ?? (int) now()->format('Uu'),
            'name'       => $this->vName,
            'room'       => $this->vRoom,
            'capacity'   => $this->vCapacity,
            'department' => $this->vDepartment,
            'manager'    => $this->vManager,
            'status'     => $this->vStatus,
            'features'   => $this->vFeatures,
            'timeRanges' => $this->timeRanges,
        ];

        $isCreating = !$this->editId;

        if ($isCreating) {
            $this->venues[] = $venue;
        } else {
            foreach ($this->venues as &$v) {
                if ($v['id'] === $this->editId) {
                    $v = $venue;
                    break;
                }
            }
            unset($v);
        }

        $this->dispatch('bs:close', id: 'venueJustify');
        $this->dispatch('bs:close', id: 'venueModal');
        $this->dispatch('toast', message: 'Venue saved');

        $this->reset(['justification', 'actionType', 'editId']);

        if ($isCreating) {
            $this->jumpToLastPageAfterCreate();
        }
    }

    /**
     * Opens the justification modal for the venue with the given ID.
     * This function should be called when the user wants to delete a venue.
     * It sets the currently edited venue ID and the isDeleting flag to true, and then opens the justification modal.
     * @param int $id The ID of the venue to delete
     */
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

    /**
     * Opens the justification modal for bulk deletion of venues.
     *
     * This function should be called when the user wants to delete multiple venues at once.
     * It sets the isBulkDeleting flag to true, and then opens the justification modal.
     */
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

    /**
     * Restore all soft deleted venues.
     *
     * This function will reset the soft_deleted_venue_ids session key to an empty array,
     * effectively restoring all soft deleted venues.
     *
     * @return void
     */
    public function restoreUsers(): void
    {
        session(['soft_deleted_venue_ids' => []]);
        $this->dispatch('toast', message: 'All deleted venues restored');
    }


    public function addTimeRange(): void
    {
        $this->timeRanges[] = ['from' => '', 'to' => '', 'reason' => ''];
    }


    public function removeTimeRange(int $i): void
    {
        unset($this->timeRanges[$i]);
        $this->timeRanges = array_values($this->timeRanges);
    }

    /**
     * Returns a filtered collection of venues based on the current search query.
     *
     * The collection is filtered on the following criteria:
     * - The search query is empty, or the venue name or manager contains the search query.
     * - The department filter is empty, or the venue department matches the filter.
     * - The capacity filter is empty, or the venue capacity is within the range of the filter.
     *
     * @return Collection The filtered collection of venues
     */
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

    /**
     * Paginate the filtered collection of venues.
     *
     * This function takes the filtered collection of venues and paginates it
     * based on the current page and page size. It then returns a
     * LengthAwarePaginator object, which can be used to display the
     * paginated data.
     *
     * @return LengthAwarePaginator
     */
    protected function paginated(): LengthAwarePaginator
    {
        $data = $this->filtered();
        $items = $data->slice(($this->page - 1) * $this->pageSize, $this->pageSize)->values();
        return new LengthAwarePaginator($items, $data->count(), $this->pageSize, $this->page, [
            'path' => request()->url(),
            'query' => request()->query()
        ]);
    }

    /**
     * Resets the edit form properties to their default values.
     *
     * This function is called when the edit form is closed or when the user
     * submits the form successfully. It resets the properties to their
     * default values, ensuring that the form is cleared and ready for
     * the next edit operation.
     */
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
            'timeRanges'
        ]);
        $this->vCapacity = 0;
        $this->vStatus   = 'Active';
        $this->vFeatures = [];
        $this->timeRanges = [];
    }

    /**
     * Renders the venues index page.
     *
     * This function renders the venues index page and provides the necessary data
     * to the view. It paginates the filtered collection of venues and ensures
     * that the current page is within the bounds of the paginator. It then
     * returns the view with the paginated data and the visible IDs.
     */
    public function render()
    {
        $paginator = $this->paginated();
        if ($this->page !== $paginator->currentPage()) {
            $paginator = $this->paginated();
        }
        $visibleIds = $paginator->pluck('id')->all();
        return view('livewire.admin.venues-index', [
            'rows' => $paginator,
            'visibleIds' => $visibleIds,
        ]);
    }
}
