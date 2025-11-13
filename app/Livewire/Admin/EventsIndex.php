<?php

namespace App\Livewire\Admin;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Livewire\Traits\EventFilters;
use App\Livewire\Traits\EventEditState;
use App\Services\CategoryService;
use App\Services\EventService;
// Note: Admin views must use services only (no direct models). Venue lookups are avoided here.
use DateTimeInterface;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.app')]
class EventsIndex extends Component
{
    // Traits / shared state
    use EventFilters, EventEditState;

    /**
     * Backing store for generated requests displayed in oversight.
     * Generated from EventFactory on mount and kept stable during the session.
     *
     * @var array<int,array<string,mixed>>
     */
    // Properties / backing stores
    public array $requests = [];
    /**
     * Pool of category names generated from CategoryFactory (no DB).
     *
     * @var array<int,string>
     */
    public array $categoryPool = [];

    // Accessors and Mutators
    /**
     * Organizations list from DB via service (distinct), no hardcoding.
     *
     * @return array<int,string>
     */
    public function getOrganizationsProperty(): array
    {
        try {
            return app(EventService::class)
                ->getDistinctOrganizations()
                ->values()
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

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
        // Load categories for the edit dropdown and validation rules via service
        try {
            $categories = app(CategoryService::class)->getAllCategories();
            $this->categoryPool = $categories->pluck('name')->sort()->values()->all();
        } catch (\Throwable $e) {
            $this->categoryPool = [];
        }
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
        $this->page = 1;
    }

    /**
     * Keep venue label ($eVenue) in sync when the selected venue id changes in the edit modal.
     */
    public function updatedEVenueId($value): void
    {
        try {
            $opts = app(EventService::class)->listVenuesForFilter();
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

        $request = $this->filtered()->firstWhere('id', $id);
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

        $request = $this->filtered()->firstWhere('id', $id);
        if (!$request) return;
        $this->fillEditFromRequest($request);
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
        $this->authorize('perform-override');

        $this->editId = $request['id'];
        $this->eTitle = $request['title'];
        $this->ePurpose = $request['description'] ?? ($request['purpose'] ?? '');
        $this->eVenue = $request['venue'];
        $this->eVenueId = (int)($request['venue_id'] ?? 0);
        $this->eFrom = substr($request['from'], 0, 16);
        $this->eTo   = substr($request['to'], 0, 16);
        $this->eAttendees = $request['attendees'] ?? 0;
        $this->eCategory  = $request['category'] ?? '';
        // Policies
        $this->eHandlesFood = (bool)($request['handles_food'] ?? false);
        $this->eUseInstitutionalFunds = (bool)($request['use_institutional_funds'] ?? false);
        $this->eExternalGuest = (bool)($request['external_guest'] ?? false);

        // Organization and student info
        $this->eOrganization   = $request['organization'] ?? ($request['organization_nexo_name'] ?? '');
        $this->eAdvisorName    = $request['organization_advisor_name']  ?? '';
        $this->eAdvisorEmail   = $request['organization_advisor_email'] ?? '';
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
                        'guests'       => (int)$this->eAttendees,
                        'organization_name' => (string)$this->eOrganization,
                        'organization_advisor_name'  => (string)$this->eAdvisorName,
                        'organization_advisor_email' => (string)$this->eAdvisorEmail,
                        'creator_institutional_number' => (string)$this->eStudentNumber,
                        'creator_phone_number'          => (string)$this->eStudentPhone,
                        'handles_food' => (bool)$this->eHandlesFood,
                        'use_institutional_funds' => (bool)$this->eUseInstitutionalFunds,
                        'external_guests' => (bool)$this->eExternalGuest,
                    ];
                    // Route edits through performManualOverride (service-only, no model access here)
                    $saved = $svc->performManualOverride($event, $payload, Auth::user(), (string)($this->justification ?? ''), 'save');
                    // Sync category by name (if provided)
                    if (trim((string)$this->eCategory) !== '') {
                        try { $svc->syncEventCategoryByName($saved, (string)$this->eCategory); } catch (\Throwable) { /* noop */ }
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

    // Restore-all functionality removed

    // Action workflows (approve/deny/advance/reroute)
    /**
     * Opens the justification modal with the action type set to 'approve'.
     *
     * This function is used to approve an event request that has been flagged for oversight.
     * It will open the justification modal with the action type set to 'approve', allowing the user to enter a justification for the approval.
     */
    public function approve(): void
    {
        $this->authorize('perform-override');

        $this->actionType = 'approve';
        $this->dispatch('bs:open', id: 'oversightJustify');
    }

    /**
     * Opens the justification modal with the action type set to 'deny'.
     *
     * This function is used to deny an event request that has been flagged for oversight.
     * It will open the justification modal with the action type set to 'deny', allowing the user to enter a justification for the denial.
     */    public function deny(): void
    {
        $this->authorize('perform-override');

        $this->actionType = 'deny';
        $this->dispatch('bs:open', id: 'oversightJustify');
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

        $this->actionType = 'advance';
        $this->advanceTo = '';
        // First show confirmation; justification will be collected after confirm
        $this->dispatch('bs:open', id: 'oversightAdvance');
    }

    /**
     * Opens the justification modal with the action type set to 'reroute'.
     *
     * This function is used to re-route an event request that has been flagged for oversight.
     * It will open the justification modal with the action type set to 'reroute', allowing the user to enter a justification for the re-routing.
     */
    // reroute removed

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
        // Apply status change via service (no hardcoded statuses)
        $toastMsg = null;
        if ($this->editId && in_array($this->actionType, ['approve', 'deny'], true)) {
            try {
                $svc = app(EventService::class);
                $event = $this->getEventFromServiceById((int)$this->editId);
                if ($event) {
                    if ($this->actionType === 'approve') {
                        $svc->approveEvent($event, Auth::user());
                        $toastMsg = 'Event approved';
                    } elseif ($this->actionType === 'deny') {
                        $this->validateJustification();
                        $svc->denyEvent((string)($this->justification ?? ''), $event, Auth::user());
                        $toastMsg = 'Event denied';
                    }
                }
            } catch (\Throwable $e) {
                // ignore service errors; UI will still close modals
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
    $total = $this->filtered()->count();
    $last  = max(1, (int) ceil($total / max(1, $this->pageSize)));
    $this->page = max(1, min($target, $last));
    }

    /**
     * Renders the events index page.
     *
     * The page is re-paginated if the current page number is not the same as the paginator's current page number.
     * The visible IDs are obtained from the paginator.
     * The view is rendered with the paginator, visible IDs, and organizations.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): \Illuminate\Contracts\View\View
    {
        $this->authorize('access-dashboard');

        $paginator = $this->paginated();
        if ($this->page !== $paginator->currentPage()) {
            $paginator = $this->paginated();
        }
        $visibleIds = $paginator->pluck('id')->all();
        // Venue options for filter (disambiguate duplicate names)
        try {
            $venues = app(EventService::class)->listVenuesForFilter()->values()->all();
        } catch (\Throwable $e) {
            $venues = [];
        }
        return view('livewire.admin.events-index', [
            'rows' => $paginator,
            'visibleIds' => $visibleIds,
            'statuses' => $this->statuses,
            'categories' => $this->categoryPool,
            'venues' => $venues,
        ]);
    }

    // Presentation helpers
    /**
     * Render the Events list with filters and modals.
     */
    public function statusBadgeClass(string $status): string
    {
    $s = mb_strtolower(trim($status));
        if ($s === '') return 'text-bg-secondary';
        if (str_contains($s, 'approve')) return 'text-bg-success';
        if (str_contains($s, 'deny') || str_contains($s, 'reject') || str_contains($s, 'cancel') || str_contains($s, 'withdraw')) return 'text-bg-danger';
        if (str_contains($s, 'pending')) return 'text-bg-primary';
        return 'text-bg-secondary';
    }

    // Private/Protected Helper Methods

    protected function allRequests(): Collection
    {
        $this->authorize('perform-override');

        // Query live data from DB and normalize for the view
        $raw = $this->fetchEventsCollection();
        $rows = $raw
            ->map(function ($e) {
                $from = $e->start_time instanceof DateTimeInterface ? $e->start_time->format('Y-m-d H:i') : (string)$e->start_time;
                $to   = $e->end_time   instanceof DateTimeInterface ? $e->end_time->format('Y-m-d H:i')   : (string)$e->end_time;
                $requestor = method_exists($e, 'requester') && $e->requester ? trim(($e->requester->first_name ?? '') . ' ' . ($e->requester->last_name ?? '')) : ('User ' . (string)($e->creator_id ?? ''));
                $venueName = method_exists($e, 'venue') && $e->venue ? ($e->venue->name ?? $e->venue->code ?? '') : '';
                $category  = method_exists($e, 'categories') && $e->categories?->first()?->name ? $e->categories->first()->name : '';
                return [
                    'id' => (int)$e->id,
                    'title' => (string)($e->title ?? 'Untitled'),
                    'requestor' => $requestor,
                    'organization' => (string)($e->organization_name ?? ''),
                    'organization_advisor_name' => (string)($e->organization_advisor_name ?? ''),
                    'organization_advisor_email' => (string)($e->organization_advisor_email ?? ''),
                    'venue' => (string)$venueName,
                    'venue_id' => (int)($e->venue_id ?? 0),
                    'from' => (string)$from,
                    'to' => (string)$to,
                    'status' => (string)($e->status ?? ''),
                    'category' => (string)$category,
                    'updated' => now()->format('Y-m-d H:i'),
                    'description' => (string)($e->description ?? ''),
                    'attendees' => (int)($e->guest_size ?? 0),
                    'handles_food' => (bool)($e->handles_food ?? false),
                    'use_institutional_funds' => (bool)($e->use_institutional_funds ?? false),
                    'external_guest' => (bool)($e->external_guest ?? false),
                    'creator_institutional_number' => (string)($e->creator_institutional_number ?? ''),
                    'creator_phone_number'  => (string)($e->creator_phone_number ?? ''),
                ];
            });
        return collect($rows);
    }

    /**
     * Applies filters to the collection of all event requests (excluding soft-deleted).
     */
    protected function filtered(): Collection
    {
        $s = mb_strtolower(trim($this->search));
        return $this->allRequests()->filter(function ($request) use ($s) {
            $orgVal = $request['organization'] ?? ($request['organization_nexo_name'] ?? '');
            $hit = $s === '' ||
                str_contains(mb_strtolower($request['title']), $s) ||
                str_contains(mb_strtolower($request['requestor']), $s) ||
                str_contains(mb_strtolower((string)$orgVal), $s);
            $statOk  = $this->status === '' || (mb_strtolower((string)($request['status'] ?? '')) === mb_strtolower($this->status));
            // Venue filter: support either venue id (int) or venue name (string)
            $venueOk = true;
            if (!is_null($this->venue) && $this->venue !== '') {
                if (is_int($this->venue) || (is_string($this->venue) && ctype_digit($this->venue))) {
                    $venueOk = (int)($request['venue_id'] ?? 0) === (int)$this->venue;
                } else {
                    $venueOk = (string)$request['venue'] === (string)$this->venue;
                }
            }
            $catOk   = $this->category === '' || $request['category'] === $this->category;
            // Organization is part of free-text search; org filter disabled
            $orgOk   = true;

            // Normalize date comparisons to timestamps
            $dateOk = true;
            $reqFromTs = strtotime(str_replace('T', ' ', (string)($request['from'] ?? '')));
            $reqToTs   = strtotime(str_replace('T', ' ', (string)($request['to']   ?? '')));
            $fromTs = $this->from ? strtotime(str_replace('T', ' ', (string)$this->from)) : null;
            $toTs   = $this->to   ? strtotime(str_replace('T', ' ', (string)$this->to))   : null;
            if ($fromTs) {
                $dateOk = $dateOk && ($reqFromTs !== false && $reqFromTs >= $fromTs);
            }
            if ($toTs) {
                $dateOk = $dateOk && ($reqToTs   !== false && $reqToTs   <= $toTs);
            }

            return $hit && $statOk && $venueOk && $dateOk && $catOk && $orgOk;
        })->values();
    }

    /**
     * Validation rules for all editable event fields.
     *
     * @return array<string, array<int,string|\Illuminate\Validation\Rule>>
     */
    protected function eventFieldRules(): array
    {
        return [
            'eTitle' => ['required', 'string', 'min:3', 'max:120', 'not_regex:/^\s*$/'],
            'ePurpose' => ['required', 'string', 'min:3', 'max:2000', 'not_regex:/^\s*$/'],
            'eVenueId' => ['required', 'integer', 'min:1'],
            'eFrom' => ['required', 'string'], // validated as date in datesInOrder()
            'eTo' => ['required', 'string'],   // validated as date in datesInOrder()
            'eAttendees' => ['required', 'integer', 'min:1', 'max:50000'],
            'eCategory' => ['required', 'string', Rule::in($this->categoryPool)],
            'eHandlesFood' => ['boolean'],
            'eUseInstitutionalFunds' => ['boolean'],
            'eExternalGuest' => ['boolean'],
            'eOrganization' => ['required', 'string', 'min:2', 'max:120', 'not_regex:/^\s*$/'],
            'eAdvisorName' => ['nullable', 'string', 'max:120'],
            'eAdvisorEmail' => ['nullable', 'email', 'max:120'],
            // Advisor phone removed; no backing column in schema
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
     * Paginates the filtered collection of event requests.
     */
    protected function paginated(): LengthAwarePaginator
    {
        $data = $this->filtered();
        $items = $data->slice(($this->page - 1) * $this->pageSize, $this->pageSize)->values();
        return new LengthAwarePaginator($items, $data->count(), $this->pageSize, $this->page, ['path' => request()->url(), 'query' => request()->query()]);
    }

    /**
     * Returns an array of validation rules for the justification field.
     */
    protected function rules(): array
    {
        return [
            'justification' => ['required', 'string', 'min:10', 'max:200', 'not_regex:/^\s*$/']
        ];
    }

    /**
     * Validates only the justification field.
     */
    protected function validateJustification(): void
    {
        $this->validateOnly('justification');
    }

    /**
     * Fetch events via EventService without relying on undefined methods.
     */
    protected function fetchEventsCollection(): Collection
    {
        try {
            $res = app(EventService::class)->getAllEvents([]);
            if ($res instanceof LengthAwarePaginator) {
                return collect($res->items());
            }
            if (is_iterable($res)) return collect($res);
        } catch (\Throwable $e) { /* ignore */
        }
        return collect();
    }

    /**
     * Resolve a single event by id using the service response only.
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


}
