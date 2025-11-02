<?php

namespace App\Livewire\Admin;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Repositories\VenueRepository;
use App\Livewire\Traits\VenueFilters;
use App\Livewire\Traits\VenueEditState;

#[Layout('layouts.app')]
class VenuesIndex extends Component
{
    // Traits / shared state
    use VenueFilters, VenueEditState;

    // Properties / backing stores
    public array $venues = [];
    public $csvFile;

    // Sorting
    public string $sortField = '';
    public string $sortDirection = 'asc';

    // Accessors and Mutators
    /**
     * Dynamic list of departments based on the current venues (excluding soft-deleted).
     * Sorted naturally, case-insensitive.
     *
     * @return array<int,string>
     */
    public function getDepartmentsProperty(): array
    {
        // Gather departments from current venues (excluding soft-deleted)
        $deps = $this->allVenues()
            ->pluck('department')
            ->filter(fn($venue) => is_string($venue) && trim($venue) !== '')
            ->map(fn($venue) => trim($venue))
            ->all();

        // Case-insensitive de-duplication while preserving first casing seen
        $map = [];
        foreach ($deps as $d) {
            $k = mb_strtolower($d);
            if (!isset($map[$k])) {
                $map[$k] = $d;
            }
        }

        // Natural, case-insensitive sort
        $values = array_values($map);
        usort($values, fn($a, $b) => strnatcasecmp($a, $b));
        return $values;
    }

    /**
     * Initializes the component by retrieving all the venues from the database.
     *
     * This function is called when the component is mounted.
     */
    // Lifecycle
    public function mount()
    {
        $this->venues = VenueRepository::all();
    }


    // Filters: clear/reset
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
        $this->page = 1;
    }

    // Pagination & filter reactions
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
    }

    // Filters: search update reaction
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
     * Toggle or set the active sort column and direction.
     */
    public function sortBy(string $field): void
    {
        if ($field === $this->sortField) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->page = 1;
    }

    // Create / modal workflows
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

    /**
     * Resets the csvFile field to its default value and opens the CSV modal.
     *
     * This function will reset the csvFile field to its default value and
     * open the CSV modal. It is called when the user clicks the "Add
     * Venues by CSV" button.
     */
    public function openCsvModal(): void
    {
        $this->reset('csvFile');
        $this->dispatch('bs:open', id: 'csvModal');
    }

    /**
     * No-op handler when CSV upload is disabled; emits an informational toast.
     */
    public function csvUploadDisabled(): void
    {
        $this->dispatch('toast', message: 'CSV upload is disabled');
    }

    // CSV import
    /**
     * Validates the CSV file, and then imports the venues from the CSV file.
     *
     * The CSV file is expected to have the following columns:
     * - room code
     * - department name
     * - name
     * - capacity
     * - email
     * - allow teaching online
     * - allow teaching with multimedia
     * - allow teaching with computer
     * - allow teaching
     * - features (| separated string)
     * - timeRanges (| separated string)
     *
     * If a column is not found, it will be skipped.
     * The "name" column is used as the name of the venue.
     * If the "name" column is not found, the "room code" column is used as the name of the venue.
     * The "manager" column is used as the email of the venue.
     * If the "manager" column is not found, an empty string is used as the email.
     * The "features" column is an array of strings containing the features of the venue.
     * The "timeRanges" column is an array of time ranges in the format of [[int, int], ...].
     * After importing the venues, the CSV modal is closed and a toast is dispatched with the message "Venues imported from CSV".
     */
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
                //'manager'    => $data['manager'] ?? '',
                //'status'     => $data['status'] ?? 'Active',
                'features'   => isset($data['features']) ? explode('|', $data['features']) : [],
                //'timeRanges' => isset($data['timeRanges']) ? json_decode($data['timeRanges'], true) : [],
            ];
            $this->venues[] = $venue;
        }

        $this->dispatch('bs:close', id: 'csvModal');
        $this->dispatch('toast', message: 'Venues imported from CSV');
    }

    // Edit workflow
    /**
     * Opens the edit venue modal with the given ID.
     * If the venue is not found, the function does nothing.
     * It sets the currently edited venue ID and the values of the venue to be edited, and then opens the edit venue modal.
     * @param int $id The ID of the venue to edit
     */
    public function openEdit(int $id): void
    {
        $venue = $this->filtered()->firstWhere('id', $id);
        if (!$venue) return;
        $this->editId    = $venue['id'];
        $this->vName     = $venue['name'];
        $this->vRoom     = $venue['room'];
        $this->vCapacity = $venue['capacity'];
        $this->vDepartment = $venue['department'];
        $this->vManager  = $venue['manager'];
        $this->vStatus   = $venue['status'];
        $this->vFeatures = $venue['features'];
        $this->timeRanges = $venue['timeRanges'] ?? [];

        $this->dispatch('bs:open', id: 'venueModal');
    }

    // Validation / rules
    /**
     * Returns an array of rules for the edit venue form.
     *
     * @return array
     */
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
            // Justification is validated at confirm time, not at form validate/save time
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
        // Require a non-trivial justification only when confirming
        $this->validate([
            'justification' => ['required', 'string', 'min:3', 'max:200'],
        ]);
    }

    // Persist edits / session writes
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
                if ($venue['id'] === $this->editId) {
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
    }

    // Delete workflows
    /**
     * Opens the justification modal for the venue with the given ID.
     * This function should be called when the user wants to delete a venue.
     * It sets the currently edited venue ID and sets actionType to 'delete', then opens the justification modal.
     * @param int $id The ID of the venue to delete
     */
    public function delete(int $id): void
    {
        $this->editId = $id;
        $this->actionType = 'delete';
        $this->dispatch('bs:open', id: 'venueConfirm');
    }

    /**
     * Proceeds from the delete confirmation to the justification modal.
     */
    public function proceedDelete(): void
    {
        $this->dispatch('bs:close', id: 'venueConfirm');
        $this->dispatch('bs:open', id: 'venueJustify');
    }

    /**
     * Confirms the deletion of a venue.
     *
     * This function will validate the justification entered by the user, and then delete the venue with the given ID.
     * After deletion, it clamps the current page to prevent the page from becoming out of bounds.
     * Finally, it shows a toast message indicating the venue was deleted.
     */
    public function confirmDelete(): void
    {
        if ($this->editId) {
            $this->validateJustification();
            session()->push('soft_deleted_venue_ids', $this->editId);
        }
        $this->dispatch('bs:close', id: 'venueJustify');
        $this->dispatch('toast', message: 'Venue deleted');
        $this->reset(['editId', 'justification', 'actionType']);
    }

    // Restore-all functionality removed


    // Time range helpers
    /**
     * Adds a new time range to the list of time ranges.
     *
     * This function adds a new empty time range to the list of time ranges.
     * The new time range will be added to the end of the list.
     */
    public function addTimeRange(): void
    {
        $this->timeRanges[] = ['from' => '', 'to' => '', 'reason' => ''];
    }


    /**
     * Removes the time range at index $i from the list of time ranges.
     *
     * This function removes the time range at index $i from the list of time ranges,
     * and then re-indexes the list to maintain contiguous keys.
     *
     * @param int $i The index of the time range to remove.
     */
    public function removeTimeRange(int $i): void
    {
        unset($this->timeRanges[$i]);
        $this->timeRanges = array_values($this->timeRanges);
    }

    // Render
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
            'departments' => $this->departments,
        ]);
    }

    // Private/Protected Helper Methods
    /**
     * Returns a collection of all the venues, excluding any soft-deleted venues.
     */
    protected function allVenues(): Collection
    {
        // Exclude soft-deleted IDs for this session
        $deletedIds   = array_map('intval', session('soft_deleted_venue_ids', []));
        $deletedIndex = array_flip(array_unique($deletedIds));

        $combined = array_values(array_filter($this->venues, fn(array $venue) => !isset($deletedIndex[(int) ($venue['id'] ?? 0)])));

        // Normalize minimal shape to avoid undefined index notices in views/filters
        $combined = array_map(function (array $venue) {
            $venue['manager']   = $venue['manager']   ?? '';
            $venue['status']    = $venue['status']    ?? 'Active';
            $venue['features']  = isset($venue['features']) && is_array($venue['features']) ? array_values(array_unique($venue['features'])) : [];
            $venue['timeRanges'] = isset($venue['timeRanges']) && is_array($venue['timeRanges']) ? array_values($venue['timeRanges']) : [];
            return $venue;
        }, $combined);

        return collect($combined);
    }

    /**
     * Returns a filtered collection of venues based on the current search query.
     */
    protected function filtered(): Collection
    {
        $needle = mb_strtolower(trim($this->search));
        return $this->allVenues()->filter(function ($venue) use ($needle) {
            $hit = $needle === '' ||
                str_contains(mb_strtolower($venue['name']), $needle) ||
                str_contains(mb_strtolower($venue['room']), $needle) ||
                str_contains(mb_strtolower($venue['manager']), $needle);

            $deptOk = $this->department === '' || $venue['department'] === $this->department;

            $capOk = true;
            if ($this->capMin !== null) $capOk = $capOk && ($venue['capacity'] >= $this->capMin);
            if ($this->capMax !== null) $capOk = $capOk && ($venue['capacity'] <= $this->capMax);

            return $hit && $deptOk && $capOk;
        })->values();
    }

    /**
     * Paginate the filtered collection of venues.
     */
    protected function paginated(): LengthAwarePaginator
    {
        $data = $this->filtered();
        // Apply sorting only after user clicks a sort header
        if ($this->sortField !== '') {
            // Sort using natural, case-insensitive order by the active field
            $options = SORT_NATURAL | SORT_FLAG_CASE;
            $data = $data->sortBy(fn($row) => $row[$this->sortField] ?? '', $options, $this->sortDirection === 'desc')->values();
        }
        $items = $data->slice(($this->page - 1) * $this->pageSize, $this->pageSize)->values();
        return new LengthAwarePaginator($items, $data->count(), $this->pageSize, $this->page, [
            'path' => request()->url(),
            'query' => request()->query()
        ]);
    }

    /**
     * Resets the edit form properties to their default values.
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
}
