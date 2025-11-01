<?php

namespace App\Livewire\Admin;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Livewire\Traits\EventFilters;
use App\Livewire\Traits\EventEditState;
use App\Models\Event as EventModel;
use App\Models\Category as CategoryModel;
use DateTimeInterface;
use Illuminate\Validation\Rule;

#[Layout('layouts.app')]
class EventsIndex extends Component
{
    use EventFilters, EventEditState;

    /**
     * Backing store for generated requests displayed in oversight.
     * Generated from EventFactory on mount and kept stable during the session.
     *
     * @var array<int,array<string,mixed>>
     */
    public array $requests = [];
    /**
     * Pool of category names generated from CategoryFactory (no DB).
     *
     * @var array<int,string>
     */
    public array $categoryPool = [];

    /**
     * Initialize the component with in-memory event requests and a category pool generated from factories.
     */
    public function mount(): void
    {
        // Generate a consistent set of fake events without touching the DB
        $count = 20;
        $raw = EventModel::factory()->count($count)->raw([
            // Avoid resolving relation factories; use simple integers
            'creator_id' => fake()->numberBetween(100, 999),
            'venue_id'   => fake()->numberBetween(1, 50),
        ]);

        // Generate a small pool of categories via CategoryFactory (no DB writes)
        $catRaw = CategoryModel::factory()->count(6)->raw();
        $catRows = is_array($catRaw) && isset($catRaw[0]) ? $catRaw : [$catRaw];
        $catNames = array_values(array_filter(array_map(function ($c) {
            return is_array($c) ? (string)($c['name'] ?? '') : '';
        }, $catRows), fn($n) => $n !== ''));
        if (empty($catNames)) {
            $catNames = ['General'];
        }
        $this->categoryPool = $catNames;

        $rows = is_array($raw) && isset($raw[0]) ? $raw : [$raw];
        $mapped = [];
        foreach (array_values($rows) as $i => $request) {
            $from = $request['start_time'];
            $to   = $request['end_time'];
            if ($from instanceof DateTimeInterface) $from = $from->format('Y-m-d H:i');
            if ($to instanceof DateTimeInterface)   $to   = $to->format('Y-m-d H:i');

            // Pick a category from the generated pool
            $pickIdx = array_rand($catNames);
            $category = $catNames[$pickIdx] ?? 'General';

            $mapped[] = [
                'id' => 1000 + $i,
                'title' => (string)($request['title'] ?? 'Untitled'),
                'requestor' => 'User ' . (string)($request['creator_id'] ?? 'N/A'),
                'organization' => (string)($request['organization_nexo_name'] ?? ''),
                'organization_advisor_name' => (string)($request['organization_advisor_name'] ?? ''),
                'organization_advisor_email' => (string)($request['organization_advisor_email'] ?? ''),
                'organization_advisor_phone' => (string)($request['organization_advisor_phone'] ?? ''),
                'student_number' => (string)($request['student_number'] ?? ''),
                'student_phone'  => (string)($request['student_phone'] ?? ''),
                'venue' => 'Venue ' . (string)($request['venue_id'] ?? 'N/A'),
                'from' => (string)$from,
                'to' => (string)$to,
                'status' => (string)($request['status'] ?? 'pending'),
                'category' => (string)($request['category'] ?? $category),
                'updated' => now()->format('Y-m-d H:i'),
                'description' => (string)($request['description'] ?? ''),
                'attendees' => (int)($request['guests'] ?? 0),
                'handles_food' => (bool)($request['handles_food'] ?? false),
                'use_institutional_funds' => (bool)($request['use_institutional_funds'] ?? false),
                'external_guest' => (bool)($request['external_guest'] ?? false),
            ];
        }

        $this->requests = $mapped;
    }

    /**
     * Returns a collection of all events, excluding any soft-deleted events.
     *
     * This function uses the session variable 'soft_deleted_event_ids' to filter
     * out any events that have been deleted. It returns a collection of all the
     * remaining events.
     *
     * @return Collection
     */
    protected function allRequests(): Collection
    {
        $deletedIndex = array_flip(array_unique(array_map('intval', session('soft_deleted_event_ids', []))));

        $combined = array_values(array_filter(
            $this->requests,
            function (array $request) use ($deletedIndex) {
                return !isset($deletedIndex[(int) ($request['id'] ?? 0)]);
            }
        ));

        return collect($combined);
    }

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
     * Clears all filters and resets the current page to 1.
     *
     * This function is called when the user clicks the "Clear" button on the filter form.
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->status = '';
        $this->venue = '';
        $this->from = null;
        $this->to = null;
        $this->requestor = '';
        $this->category = '';
        $this->organization = '';
        $this->page = 1;
    }

    /**
     * Opens the edit event modal with the given ID.
     * If the event is not found, the function does nothing.
     * It sets the currently edited event ID and the values of the event to be edited, and then opens the edit event modal.
     * @param int $id The ID of the event to edit
     */
    public function openEdit(int $id): void
    {
        $request = $this->filtered()->firstWhere('id', $id);
        if (!$request) return;
        $this->editId = $request['id'];
        $this->eTitle = $request['title'];
        $this->ePurpose = $request['description'] ?? ($request['purpose'] ?? '');
        $this->eVenue = $request['venue'];
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
        $this->eAdvisorPhone   = $request['organization_advisor_phone'] ?? '';
        $this->eStudentNumber  = $request['student_number'] ?? '';
        $this->eStudentPhone   = $request['student_phone']  ?? '';

        $this->dispatch('bs:open', id: 'oversightEdit');
    }

    /**
     * Open a read-only view modal for a specific request, pre-filling the view state.
     *
     * @param int $id The ID of the request to display.
     */
    public function openView(int $id): void
    {
        $request = $this->filtered()->firstWhere('id', $id);
        if (!$request) return;
        $this->editId = $request['id'];
        $this->eTitle = $request['title'];
        $this->ePurpose = $request['description'] ?? ($request['purpose'] ?? '');
        $this->eVenue = $request['venue'];
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
        $this->eAdvisorPhone   = $request['organization_advisor_phone'] ?? '';
        $this->eStudentNumber  = $request['student_number'] ?? '';
        $this->eStudentPhone   = $request['student_phone']  ?? '';

        $this->dispatch('bs:open', id: 'oversightView');
    }

    /**
     * Returns an array of validation rules for the justification field.
     *
     * The rules array contains a single key-value pair where the key is 'justification'
     * and the value is an array of validation rules. The validation rules are
     * 'required', 'string', and 'min:3'.
     *
     * @return array
     */
    protected function rules(): array
    {
        return [
            'justification' => ['required', 'string', 'min:3']
        ];
    }

    /**
     * Validates only the justification field.
     *
     * This function is a helper to validate only the justification field by calling
     * `validateOnly` with the justification field as the parameter.
     */
    protected function validateJustification(): void
    {
        $this->validateOnly('justification');
    }

    /**
     * Opens the justification modal for saving the event.
     *
     * This function sets the actionType to 'save' and then opens the justification modal.
     */
    public function save(): void
    {
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
        $this->validateJustification();
        $isEditing = !empty($this->editId);
        $this->dispatch('bs:close', id: 'oversightJustify');
        $this->dispatch('bs:close', id: 'oversightEdit');
        $this->dispatch('toast', message: 'Event saved');
        $this->reset(['actionType', 'justification']);
    }

    /**
     * Opens the justification modal for deleting an event.
     *
     * This function sets the currently edited event ID and the actionType to 'delete', and then opens the justification modal.
     * @param int $id The ID of the event to delete
     */
    public function delete(int $id): void
    {
        $this->editId = $id;
        $this->actionType = 'delete';
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
        if ($this->editId) {
            $this->validateJustification();
            session()->push('soft_deleted_event_ids', $this->editId);
        }
        $this->dispatch('bs:close', id: 'oversightJustify');
        $this->dispatch('toast', message: 'Event deleted');
        $this->reset(['editId', 'actionType', 'justification']);
    }

    // Restore-all functionality removed

    /**
     * Opens the justification modal with the action type set to 'approve'.
     *
     * This function is used to approve an event request that has been flagged for oversight.
     * It will open the justification modal with the action type set to 'approve', allowing the user to enter a justification for the approval.
     */
    public function approve(): void
    {
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
        $this->actionType = 'advance';
        $this->advanceTo = '';
        $this->dispatch('bs:open', id: 'oversightAdvance');
    }

    /**
     * Opens the justification modal with the action type set to 'reroute'.
     *
     * This function is used to re-route an event request that has been flagged for oversight.
     * It will open the justification modal with the action type set to 'reroute', allowing the user to enter a justification for the re-routing.
     */
    public function reroute(): void
    {
        $this->actionType = 'reroute';
        $this->rerouteTo = '';
        $this->dispatch('bs:open', id: 'oversightReroute');
    }

    /**
     * Closes the justification and edit modals and displays a toast message indicating that the action has been completed.
     *
     * This function is called after the user has submitted the justification form.
     * It will close the justification and edit modals, and then display a toast message indicating that the action has been completed.
     * The toast message will be in the format "Action completed", where "Action" is the value of the `actionType` property.
     */
    public function confirmAction(): void
    {
        // Apply status change based on action type
        $toastMsg = null;
        if ($this->editId && in_array($this->actionType, ['approve', 'deny', 'advance'], true)) {
            $newStatus = match ($this->actionType) {
                'approve' => 'Approved',
                'deny'    => 'Denied',
                'advance' => 'Pending',
                default   => null,
            };
            if ($newStatus !== null) {
                foreach ($this->requests as &$request) {
                    if ((int)($request['id'] ?? 0) === (int)$this->editId) {
                        $request['status']  = $newStatus;
                        $request['updated'] = now()->format('Y-m-d H:i');
                        break;
                    }
                }
                unset($request);
            }

            // More descriptive toast
            $toastMsg = match ($this->actionType) {
                'approve' => 'Event approved',
                'deny'    => 'Event denied',
                'advance' => ($this->advanceTo !== '' ? 'Advanced to ' . $this->advanceTo : 'Advanced'),
                default   => null,
            };
        }
        $this->dispatch('bs:close', id: 'oversightJustify');
        $this->dispatch('bs:close', id: 'oversightEdit');
        $this->dispatch('toast', message: $toastMsg ?? (ucfirst($this->actionType) . ' completed'));
        $this->reset('actionType', 'justification');
    }

    /**
     * Confirm advance with a target. Updates status and stores the target, with a clear toast.
     */
    public function confirmAdvance(): void
    {
        $data = $this->validate([
            'advanceTo' => ['required', 'string', 'min:2', 'max:120'],
        ]);

        if ($this->editId) {
            foreach ($this->requests as &$request) {
                if ((int)($request['id'] ?? 0) === (int)$this->editId) {
                    $request['status']     = 'Pending';
                    $request['updated']    = now()->format('Y-m-d H:i');
                    $request['routed_to']  = (string)($data['advanceTo'] ?? $this->advanceTo);
                    break;
                }
            }
            unset($request);
        }

        $this->dispatch('bs:close', id: 'oversightAdvance');
        $this->dispatch('bs:close', id: 'oversightEdit');
        $this->dispatch('toast', message: 'Advanced to ' . $this->advanceTo);
        $this->reset('actionType', 'advanceTo');
    }

    /**
     * Confirm reroute with a target. Updates status and stores the reroute target.
     */
    public function confirmReroute(): void
    {
        $data = $this->validate([
            'rerouteTo' => ['required', 'string', 'min:2', 'max:120'],
        ]);

        if ($this->editId) {
            foreach ($this->requests as &$request) {
                if ((int)($request['id'] ?? 0) === (int)$this->editId) {
                    $request['status']     = 'Pending';
                    $request['updated']    = now()->format('Y-m-d H:i');
                    $request['routed_to']  = (string)($data['rerouteTo'] ?? $this->rerouteTo);
                    break;
                }
            }
            unset($request);
        }

        $this->dispatch('bs:close', id: 'oversightReroute');
        $this->dispatch('bs:close', id: 'oversightEdit');
        $this->dispatch('toast', message: 'Re-routed to ' . $this->rerouteTo);
        $this->reset('actionType', 'rerouteTo');
    }

    /**
     * Applies filters to the collection of all event requests (excluding soft-deleted).
     *
     * The filters are:
     * - Search term: filters by title and requestor
     * - Status: filters by status
     * - Venue: filters by venue
     * - Category: filters by category
     * - Organization: filters by organization (falls back to requestor when organization is missing)
     * - Date range: filters by from and to dates
     *
     * Returns a filtered collection of event requests.
     *
     * @return Collection
     */
    protected function filtered(): Collection
    {
        $s = mb_strtolower(trim($this->search));
        return $this->allRequests()->filter(function ($request) use ($s) {
            $hit = $s === '' ||
                str_contains(mb_strtolower($request['title']), $s) ||
                str_contains(mb_strtolower($request['requestor']), $s);
            $statOk  = $this->status === '' || $request['status'] === $this->status;
            $venueOk = $this->venue === '' || $request['venue'] === $this->venue;
            $catOk   = $this->category === '' || $request['category'] === $this->category;
            $orgVal  = $request['organization'] ?? ($request['organization_nexo_name'] ?? ($request['requestor'] ?? ''));
            $orgOk   = $this->organization === '' || $orgVal === $this->organization;

            $dateOk = true;
            if ($this->from) $dateOk = $dateOk && ($request['from'] >= $this->from);
            if ($this->to)   $dateOk = $dateOk && ($request['to']   <= $this->to);

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
            'eTitle' => ['required', 'string', 'min:3', 'max:120'],
            'ePurpose' => ['required', 'string', 'min:3', 'max:2000'],
            'eVenue' => ['required', 'string', 'max:120'],
            'eFrom' => ['required', 'string'], // validated as date in datesInOrder()
            'eTo' => ['required', 'string'],   // validated as date in datesInOrder()
            'eAttendees' => ['required', 'integer', 'min:0', 'max:50000'],
            'eCategory' => ['required', 'string', Rule::in($this->categoryPool)],
            'eHandlesFood' => ['boolean'],
            'eUseInstitutionalFunds' => ['boolean'],
            'eExternalGuest' => ['boolean'],
            'eOrganization' => ['required', 'string', 'min:2', 'max:120'],
            'eAdvisorName' => ['nullable', 'string', 'max:120'],
            'eAdvisorEmail' => ['nullable', 'email', 'max:120'],
            'eAdvisorPhone' => ['nullable', 'string', 'max:50'],
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
     *
     * This function takes the filtered collection of event requests, slices it based on the current page and page size,
     * and returns a LengthAwarePaginator object. The paginator is configured to use the current URL and query string.
     *
     * @return LengthAwarePaginator
     */
    protected function paginated(): LengthAwarePaginator
    {
        $data = $this->filtered();
        $items = $data->slice(($this->page - 1) * $this->pageSize, $this->pageSize)->values();
        return new LengthAwarePaginator($items, $data->count(), $this->pageSize, $this->page, ['path' => request()->url(), 'query' => request()->query()]);
    }

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
     * Dynamic list of organizations derived from current requests (excluding soft-deleted).
     * Falls back to requestor when organization is missing. Sorted naturally, case-insensitive.
     *
     * @return array<int,string>
     */
    public function getOrganizationsProperty(): array
    {
        $vals = $this->allRequests()
            ->map(function ($request) {
                $v = $request['organization'] ?? ($request['organization_nexo_name'] ?? ($request['requestor'] ?? ''));
                return is_string($v) ? trim($v) : '';
            })
            ->filter(fn($v) => $v !== '')
            ->all();

        // Case-insensitive unique preserving first seen casing
        $map = [];
        foreach ($vals as $v) {
            $k = mb_strtolower($v);
            if (!isset($map[$k])) $map[$k] = $v;
        }

        $values = array_values($map);
        usort($values, fn($a, $b) => strnatcasecmp($a, $b));
        return $values;
    }

    /**
     * Renders the events index page.
     *
     * The page is re-paginated if the current page number is not the same as the paginator's current page number.
     * The visible IDs are obtained from the paginator.
     * The view is rendered with the paginator, visible IDs, and organizations.
     *
     * @return Response
     */
    public function render()
    {
        $paginator = $this->paginated();
        if ($this->page !== $paginator->currentPage()) {
            $paginator = $this->paginated();
        }
        $visibleIds = $paginator->pluck('id')->all();
        return view('livewire.admin.events-index', [
            'rows' => $paginator,
            'visibleIds' => $visibleIds,
            'organizations' => $this->organizations,
            'statuses' => $this->statuses,
            'categories' => $this->categoryPool,
        ]);
    }

    /**
     * Dynamic list of statuses from current requests (excluding soft-deleted).
     * Unique, naturally sorted, preserves original casing of first occurrence.
     *
     * @return array<int,string>
     */
    public function getStatusesProperty(): array
    {
        $vals = $this->allRequests()
            ->pluck('status')
            ->filter(fn($v) => is_string($v) && trim($v) !== '')
            ->map(fn($v) => trim($v))
            ->all();

        $map = [];
        foreach ($vals as $v) {
            $k = mb_strtolower($v);
            if (!isset($map[$k])) $map[$k] = $v;
        }
        $values = array_values($map);
        usort($values, fn($a, $b) => strnatcasecmp($a, $b));
        return $values;
    }

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
        if (str_contains($s, 'complete')) return 'text-bg-info';
        return 'text-bg-secondary';
    }
}
