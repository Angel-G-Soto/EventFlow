<?php

namespace App\Livewire\Admin;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Livewire\Concerns\TableSelection;
use App\Livewire\Concerns\WithPaginationClamping;

#[Layout('layouts.app')]
class VenuesIndex extends Component
{
    use TableSelection, WithPaginationClamping;

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
            'features' => ['Allow Teaching'],
            'availability' => 'Evenings only'
        ],
    ];

    /**
     * Returns a collection of all venues that are not deleted.
     *
     * This function takes into account both soft and hard deleted venues,
     * and also applies any edits that have been made to the venues.
     *
     * @return Collection
     */
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

    /**
     * Resets the current page to 1 when the search filter is updated.
     *
     * Also clears all current selections when the search filter is updated.
     */
    public function updatedSearch()
    {
        $this->page = 1;
        $this->selected = [];
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
     * Opens the edit venue modal with the venue's data populated from the database.
     *
     * This function is called when the user clicks the "Edit" button on a venue's row.
     *
     * @param int $id The ID of the venue to edit.
     */
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

    /**
     * Validation rules for the venue modal.
     *
     * The rules are as follows:
     *
     * - vName: required, string, max 150 characters
     * - vRoom: required, string, max 50 characters
     * - vDepartment: required, string, max 120 characters
     * - vCapacity: required, integer, min 0
     * - vManager: nullable, string, max 120 characters
     * - vStatus: required, in:Active,Inactive
     * - vFeatures: array
     * - vNotes: nullable, string, max 2000 characters
     * - justification: required, string, min 3 characters
     * - blackouts.*.from: required, date
     * - blackouts.*.to: required, date, after_or_equal:blackouts.*.from
     * - blackouts.*.reason: nullable, string, max 300 characters
     *
     * @return array
     */
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
        $this->actionType = 'save';
        $this->dispatch('bs:open', id: 'venueJustify');
    }

    /**
     * Confirms the save action and updates the session with the new/edited venue data.
     * If the venue is being edited, it validates the justification length and then updates the edited_venues session.
     * If the venue is being created, it updates the new_venues session.
     * Finally, it dispatches events to close the justification modal, edit venue modal, and show a toast message with a success message.
     */
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

    /**
     * Confirms the deletion of a venue.
     *
     * This function will validate the justification entered by the user, and then delete the venue with the given ID.
     * After deletion, it clamps the current page to prevent the page from becoming out of bounds.
     * Finally, it shows a toast message indicating whether the venue was permanently deleted or just deleted.
     */
    public function confirmDelete(): void
    {
        if ($this->editId) {
            $this->validateJustification();
            session()->push($this->deleteType === 'hard' ? 'hard_deleted_venue_ids' : 'soft_deleted_venue_ids', $this->editId);
            unset($this->selected[$this->editId]);
        }
        $this->clampPageAfterMutation();
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

    /**
     * Confirms the bulk deletion of venues.
     *
     * This function will validate the justification entered by the user, and then delete the venues with the given IDs.
     * After deletion, it clamps the current page to prevent the page from becoming out of bounds.
     * Finally, it shows a toast message indicating whether the venues were permanently deleted or just deleted.
     */
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
        $this->clampPageAfterMutation();
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

    /**
     * Adds a new blackout to the list.
     *
     * This function will add an empty blackout to the list, which can then be filled in
     * by the user. The new blackout will be added to the end of the list.
     *
     * @return void
     */
    public function addBlackout(): void
    {
        $this->blackouts[] = ['from' => '', 'to' => '', 'reason' => ''];
    }

    /**
     * Removes a blackout from the list.
     *
     * This function will remove the blackout at the given index from the list.
     * After removal, it will re-index the list to maintain contiguous keys.
     *
     * @param int $i The index of the blackout to remove
     */
    public function removeBlackout(int $i): void
    {
        unset($this->blackouts[$i]);
        $this->blackouts = array_values($this->blackouts);
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
            'vNotes',
            'blackouts'
        ]);
        $this->vCapacity = 0;
        $this->vStatus   = 'Active';
        $this->vFeatures = [];
        $this->blackouts = [];
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
        $paginator = $this->ensurePageInBounds($paginator);
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
