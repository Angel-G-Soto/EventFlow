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

#[Layout('layouts.app')]
class EventsIndex extends Component
{
    // For category search in modal
    public string $categorySearch = '';
    // For displaying selected category labels
    public array $selectedCategoryLabels = [];
    // For filtered categories in modal
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
     */
    // Lifecycle
    public function mount(): void
    {
        $this->refreshCategoryPool();
        $this->filteredCategories = $this->getFilteredCategories();
    }
    public function updatedCategorySearch()
    {
        $this->filteredCategories = $this->getFilteredCategories();
    }

    public function updatedECategoryIds()
    {
        $this->updateSelectedCategoryLabels();
    }

    public function clearCategories()
    {
        $this->eCategoryIds = [];
        $this->updateSelectedCategoryLabels();
    }

    protected function getFilteredCategories(): array
    {
        $categories = app(CategoryService::class)->getAllCategories();
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

    protected function updateSelectedCategoryLabels(): void
    {
        $categories = app(CategoryService::class)->getAllCategories();
        $this->selectedCategoryLabels = collect($categories)
            ->whereIn('id', $this->eCategoryIds)
            ->pluck('name', 'id')
            ->all();
    }

    public function removeCategory($id)
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
     */
    public function updatedSearch()
    {
        $this->page = 1;
    }

    /**
     * Explicit applySearch handler so deferred search inputs submit via button/enter.
     */
    public function applySearch(): void
    {
        $this->page = 1;
    }

    /**
     * Apply the selected date range (from/to) and reset pagination.
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
     */
    protected function fillEditFromRequest(array $request): void
    {
        // $this->authorize('perform-override');

        $this->editId = $request['id'];
        $this->eTitle = $request['title'];
        $this->ePurpose = $request['description'] ?? ($request['purpose'] ?? '');
        $this->eVenue = $request['venue'];
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
     */
    public function confirmSave(): void
    {
        $this->authorize('perform-override');

        $this->validateJustification();
        $isEditing = !empty($this->editId);
        $this->dispatch('bs:close', id: 'oversightJustify');
        $this->dispatch('bs:close', id: 'oversightEdit');
        // Persist edits to DB when applicable
        if (!empty($this->editId)) {
            try {
                $svc = app(EventService::class);
                $event = $this->getEventFromServiceById((int)$this->editId);
                if ($event) {
                    $venueId = (int)($this->eVenueId ?: ($event->venue_id ?? 0));
                    // Build full payload for performManualOverride
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
                    // Route edits through performManualOverride (service-only, no model access here)
                    $saved = $svc->performManualOverride($event, $payload, Auth::user(), (string)($this->justification ?? ''), 'save');
                    // Sync categories by IDs (multiselect)
                    if (!empty($this->eCategoryIds)) {
                        try { $svc->syncEventCategoriesByIds($saved, $this->eCategoryIds); } catch (\Throwable) { /* noop */ }
                    }
                }
            } catch (\Throwable $e) {
                // swallow update errors
            }
        }

        $this->dispatch('toast', message: 'Event saved');
        $this->reset(['actionType', 'justification']);
    }

    // Delete workflows
    /**
     * Opens the justification modal for deleting an event.
     *
     * This function sets the currently edited event ID and the actionType to 'delete', and then opens the justification modal.
     * @param int $id The ID of the event to delete
     */
    public function delete(int $id): void
    {
        $this->authorize('perform-override');

        $this->resetErrorBag();
        $this->resetValidation();

        $this->editId = isset($id) && is_int($id) ? $id : null;
        $this->actionType = 'delete';
        $this->dispatch('bs:open', id: 'oversightConfirm');
    }

    /**
     * Proceeds from the delete confirmation to the justification modal.
     */
    public function proceedDelete(): void
    {
        $this->authorize('perform-override');

        $this->resetErrorBag();
        $this->resetValidation();

        $this->dispatch('bs:close', id: 'oversightConfirm');
        $this->dispatch('bs:open', id: 'oversightJustify');
    }

    /**
     * Confirms the deletion of an event.
     *
     * This function will validate the justification entered by the user, and then delete the event with the given ID.
     * After deletion, it clamps the current page to prevent the page from becoming out of bounds.
     * Finally, it shows a toast message indicating the event was deleted.
     */
    public function confirmDelete(): void
    {
        $this->authorize('perform-override');

        if ($this->editId) {
            $this->validateJustification();
            try {
                $svc = app(EventService::class);
                $event = $this->getEventFromServiceById((int)$this->editId);
                if ($event) {
                    // Delegate cancellation to service (no status hardcoding in UI)
                    $svc->cancelEvent($event, Auth::user(), (string)($this->justification ?? ''));
                }
            } catch (\Throwable $e) {
                // Surface a validation error instead of falling back
                $this->addError('justification', 'Unable to delete event.');
                return;
            }
        }
        $this->dispatch('bs:close', id: 'oversightJustify');
        $this->dispatch('toast', message: 'Event deleted');
        $this->reset(['editId', 'actionType', 'justification']);
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

    // Confirm action flows
    /**
     * Closes the justification and edit modals and displays a toast message indicating that the action has been completed.
     *
     * This function is called after the user has submitted the justification form.
     * It will close the justification and edit modals, and then display a toast message indicating that the action has been completed.
     * The toast message will be in the format "Action completed", where "Action" is the value of the `actionType` property.
     */
    public function confirmAction(): void
    {
        $this->authorize('perform-override');

        // Apply status change via service (no hardcoded statuses)
        $toastMsg = null;
        if ($this->editId && in_array($this->actionType, ['approve', 'deny'], true)) {
            try {
                $svc = app(EventService::class);
                $event = $this->getEventFromServiceById((int) $this->editId);
                if (! $event) {
                    $this->addError('justification', 'Unable to load event for action.');
                    return;
                }

                if ($this->actionType === 'approve') {
                    $svc->approveEvent($event, Auth::user());
                    $toastMsg = 'Event approved';
                } elseif ($this->actionType === 'deny') {
                    $this->validateJustification();
                    $svc->denyEvent((string) ($this->justification ?? ''), $event, Auth::user());
                    $toastMsg = 'Event denied';
                }
            } catch (\Throwable $e) {
                $this->addError('justification', 'Unable to ' . $this->actionType . ' event.');
                return;
            }
        }

        $this->dispatch('bs:close', id: 'oversightJustify');
        $this->dispatch('bs:close', id: 'oversightEdit');
        $this->dispatch('toast', message: $toastMsg ?? (ucfirst($this->actionType) . ' completed'));
        $this->reset('actionType', 'justification');
    }

    /**
     * Unified justification submit handler routing to the appropriate action.
     */
    public function confirmJustify(): void
    {
        $this->authorize('perform-override');

        $type = $this->actionType ?? '';
        if ($type === 'delete') {
            $this->confirmDelete();
            return;
        }
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
        if (in_array($type, ['approve', 'deny'], true)) {
            $this->confirmAction();
            return;
        }
        // 'reroute' removed
        $this->confirmSave();
    }

    /**
     * Confirm advance with a target. Updates status and stores the target, with a clear toast.
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
            'denied'                => ['label' => 'Denied',                          'variant' => 'danger'],
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
     */
    protected function rules(): array
    {
        return [
            'justification' => $this->justificationRules(true),
        ];
    }

    /**
     * Validates only the justification field.
     */
    protected function validateJustification(): void
    {
        $this->validateJustificationField(true);
    }

    /**
     * Fetch events via EventService without relying on undefined methods.
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
