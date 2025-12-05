<?php

namespace App\Livewire\Admin;

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Livewire\Traits\EventFilters;
use App\Livewire\Traits\EventEditState;
use App\Livewire\Traits\HasJustification;
use App\Services\CategoryService;
use App\Services\EventService;
use App\Services\VenueService;
// Note: Admin views must use services only (no direct models). Venue lookups are avoided here.
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

/**
 * Admin oversight view for event requests.
 *
 * Enables privileged users to search/filter requests, inspect full details
 * (including attached documents), and perform manual overrides (save, cancel,
 * advance, approve, deny). All persistence is delegated to EventService to
 * keep business rules and audit logging out of the UI layer.
 */
#[Layout('layouts.app')]
class EventsIndex extends Component
{
    // For category search in modal
    /** @var string Search term used to filter categories in the modal. */
    public string $categorySearch = '';
    // For displaying selected category labels
    /** @var array<int,string> Map of selected category id => label for display. */
    public array $selectedCategoryLabels = [];
    // For filtered categories in modal
    /** @var array<int,array{id:int,name:string,description:?string}> Filtered category options shown in the modal. */
    public array $filteredCategories = [];
    // Traits / shared state
    use EventFilters, EventEditState, HasJustification;

    /**
     * Pool of category names generated from CategoryFactory (no DB).
     *
     * @var array<int,string>
     */
    public array $categoryPool = [];

    /**
     * Status list from DB via service (distinct), no hardcoding.
     *
     * @return array<int,string>
     */
    public function getStatusesProperty(): array
    {
        try {
            return app(EventService::class)
                ->getFlowStatuses(true)
                ->values()
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Initialize the component loading the category pool from the database.
     *
     * @return void
     */
    // Lifecycle
    public function mount(): void
    {
        $this->refreshCategoryPool();
        $this->filteredCategories = $this->getFilteredCategories();
    }
    /**
     * Refresh filtered category options whenever the search term changes.
     *
     * Keeps the modal list in sync with the latest DB state without requiring
     * a full page reload.
     *
     * @return void
     */
    public function updatedCategorySearch(): void
    {
        $this->filteredCategories = $this->getFilteredCategories();
    }

    /**
     * Rebuild the selected category label map when IDs change in edit/view state.
     *
     * @return void
     */
    public function updatedECategoryIds(): void
    {
        $this->updateSelectedCategoryLabels();
    }

    /**
     * Clear all selected categories from the edit state.
     *
     * @return void
     */
    public function clearCategories(): void
    {
        $this->eCategoryIds = [];
        $this->updateSelectedCategoryLabels();
    }

    /**
     * Resolve and filter categories from the database for the modal multiselect.
     *
     * @return array<int,array{id:int,name:string,description:?string}>
     */
    protected function getFilteredCategories(): array
    {
        try {
            $categories = app(CategoryService::class)->getAllCategories();
        } catch (\Throwable $e) {
            return [];
        }
        $search = trim($this->categorySearch);
        if ($search === '') {
            return $categories->map(fn($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'description' => $cat->description ?? null,
            ])->all();
        }
        return $categories->filter(function ($cat) use ($search) {
            return str_contains(strtolower($cat->name), strtolower($search));
        })->map(fn($cat) => [
            'id' => $cat->id,
            'name' => $cat->name,
            'description' => $cat->description ?? null,
        ])->all();
    }

    /**
     * Build a map of category id => name for currently selected IDs.
     * This keeps labels stable in the UI even if categories change in the DB.
     *
     * @return void
     */
    protected function updateSelectedCategoryLabels(): void
    {
        try {
            $categories = app(CategoryService::class)->getAllCategories();
            $this->selectedCategoryLabels = collect($categories)
                ->whereIn('id', $this->eCategoryIds)
                ->pluck('name', 'id')
                ->all();
        } catch (\Throwable $e) {
            $this->selectedCategoryLabels = [];
        }
    }

    /**
     * Remove a single category from the selected ID list and refresh labels.
     *
     * @param int|string $id
     *
     * @return void
     */
    public function removeCategory($id): void
    {
        $this->eCategoryIds = array_values(array_diff($this->eCategoryIds, [(int)$id]));
        $this->updateSelectedCategoryLabels();
    }

    // Filters: search update reaction
    /**
     * Resets the current page to 1 when the search filter is updated.
     *
     * This function will be called whenever the search filter is updated,
     * and will reset the current page to 1.
     *
     * @return void
     */
    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    /**
     * Explicit applySearch handler so deferred search inputs submit via button/enter.
     *
     * @return void
     */
    public function applySearch(): void
    {
        $this->page = 1;
    }

    /**
     * Apply the selected date range (from/to) and reset pagination.
     *
     * @return void
     */
    public function applyDateRange(): void
    {
        $this->validate([
            'from' => ['nullable', 'string', 'date_format:Y-m-d'],
            'to' => [
                'nullable',
                'string',
                'date_format:Y-m-d',
                'after_or_equal:from',
            ],
        ]);
        $this->page = 1;
    }

    /**
     * Keep venue label ($eVenue) in sync when the selected venue id changes in the edit modal.
     *
     * @param mixed $value New venue identifier from Livewire binding.
     *
     * @return void
     */
    public function updatedEVenueId($value): void
    {
        try {
            $opts = app(VenueService::class)->listVenuesForFilter();
            $match = collect($opts)->firstWhere('id', (int)$value);
            if (is_array($match) && isset($match['label'])) {
                $this->eVenue = (string)$match['label'];
            }
        } catch (\Throwable) { /* noop */ }
    }

    // Filters: clear/reset

    /**
     * Clears all filters and resets the current page to 1.
     *
     * This function is called when the user clicks the "Clear" button on the filter form.
     *
     * @return void
     */
    public function clearFilters(): void
    {
        $this->resetErrorBag(['from', 'to']);
        $this->resetValidation(['from', 'to']);
        $this->search = '';
        $this->status = '';
        $this->venue = null;
        $this->from = null;
        $this->to = null;
        $this->requestor = '';
        $this->category = '';
        $this->page = 1;
    }

    // Edit/View workflows
    /**
     * Opens the edit event modal with the given ID.
     * If the event is not found, the function does nothing.
     * It sets the currently edited event ID and the values of the event to be edited, and then opens the edit event modal.
     * @param int $id The ID of the event to edit
     *
     * @return void
     */
    public function openEdit(int $id): void
    {
        $this->authorize('perform-override');

        $this->resetErrorBag();
        $this->resetValidation();

        $request = app(EventService::class)->getEventRowById($id);
        if (!$request) return;
        $this->fillEditFromRequest($request);
        $this->dispatch('bs:open', id: 'oversightEdit');
    }

    /**
     * Open a read-only view modal for a specific request, pre-filling the view state.
     *
     * @param int $id The ID of the request to display.
     *
     * @return void
     */
    public function openView(int $id): void
    {
        $this->authorize('access-dashboard');

        $this->resetErrorBag();
        $this->resetValidation();

        $request = app(EventService::class)->getEventRowById($id);
        if (!$request) return;
        $this->fillEditFromRequest($request);
        $this->loadViewDocuments((int)($request['id'] ?? 0));
        $this->dispatch('bs:open', id: 'oversightView');
    }

    /**
     * Fill edit/view state from a normalized request row.
     * Keeps openEdit/openView DRY and simple.
     *
     * @param array<string,mixed> $request
     *
     * @return void
     */
    protected function fillEditFromRequest(array $request): void
    {
        // $this->authorize('perform-override');

        $this->editId = (int)($request['id'] ?? 0);
        $this->eTitle = (string)($request['title'] ?? '');
        $this->ePurpose = (string)($request['description'] ?? ($request['purpose'] ?? ''));
        $this->eVenue = (string)($request['venue'] ?? '');
        $this->eVenueId = (int)($request['venue_id'] ?? 0);
        $this->eFrom = (string)($request['from_edit'] ?? $request['from'] ?? '');
        $this->eTo   = (string)($request['to_edit'] ?? $request['to'] ?? '');
        $this->eAttendees = $request['attendees'] ?? 0;
        $this->eCategory  = $request['category'] ?? '';
        // If categories are available as array/ids, set eCategoryIds
        if (!empty($request['category_ids']) && is_array($request['category_ids'])) {
            $this->eCategoryIds = $request['category_ids'];
        } elseif (!empty($request['category_id'])) {
            $this->eCategoryIds = [(int)$request['category_id']];
        } else {
            $this->eCategoryIds = [];
        }
        $this->updateSelectedCategoryLabels();
        $this->eStatus    = (string)($request['status'] ?? '');
        // Policies
        $this->eHandlesFood = (bool)($request['handles_food'] ?? false);
        $this->eUseInstitutionalFunds = (bool)($request['use_institutional_funds'] ?? false);
        $this->eExternalGuest = (bool)($request['external_guest'] ?? false);

        // Organization and student info
        $this->eOrganization   = $request['organization'] ?? ($request['organization_nexo_name'] ?? '');
        $this->eAdvisorName    = $request['organization_advisor_name']  ?? '';
        $this->eAdvisorEmail   = $request['organization_advisor_email'] ?? '';
        $this->eAdvisorPhone   = $request['organization_advisor_phone'] ?? '';
        // Map to DB-backed creator fields
        $this->eStudentNumber  = $request['creator_institutional_number'] ?? '';
        $this->eStudentPhone   = $request['creator_phone_number'] ?? '';
    }

    // Persist edits / session writes
    /**
     * Opens the justification modal for saving the event.
     *
     * This function sets the actionType to 'save' and then opens the justification modal.
     *
     * @return void
     */
    public function save(): void
    {
        $this->authorize('perform-override');

        $this->resetErrorBag();
        $this->resetValidation();

        // Validate fields before asking for justification
        $this->validate($this->eventFieldRules());
        if (!$this->datesInOrder()) {
            $this->addError('eTo', 'End date/time must be after the start date/time.');
            return;
        }
        $this->actionType = 'save';
        $this->dispatch('bs:open', id: 'oversightJustify');
    }

    /**
     * Validates the justification and saves the event. If the event is being edited, it dispatches
     * events to close the justification and edit event modals. If the event is being created, it
     * dispatches an event to jump to the last page after creation.
     *
     * @return void
     */
    public function confirmSave(): void
    {
        $this->authorize('perform-override');

        $this->validateJustification();
        $isEditing = !empty($this->editId);

        try {
            if ($isEditing) {
                $svc = app(EventService::class);
                $event = $this->getEventFromServiceById((int)$this->editId);
                if (!$event) {
                    $this->addError('justification', 'Event no longer exists or cannot be loaded.');
                    return;
                }

                $venueId = (int)($this->eVenueId ?: ($event->venue_id ?? 0));
                $payload = [
                    'venue_id'     => $venueId,
                    'title'        => (string)$this->eTitle,
                    'description'  => (string)$this->ePurpose,
                    'start_time'   => str_replace('T', ' ', (string)$this->eFrom),
                    'end_time'     => str_replace('T', ' ', (string)$this->eTo),
                    'status'       => (string)($event->status ?? 'approved'),
                    'guest_size'   => (int)$this->eAttendees,
                    'organization_name' => (string)$this->eOrganization,
                    'organization_advisor_name'  => (string)$this->eAdvisorName,
                    'organization_advisor_email' => (string)$this->eAdvisorEmail,
                    'organization_advisor_phone' => (string)$this->eAdvisorPhone,
                    'creator_institutional_number' => (string)$this->eStudentNumber,
                    'creator_phone_number'          => (string)$this->eStudentPhone,
                    'handles_food' => (bool)$this->eHandlesFood,
                    'use_institutional_funds' => (bool)$this->eUseInstitutionalFunds,
                    'external_guest' => (bool)$this->eExternalGuest,
                ];
                $saved = $svc->performManualOverride($event, $payload, Auth::user(), (string)($this->justification ?? ''), 'save');
                if (!empty($this->eCategoryIds)) {
                    try { $svc->syncEventCategoriesByIds($saved, $this->eCategoryIds); } catch (\Throwable) { /* noop */ }
                }
            }
        } catch (\Throwable $e) {
            $this->addError('justification', 'Unable to save event.');
            return;
        }

        $this->dispatch('bs:close', id: 'oversightJustify');
        $this->dispatch('bs:close', id: 'oversightEdit');
        $this->dispatch('toast', message: 'Event saved');
        $this->reset(['actionType', 'justification']);
    }

    // Delete workflows
    /**
     * Stub for delete action; deletion is disabled in this view.
     *
     * @param int $id Event identifier (ignored).
     *
     * @return void
     */
    // Delete flows disabled for admin oversight; keep stub for compatibility
    public function delete(int $id): void
    {
        $this->addError('justification', 'Delete is disabled for this view.');
    }

    /**
     * Stub for delete confirmation; deletion is disabled in this view.
     *
     * @return void
     */
    public function proceedDelete(): void
    {
        $this->addError('justification', 'Delete is disabled for this view.');
    }

    /**
     * Stub for delete confirmation with justification; deletion is disabled in this view.
     *
     * @return void
     */
    public function confirmDelete(): void
    {
        $this->addError('justification', 'Delete is disabled for this view.');
    }

    /**
     * Opens the justification modal with the action type set to 'advance'.
     *
     * This function is used to advance an event request that has been flagged for oversight.
     * It will open the justification modal with the action type set to 'advance', allowing the user to enter a justification for the advancement.
     */
    public function advance(): void
    {
        $this->authorize('perform-override');

        $this->resetErrorBag();
        $this->resetValidation();

        $this->actionType = 'advance';
        $this->advanceTo = '';
        // First show confirmation; justification will be collected after confirm
        $this->dispatch('bs:open', id: 'oversightAdvance');
    }

    // Approve/deny flows disabled: only advance/save/delete are allowed
    // public function confirmAction(): void
    // {
    //     $this->authorize('perform-override');

    //     // Apply status change via service (no hardcoded statuses)
    //     $toastMsg = null;
    //     if ($this->editId && in_array($this->actionType, ['approve', 'deny'], true)) {
    //         try {
    //             $svc = app(EventService::class);
    //             $event = $this->getEventFromServiceById((int) $this->editId);
    //             if (! $event) {
    //                 $this->addError('justification', 'Unable to load event for action.');
    //                 return;
    //             }

                // if ($this->actionType === 'approve') {
                //     $svc->approveEvent($event, Auth::user());
                //     $toastMsg = 'Event approved';
                // } elseif ($this->actionType === 'deny') {
                //     $this->validateJustification();
                //     $svc->denyEvent((string) ($this->justification ?? ''), $event, Auth::user());
                //     $toastMsg = 'Event denied';
                // }
    //         } catch (\Throwable $e) {
    //             $this->addError('justification', 'Unable to ' . $this->actionType . ' event.');
    //             return;
    //         }
    //     }

    //     $this->dispatch('bs:close', id: 'oversightJustify');
    //     $this->dispatch('bs:close', id: 'oversightEdit');
    //     $this->dispatch('toast', message: $toastMsg ?? (ucfirst($this->actionType) . ' completed'));
    //     $this->reset('actionType', 'justification');
    // }

    /**
     * Unified justification submit handler routing to the appropriate action.
     *
     * Central entry point for the justification modal; routes to delete,
     * advance, approve/deny, or save branches while enforcing justification
     * when required by policy.
     *
     * @return void
     */
    public function confirmJustify(): void
    {
        $this->authorize('perform-override');

        $type = $this->actionType ?? '';
        // Delete flows are disabled.
        if ($type === 'advance') {
            $this->validateJustification();
            try {
                $svc = app(EventService::class);
                $event = $this->getEventFromServiceById((int)$this->editId);
                if ($event) {
                    $svc->advanceEvent($event, Auth::user(), (string)($this->justification ?? ''));
                }
            } catch (\Throwable) { /* ignore */ }
            $this->dispatch('bs:close', id: 'oversightJustify');
            $this->dispatch('bs:close', id: 'oversightEdit');
            $this->dispatch('toast', message: 'Advance completed');
            $this->reset('actionType', 'justification');
            return;
        }
        // Approve/Deny flows are disabled; fall through to save.
        // 'reroute' removed
        $this->confirmSave();
    }

    /**
     * Confirm advance with a target. Updates status and stores the target, with a clear toast.
     *
     * @return void
     */
    public function confirmAdvance(): void
    {
        $this->authorize('perform-override');

        // After confirm, move to justification modal
        $this->dispatch('bs:close', id: 'oversightAdvance');
        $this->dispatch('bs:open', id: 'oversightJustify');
    }

        // Reroute logic removed
    
    /**
     * Navigates to a given page number, clamping within valid bounds.
     *
     * @param int $target Desired page number.
     *
     * @return void
     */
    public function goToPage(int $target): void
    {
        $this->page = max(1, $target);
    }

    /**
     * Renders the events index page.
     *
     * The page is re-paginated if the current page number is not the same as the paginator's current page number.
     * The visible IDs are obtained from the paginator.
     * The view is rendered with the paginator, visible IDs, statuses, and venues.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): \Illuminate\Contracts\View\View
    {
        $this->authorize('access-dashboard');

        // Keep category options aligned with DB state
        $categories = !empty($this->categoryPool) ? $this->categoryPool : $this->refreshCategoryPool();

        $paginator = $this->eventsPaginator();
        $visibleIds = $paginator->pluck('id')->all();
        // Venue options for filter (disambiguate duplicate names)
        try {
            $venues = app(VenueService::class)->listVenuesForFilter()->values()->all();
        } catch (\Throwable $e) {
            $venues = [];
        }
        return view('livewire.admin.events-index', [
            'rows' => $paginator,
            'visibleIds' => $visibleIds,
            'statuses' => $this->statuses,
            'categories' => $categories,
            'venues' => $venues,
        ]);
    }

    /**
     * Build a paginator for events using the current filter set.
     *
     * @return LengthAwarePaginator
     */
    protected function eventsPaginator(): LengthAwarePaginator
    {
        $svc = app(EventService::class);
        $venueValue = $this->venue;
        $venueId = null;
        $venueName = null;
        if (is_int($venueValue) || (is_string($venueValue) && ctype_digit($venueValue))) {
            $venueId = (int)$venueValue;
        } elseif (is_string($venueValue) && trim($venueValue) !== '') {
            $venueName = trim($venueValue);
        }

        $paginator = $svc->paginateEventRows(
            [
                'search' => $this->search,
                'status' => $this->status,
                'venue_id' => $venueId,
                'venue_name' => $venueName,
                'category' => $this->category,
                'from' => $this->from,
                'to' => $this->to,
            ],
            $this->pageSize,
            $this->page
        );

        $last = max(1, (int)$paginator->lastPage());
        if ($this->page > $last) {
            $this->page = $last;
            if ((int)$paginator->currentPage() !== $last) {
                $paginator = $svc->paginateEventRows(
                    [
                        'search' => $this->search,
                        'status' => $this->status,
                        'venue_id' => $venueId,
                        'venue_name' => $venueName,
                        'category' => $this->category,
                        'from' => $this->from,
                        'to' => $this->to,
                    ],
                    $this->pageSize,
                    $this->page
                );
            }
        }

        return $paginator;
    }

    // Presentation helpers
    /**
     * Normalize status for UI display (label + variant).
     * Accepts either a canonical status code (preferred) or a raw status string.
     *
     * @param string $value Status code or label to normalize.
     *
     * @return array{label:string,variant:string}
     */
    public function statusIndicatorData(string $value): array
    {
        $normalized = mb_strtolower(trim($value));

        // Canonical code map (preferred path)
        $map = [
            'pending_advisor'       => ['label' => 'Awaiting Advisor Approval',       'variant' => 'warning'],
            'pending_venue_manager' => ['label' => 'Awaiting Venue Manager Approval', 'variant' => 'warning'],
            'pending_dsca'          => ['label' => 'Awaiting DSCA Approval',          'variant' => 'warning'],
            'pending_deanship'      => ['label' => 'Awaiting Deanship Approval',      'variant' => 'warning'],
            'approved'              => ['label' => 'Approved',                        'variant' => 'success'],
            'rejected'                => ['label' => 'Rejected',                          'variant' => 'danger'],
            'cancelled'             => ['label' => 'Cancelled',                       'variant' => 'danger'],
            'withdrawn'             => ['label' => 'Withdrawn',                       'variant' => 'danger'],
            'completed'             => ['label' => 'Completed',                       'variant' => 'success'],
            'draft'                 => ['label' => 'Draft',                           'variant' => 'warning'],
        ];

        if (isset($map[$normalized])) {
            return $map[$normalized];
        }

        // Fallback for legacy/free-form status strings
        $label = 'Unknown';
        if ($normalized !== '') {
            if (str_contains($normalized, 'advisor')) {
                $label = 'Awaiting Advisor Approval';
            } elseif (str_contains($normalized, 'venue manager')) {
                $label = 'Awaiting Venue Manager Approval';
            } elseif (str_contains($normalized, 'dsca')) {
                $label = 'Awaiting DSCA Approval';
            } else {
                $label = ucfirst($value);
            }
        }

        $variant = match (true) {
            str_contains($normalized, 'cancel'),
            str_contains($normalized, 'withdraw'),
            str_contains($normalized, 'reject'),
            str_contains($normalized, 'deny') => 'danger',
            str_contains($normalized, 'approve'),
            str_contains($normalized, 'complete') => 'success',
            default => 'warning',
        };

        return ['label' => $label, 'variant' => $variant];
    }

    // Private/Protected Helper Methods

    /**
     * Validation rules for all editable event fields.
     *
     * @return array<string, array<int,string|\Illuminate\Validation\Rule>>
     */
    protected function eventFieldRules(): array
    {
        if (empty($this->categoryPool)) {
            $this->refreshCategoryPool();
        }
        $today = now()->format('Y-m-d H:i');
        return [
            'eTitle' => ['required', 'string', 'min:3', 'max:120', 'not_regex:/^\s*$/'],
            'ePurpose' => ['required', 'string', 'min:3', 'max:2000', 'not_regex:/^\s*$/'],
            'eVenueId' => ['required', 'integer', 'min:1'],
            'eFrom' => [
                'required', 'string',
                function ($attribute, $value, $fail) use ($today) {
                    if (strtotime($value) < strtotime($today)) {
                        $fail('Start date/time cannot be in the past.');
                    }
                }
            ],
            'eTo' => [
                'required', 'string',
                function ($attribute, $value, $fail) use ($today) {
                    if (strtotime($value) < strtotime($today)) {
                        $fail('End date/time cannot be in the past.');
                    }
                }
            ],
            'eAttendees' => [
                'required', 'integer', 'min:1', 'max:50000',
                function ($attribute, $value, $fail) {
                    if ($value > 10000) {
                        $fail('Attendee count seems unusually high.');
                    }
                }
            ],
            'eCategoryIds' => [
                'required', 'array', 'min:1',
                function ($attribute, $value, $fail) {
                    if (empty($this->categoryPool)) {
                        $fail('No categories are available. Please contact an administrator.');
                    }
                }
            ],
            'eHandlesFood' => ['boolean'],
            'eUseInstitutionalFunds' => ['boolean'],
            'eExternalGuest' => ['boolean'],
            'eOrganization' => ['required', 'string', 'min:2', 'max:120', 'not_regex:/^\s*$/'],
            'eAdvisorName' => ['nullable', 'string', 'max:120'],
            'eAdvisorEmail' => ['nullable', 'email', 'max:120'],
            'eAdvisorPhone' => ['nullable', 'string', 'max:30'],
            'eStudentNumber' => ['nullable', 'string', 'max:50'],
            'eStudentPhone' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Ensure eTo is after eFrom; supports both "Y-m-d H:i" and "Y-m-dTH:i".
     *
     * @return bool True when the end time is after or equal to the start.
     */
    protected function datesInOrder(): bool
    {
        $from = str_replace('T', ' ', (string)$this->eFrom);
        $to   = str_replace('T', ' ', (string)$this->eTo);
        $sf = strtotime($from);
        $st = strtotime($to);
        if ($sf === false || $st === false) return false;
        return $st >= $sf;
    }

    /**
     * Returns an array of validation rules for the justification field.
     *
     * @return array<string, array<int,\Illuminate\Validation\Rule|string>>
     */
    protected function rules(): array
    {
        return [
            'justification' => $this->justificationRules(true),
        ];
    }

    /**
     * Validates only the justification field.
     *
     * @return void
     */
    protected function validateJustification(): void
    {
        $this->validateJustificationField(true);
    }

    /**
     * Fetch events via EventService without relying on undefined methods.
     *
     * @param int $id Event identifier.
     *
     * @return mixed|null Event model or null when not found.
     */
    protected function getEventFromServiceById(int $id)
    {
        if (!isset($id) || $id <= 0) return null;
        try {
            return app(EventService::class)->findEventById($id);
        } catch (\Throwable $e) {
            return null;
        }
    }


    /**
     * Load and normalize attached documents for the read-only view modal.
     *
     * Documents are mapped to a lightweight array (id, name, label, url)
     * via DocumentService so the Blade template stays decoupled from
     * storage implementation details and concrete model types.
     *
     * @param int $eventId Event identifier for which documents are loaded.
     *
     * @return void
     */
    protected function loadViewDocuments(int $eventId): void
    {
        $this->eDocuments = [];
        $previousStatus = $this->eStatus;

        try {
            $event = app(EventService::class)->findEventById($eventId);
            if (!$event) {
                $this->eStatus = $previousStatus;
                return;
            }

            $this->eStatus = (string)($event->status ?? $previousStatus);
            $documents = collect($event->documents ?? []);
            $docService = app(\App\Services\DocumentService::class);

            $this->eDocuments = $documents
                ->map(function ($doc) use ($docService) {
                    $id   = (int)($doc->id ?? 0);
                    $name = (string)($doc->name ?? '');
                    $path = (string)($doc->file_path ?? '');
                    $label = $name !== '' ? $name : basename($path ?: ('document-' . ($doc->id ?? '')));
                    $url   = $id > 0 ? $docService->getDocumentViewUrl($doc) : null;

                    return compact('id', 'name', 'label', 'url');
                })
                ->filter(function ($doc) {
                    return ($doc['id'] ?? 0) > 0
                        || trim((string)($doc['name'] ?? '')) !== ''
                        || trim((string)($doc['label'] ?? '')) !== '';
                })
                ->values()
                ->all();
        } catch (\Throwable $exception) {
            $this->eStatus = $previousStatus;
            $this->eDocuments = [];
        }
    }

    /**
     * Refresh categories from the database for oversight UI and validation.
     *
     * @return array<int,string>
     */
    protected function refreshCategoryPool(): array
    {
        try {
            $categories = app(CategoryService::class)->getAllCategories();
            $this->categoryPool = $categories->pluck('name')->sort()->values()->all();
        } catch (\Throwable $e) {
            $this->categoryPool = [];
        }

        return $this->categoryPool;
    }

}
