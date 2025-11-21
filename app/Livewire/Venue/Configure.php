<?php

/**
 * Livewire Component: Configure Venue
 *
 * EventFlow (Laravel 12 + Livewire 3 + Bootstrap 5).
 * Edits configuration for a single venue, including requirements and optional
 * opening/closing hours (single daily window).
 *
 * Responsibilities:
 * - Load a Venue in mount() and expose it to the view.
 * - Create/Update/Delete venue requirements.
 * - Optionally set opening_time/closing_time for the venue (no per-day schedule).
 * - Dispatch UI events for toasts/modals as needed.
 *
 * @since   2025-11-01
 */

namespace App\Livewire\Venue;

use App\Models\Venue;
use App\Services\VenueAvailabilityService;
use App\Services\UseRequirementService;
use App\Services\VenueService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
/**
 * Class Configure
 *
 * Livewire component to configure a venue's requirements and availability hours.
 * Receives a Venue (or ID) on mount and exposes state/methods for the form.
 */
class Configure extends Component
{
    private const DAYS_OF_WEEK = [
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
        'Sunday',
    ];
    /**
     * @var \App\Models\Venue
     */
    public Venue $venue;

    /** @var array<int,array{id?:int, uuid:string, name:string, description:?string, hyperlink:?string, position:int}> */
    public array $rows = [];

    public string $description = '';
    public array $availabilityForm = [];
    public array $weekDays = [];

    public ?string $confirmDeleteUuid = null;
    public string $confirmDeleteAction = '';
    public string $confirmDeleteMessage = '';
    public string $justification = '';
    public string $pendingAction = '';
    public ?string $pendingUuid = null;

    protected VenueService $venueService;
    protected VenueAvailabilityService $venueAvailabilityService;
    protected UseRequirementService $useRequirementService;

    public function boot(
        VenueService $venueService,
        VenueAvailabilityService $venueAvailabilityService,
        UseRequirementService $useRequirementService
    ): void {
        $this->venueService = $venueService;
        $this->venueAvailabilityService = $venueAvailabilityService;
        $this->useRequirementService = $useRequirementService;
    }

    /**
     * Initialize component state from a given Venue identifier.
     *
     * @param mixed $venue
     */
    public function mount($venue): void
    {
        $venueId = $venue instanceof Venue ? (int) $venue->getKey() : (int) $venue;
        $this->venue = $this->venueService->requireById($venueId);
        $this->description = (string) ($this->venue->description ?? '');
        $this->weekDays = self::DAYS_OF_WEEK;

        $this->refreshAvailabilityForm();
        $this->refreshRequirements();



//        $this->rows = $venue->requirements()->get()->map(function (UseRequirement $r) {
//            return [
//                'id'          => $r->id,
//                'uuid'        => (string) Str::uuid(), // stable wire:key per row
//                'name'        => $r->name,
//                'description' => $r->description,
//                'hyperlink'     => $r->hyperlink,
//                'position'    => $r->position ?? 0,
//            ];
//        })->values()->all();

        if (empty($this->rows)) {
            $this->addRow(); // start with one empty row
        }
    }
/**
 * AddRow action.
 * @return void
 */

    public function addRow(): void
    {
        $this->rows[] = [
            'uuid'        => (string) Str::uuid(),
            'name'        => '',
            'description' => '',
            'hyperlink'   => '',
            'position'    => count($this->rows),
        ];
    }
/**
 * SaveAvailability action.
 * @return void
 */

    public function saveAvailability(): void
    {
        $this->startJustification('save_availability');
    }
    /**
     * Remove a requirement row and persist immediately.
     *
     * @param string $uuid
     * @return void
     */
    public function removeRow(string $uuid): void
    {
        $this->applyRemoveRow($uuid);
    }

    /**
     * Show confirmation modal before removing a single requirement row.
     *
     * @param string $uuid
     * @return void
     */
    public function confirmRemoveRow(string $uuid): void
    {
        $this->authorize('update-requirements', $this->venue);

        $row = $this->findRowByUuid($uuid);
        if (!$row) {
            return;
        }

        $label = trim((string) ($row['name'] ?? ''));
        $labelText = $label !== '' ? "\"{$label}\"" : 'this requirement';

        $this->confirmDeleteUuid = $uuid;
        $this->confirmDeleteAction = 'remove';
        $this->confirmDeleteMessage = "Remove {$labelText}? This change is only applied after saving.";

        $this->dispatch('bs:open', id: 'requirementsConfirm');
    }

    /**
     * Show confirmation modal before clearing all requirements.
     *
     * @return void
     */
    public function confirmClearRequirements(): void
    {
        $this->authorize('update-requirements', $this->venue);

        $this->confirmDeleteUuid = null;
        $this->confirmDeleteAction = 'clear';
        $this->confirmDeleteMessage = 'Remove all requirements for this venue? This action cannot be undone.';

        $this->dispatch('bs:open', id: 'requirementsConfirm');
    }

    /**
     * Execute the confirmed deletion action.
     *
     * @return void
     */
    public function confirmDelete(): void
    {
        if ($this->confirmDeleteAction === 'clear') {
            $this->startJustification('clear_requirements');
        } elseif ($this->confirmDeleteAction === 'remove' && $this->confirmDeleteUuid) {
            $this->applyRemoveRow($this->confirmDeleteUuid);
        }

        $this->dispatch('bs:close', id: 'requirementsConfirm');
        $this->resetConfirmDelete();
    }

    /**
     * Cancel the confirmation dialog and reset state.
     *
     * @return void
     */
    public function cancelConfirmDelete(): void
    {
        $this->resetConfirmDelete();
        $this->dispatch('bs:close', id: 'requirementsConfirm');
    }
    /**
     * Validate input and persist configuration changes.
     * @return void
     */

    public function save(): void
    {
        $this->startJustification('save_requirements');
    }

    /**
     * Remove every requirement associated with the current venue.
     *
     * @return void
     */
    public function clearRequirements(): void
    {
        $this->authorize('update-requirements', $this->venue);

        $this->venueService->updateOrCreateVenueRequirements($this->venue, [], Auth::user());

        $this->rows = [];
        $this->addRow();
        $this->dispatchGreenToast('All requirements have been cleared.');
    }
    
/**
 * GoBack action.
 * @return void
 */
    public function goBack(): void
    {
        $previous = url()->previous();
        // Fallback if there's no referrer or it’s off-site
        $fallback = route('venues.index');

        // Basic same-origin check
        $isSameOrigin = $previous && str_starts_with($previous, url('/'));

        $this->redirect($isSameOrigin ? $previous : $fallback);
    }
/**
 * Render the configure view for the selected venue.
 * @return \Illuminate\Contracts\View\View
 */

    public function render()
    {
        $this->authorize('update-availability', $this->venue);
        $this->authorize('update-requirements', $this->venue);

        return view('livewire.venue.configure');
    }

    protected function refreshAvailabilityForm(): void
    {
        $records = $this->venueAvailabilityService->listByVenueId((int) $this->venue->id);
        $this->availabilityForm = $this->buildAvailabilityForm($records);
    }

    protected function refreshRequirements(): void
    {
        $requirements = $this->useRequirementService->listByVenue((int) $this->venue->id);
        $this->rows = $requirements->map(function ($requirement) {
            return [
                'id'          => $requirement->id,
                'uuid'        => (string) Str::uuid(),
                'name'        => $requirement->name,
                'description' => $requirement->description,
                'hyperlink'   => (string) ($requirement->hyperlink ?? ''),
                'position'    => $requirement->position ?? 0,
            ];
        })->values()->all();

        if (empty($this->rows)) {
            $this->addRow();
        }
    }

    protected function buildAvailabilityForm(Collection $availabilities): array
    {
        $existing = $availabilities->keyBy('day');
        $form = [];

        foreach (self::DAYS_OF_WEEK as $day) {
            $record = $existing->get($day);
            $form[$day] = [
                'enabled' => $record !== null,
                'opens_at' => $record ? substr($record->opens_at, 0, 5) : '',
                'closes_at' => $record ? substr($record->closes_at, 0, 5) : '',
            ];
        }

        return $form;
    }

    protected function normalizeAvailabilityInput(): array
    {
        $payload = [];
        $errors = [];

        foreach (self::DAYS_OF_WEEK as $day) {
            $row = $this->availabilityForm[$day] ?? [];
            $enabled = (bool)($row['enabled'] ?? false);
            if (!$enabled) {
                continue;
            }

            $validator = validator(
                [
                    'opens_at' => $row['opens_at'] ?? null,
                    'closes_at' => $row['closes_at'] ?? null,
                ],
                [
                    'opens_at' => ['required', 'date_format:H:i'],
                    'closes_at' => ['required', 'date_format:H:i', 'after:opens_at'],
                ],
                [],
                [
                    'opens_at' => "$day opening time",
                    'closes_at' => "$day closing time",
                ]
            );

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $field => $messages) {
                    $errors["availabilityForm.$day.$field"] = $messages;
                }
                continue;
            }

            $payload[] = [
                'day' => $day,
                'opens_at' => $row['opens_at'],
                'closes_at' => $row['closes_at'],
            ];
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        if (empty($payload)) {
            throw ValidationException::withMessages([
                'availabilityForm' => ['Select at least one day and provide its hours.'],
            ]);
        }

        return $payload;
    }

    private function findRowByUuid(string $uuid): ?array
    {
        foreach ($this->rows as $row) {
            if (($row['uuid'] ?? null) === $uuid) {
                return $row;
            }
        }

        return null;
    }

    private function resetConfirmDelete(): void
    {
        $this->confirmDeleteAction = '';
        $this->confirmDeleteUuid = null;
        $this->confirmDeleteMessage = '';
    }

    public function startJustification(string $action, ?string $uuid = null): void
    {
        $this->pendingAction = $action;
        $this->pendingUuid = $uuid;
        $this->justification = '';
        $this->resetErrorBag(['justification']);
        $this->dispatch('bs:open', id: 'sharedJustification');
    }

    public function submitJustification(): void
    {
        $this->validate([
            'justification' => ['required', 'string', 'min:10'],
        ], [], [
            'justification' => 'justification',
        ]);

        $action = $this->pendingAction;
        $uuid = $this->pendingUuid;
        $justification = $this->justification;

        $this->pendingAction = '';
        $this->pendingUuid = null;
        $this->justification = '';

        $this->dispatch('bs:close', id: 'sharedJustification');

        if ($action === 'save_availability') {
            $this->performSaveAvailability($justification);
        } elseif ($action === 'save_requirements') {
            $this->performSaveRequirements($justification);
        } elseif ($action === 'clear_requirements') {
            $this->performClearRequirements($justification);
        }
    }

    protected function performSaveAvailability(string $justification): void
    {
        $this->authorize('update-availability', $this->venue);

        $this->validate([
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $payload = $this->normalizeAvailabilityInput();

        $this->venue = $this->venueService->updateVenueDescription($this->venue, $this->description, Auth::user());

        $this->venueService->updateVenueOperatingHours($this->venue, $payload, Auth::user(), $justification);

        $this->refreshAvailabilityForm();

        $this->dispatchGreenToast('Venue details updated.');
    }

    protected function performSaveRequirements(string $justification): void
    {
        $this->authorize('update-availability', $this->venue);
        $this->authorize('update-requirements', $this->venue);

        foreach ($this->rows as $i => &$row) {
            $row['position'] = $i;
        }
        unset($row);

        $this->validate([
            'rows'                 => 'array|min:1',
            'rows.*.name'          => 'required|string|max:255',
            'rows.*.description'   => 'nullable|string|max:2000',
            'rows.*.hyperlink'     => 'nullable|url|max:2048',
            'rows.*.position'      => 'integer|min:0',
        ], [], [
            'rows.*.name' => 'requirement name',
            'rows.*.hyperlink' => 'document link',
        ]);

        $this->venueService->updateOrCreateVenueRequirements($this->venue, $this->rows, Auth::user());

        $this->dispatchGreenToast('Venue requirements saved.');
        $this->refreshRequirements();
    }

    protected function performClearRequirements(string $justification): void
    {
        $this->authorize('update-requirements', $this->venue);

        $this->venueService->updateOrCreateVenueRequirements($this->venue, [], Auth::user());

        $this->rows = [];
        $this->addRow();

        $this->dispatchGreenToast('All requirements have been cleared.');
    }

    private function applyRemoveRow(string $uuid): void
    {
        foreach ($this->rows as $i => $row) {
            if (($row['uuid'] ?? null) === $uuid) {
                array_splice($this->rows, $i, 1);
                break;
            }
        }
        if (empty($this->rows)) {
            $this->addRow();
        }

        foreach ($this->rows as $i => &$row) {
            $row['position'] = $i;
        }
        unset($row);
    }

    private function dispatchGreenToast(string $message): void
    {
        $this->dispatch('toast', message: $message, className: 'text-bg-success border-0 shadow-lg rounded-3', delay: 3200);
    }
}



//public function render()
//{
//    return view('livewire.venue.managers.configure');
//}
