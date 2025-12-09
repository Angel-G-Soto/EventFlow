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
use App\Livewire\Traits\HasJustification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
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
    use HasJustification;
    private const DAYS_OF_WEEK = [
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
        'Sunday',
    ];

    /** @var \App\Models\Venue */
    public Venue $venue;

    /** @var array<int,array{id?:int, uuid:string, name:string, description:?string, hyperlink:?string, position:int}> */
    public array $rows = [];

    public string $description = '';
    public array $availabilityForm = [];
    public string $bulkOpensAt = '';
    public string $bulkClosesAt = '';
    public array $weekDays = [];

    public ?string $confirmDeleteUuid = null;
    public string $confirmDeleteAction = '';
    public string $confirmDeleteMessage = '';
    public string $justification = '';
    public string $pendingAction = '';
    public ?string $pendingUuid = null;

protected string $detailsSnapshot = '';
protected string $requirementsSnapshot = '';
protected string $availabilitySnapshot = '';
    protected bool $detailsDirtyFlag = false;
    protected bool $requirementsDirtyFlag = false;

    protected VenueService $venueService;
    protected VenueAvailabilityService $venueAvailabilityService;
    protected UseRequirementService $useRequirementService;

    /**
     * Dependency injection hook to wire required services.
     *
     * @param VenueService $venueService
     * @param VenueAvailabilityService $venueAvailabilityService
     * @param UseRequirementService $useRequirementService
     * @return void
     */
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
     * @param  mixed  $venue
     */
    public function mount($venue): void
    {
        $venueId = $venue instanceof Venue ? (int) $venue->getKey() : (int) $venue;
        $this->venue = $this->venueService->requireById($venueId);

        $this->description = (string) ($this->venue->description ?? '');
        $this->weekDays = self::DAYS_OF_WEEK;

        $this->refreshAvailabilityForm();
        $this->refreshRequirements();
    }

    /**
     * AddRow action.
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

        $this->recalculateRequirementsDirty();
    }

    /**
     * SaveAvailability action – starts justification flow.
     */
    public function saveAvailability(): void
    {
        $this->startJustification('save_availability');
    }

    /**
     * Enable all days for weekly availability at once.
     */
    public function enableAllDays(): void
    {
        $this->authorize('update-availability', $this->venue);

        foreach (self::DAYS_OF_WEEK as $day) {
            $this->availabilityForm[$day]['enabled'] = true;
        }

        $this->recalculateDetailsDirty();
    }

    /**
     * Disable all days for weekly availability at once.
     */
    public function disableAllDays(): void
    {
        $this->authorize('update-availability', $this->venue);

        foreach (self::DAYS_OF_WEEK as $day) {
            $this->availabilityForm[$day]['enabled'] = false;
        }

        $this->recalculateDetailsDirty();
    }

    /**
     * Apply the bulk opening/closing times to all currently enabled days.
     */
    public function applyBulkAvailability(): void
    {
        $this->authorize('update-availability', $this->venue);

        $enabledDays = collect(self::DAYS_OF_WEEK)
            ->filter(fn (string $day) => (bool) ($this->availabilityForm[$day]['enabled'] ?? false));

        if ($enabledDays->isEmpty()) {
            throw ValidationException::withMessages([
                'bulkAvailability' => ['Select at least one day before applying bulk hours.'],
            ]);
        }

        $validator = validator(
            [
                'bulkOpensAt' => $this->bulkOpensAt,
                'bulkClosesAt' => $this->bulkClosesAt,
            ],
            [
                'bulkOpensAt' => ['required', 'date_format:H:i'],
                'bulkClosesAt' => ['required', 'date_format:H:i', 'after:bulkOpensAt'],
            ],
            [],
            [
                'bulkOpensAt' => 'opens at',
                'bulkClosesAt' => 'closes at',
            ]
        );

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->messages());
        }

        $opens = $validator->validated()['bulkOpensAt'];
        $closes = $validator->validated()['bulkClosesAt'];

        foreach ($enabledDays as $day) {
            $this->availabilityForm[$day]['opens_at'] = $opens;
            $this->availabilityForm[$day]['closes_at'] = $closes;
        }

        $this->recalculateDetailsDirty();
    }

    /**
     * Remove a requirement row and persist immediately (via justification when clearing).
     */
    public function removeRow(string $uuid): void
    {
        $this->applyRemoveRow($uuid);
    }

    /**
     * Show confirmation modal before removing a single requirement row.
     */
    public function confirmRemoveRow(string $uuid): void
    {
        $this->authorize('update-requirements', $this->venue);

        $row = $this->findRowByUuid($uuid);
        if (! $row) {
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
     */
    public function cancelConfirmDelete(): void
    {
        $this->resetConfirmDelete();
        $this->dispatch('bs:close', id: 'requirementsConfirm');
    }

    /**
     * Save requirements – starts justification flow.
     */
    public function save(): void
    {
        $this->startJustification('save_requirements');
    }

    /**
     * Track dirty state for description/availability/requirements.
     */
    public function updated(string $propertyName, mixed $value): void
    {
        if (
            $propertyName === 'description'
            || $propertyName === 'availabilityForm'
            || str_starts_with($propertyName, 'availabilityForm.')
        ) {
            $this->recalculateDetailsDirty();
        }

        if ($propertyName === 'rows' || str_starts_with($propertyName, 'rows.')) {
            $this->recalculateRequirementsDirty();
        }
    }

    /**
     * Remove every requirement associated with the current venue (without justification).
     */
    public function clearRequirements(): void
    {
        $this->authorize('update-requirements', $this->venue);

        $this->venueService->updateOrCreateVenueRequirements($this->venue, [], Auth::user());
        $this->rows = [];
        $this->updateRequirementsSnapshot();
        $this->dispatchGreenToast('All requirements have been cleared.');
    }

    /**
     * GoBack action.
     */
    public function goBack(): void
    {
        $previous = url()->previous();
        // Fallback if there's no referrer or it’s off-site
        $fallback = route('venues.manage');

        // Basic same-origin check
        $isSameOrigin = $previous && str_starts_with($previous, url('/'));

        $this->redirect($isSameOrigin ? $previous : $fallback);
    }

    /**
     * Render the configure view for the selected venue.
     */
    public function render()
    {
        $this->authorize('update-availability', $this->venue);
        $this->authorize('update-requirements', $this->venue);

        return view('livewire.venue.configure');
    }

    /**
     * Loads availability data from storage and snapshots initial state.
     *
     * @return void
     */
    protected function refreshAvailabilityForm(): void
    {
        $records = $this->venueAvailabilityService->listByVenueId((int) $this->venue->id);
        $this->availabilityForm = $this->buildAvailabilityForm($records);
        $this->updateDetailsSnapshot();
        $this->availabilitySnapshot = $this->snapshotAvailability();
    }

    /**
     * Loads venue requirements into the editable rows array.
     *
     * @return void
     */
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

        $this->updateRequirementsSnapshot();
    }

    /**
     * Builds the default availability form structure for all week days.
     *
     * @param Collection $availabilities
     * @return array
     */
    protected function buildAvailabilityForm(Collection $availabilities): array
    {
        $existing = $availabilities->keyBy('day');
        $form = [];

        foreach (self::DAYS_OF_WEEK as $day) {
            $record = $existing->get($day);
            $form[$day] = [
                'enabled'   => $record !== null,
                'opens_at'  => $record ? substr($record->opens_at, 0, 5) : '',
                'closes_at' => $record ? substr($record->closes_at, 0, 5) : '',
            ];
        }

        return $form;
    }

    /**
     * Validates and normalizes the availability form into a payload.
     *
     * @return array<int,array{day:string,opens_at:string,closes_at:string}>
     */
    protected function normalizeAvailabilityInput(): array
    {
        $payload = [];
        $errors = [];
        $state = $this->normalizedAvailabilityState();

        foreach (self::DAYS_OF_WEEK as $day) {
            $row = $state[$day];
            $enabled = $row['enabled'];

            if (! $enabled) {
                continue;
            }

            $validator = validator(
                [
                    'opens_at'  => $row['opens_at'] !== '' ? $row['opens_at'] : null,
                    'closes_at' => $row['closes_at'] !== '' ? $row['closes_at'] : null,
                ],
                [
                    'opens_at'  => ['required', 'date_format:H:i'],
                    'closes_at' => ['required', 'date_format:H:i', 'after:opens_at'],
                ],
                [],
                [
                    'opens_at'  => "$day opening time",
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
                'day'       => $day,
                'opens_at'  => $row['opens_at'],
                'closes_at' => $row['closes_at'],
            ];
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return $payload;
    }

    /**
     * Finds a requirement row by its UUID.
     *
     * @param string $uuid
     * @return array|null
     */
    private function findRowByUuid(string $uuid): ?array
    {
        foreach ($this->rows as $row) {
            if (($row['uuid'] ?? null) === $uuid) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Clears confirmation state for delete dialogs.
     *
     * @return void
     */
    private function resetConfirmDelete(): void
    {
        $this->confirmDeleteAction = '';
        $this->confirmDeleteUuid = null;
        $this->confirmDeleteMessage = '';
    }

    /**
     * Opens the justification modal for a pending action.
     *
     * @param string $action
     * @param string|null $uuid
     * @return void
     */
    public function startJustification(string $action, ?string $uuid = null): void
    {
        $this->pendingAction = $action;
        $this->pendingUuid = $uuid;
        $this->justification = '';

        $this->resetErrorBag(['justification']);

        $this->dispatch('bs:open', id: 'sharedJustification');
    }

    /**
     * Validates justification text and routes to the requested action handler.
     *
     * @return void
     */
    public function submitJustification(): void
    {
        $this->validate([
            'justification' => $this->justificationRules(true),
        ], [], [
            'justification' => 'justification',
        ]);

        $action        = $this->pendingAction;
        $uuid          = $this->pendingUuid;
        $justification = $this->justification;

        $this->pendingAction = '';
        $this->pendingUuid   = null;
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

    /**
     * Persists availability changes and description updates.
     *
     * @param string $justification
     * @return void
     */
    protected function performSaveAvailability(string $justification): void
    {
        $this->authorize('update-availability', $this->venue);

        // Trim to avoid whitespace-only descriptions and enforce length bounds when provided.
        $this->description = trim($this->description);

        // If a description existed and is now cleared, treat it as invalid (enforce min length).
        if ($this->venue->description && $this->description === '') {
            throw ValidationException::withMessages([
                'description' => ['Description must be at least 10 characters.'],
            ]);
        }

        $this->validate([
            'description' => ['nullable', 'string', 'min:10', 'max:2000'],
        ]);

        $availabilityChanged = false;
        $payload = [];

        $newDescription = (string) $this->description;
        $currentDescription = (string) ($this->venue->description ?? '');
        if (trim($newDescription) !== trim($currentDescription)) {
            $this->venue = $this->venueService->updateVenueDescription(
                $this->venue,
                $newDescription,
                Auth::user(),
                $justification
            );
        }
        $this->description = (string) ($this->venue->description ?? $newDescription);

        // Only persist operating hours if the incoming state differs from current DB values
        $payload = $this->normalizeAvailabilityInput();
        $currentAvailability = $this->venueAvailabilityService
            ->listByVenueId((int) $this->venue->id)
            ->map(fn($slot) => [
                'day' => (string) $slot->day,
                'opens_at' => substr((string) $slot->opens_at, 0, 5),
                'closes_at' => substr((string) $slot->closes_at, 0, 5),
            ])
            ->sortBy('day')
            ->values()
            ->all();
        $incomingAvailability = collect($payload)
            ->map(fn($slot) => [
                'day' => (string) $slot['day'],
                'opens_at' => (string) $slot['opens_at'],
                'closes_at' => (string) $slot['closes_at'],
            ])
            ->sortBy('day')
            ->values()
            ->all();
        if ($incomingAvailability !== $currentAvailability) {
            $this->venueService->updateVenueOperatingHours($this->venue, $payload, Auth::user(), $justification);
        }

        $this->refreshAvailabilityForm();

        $this->dispatchGreenToast('Venue details updated.');
    }

    /**
     * Persists requirement rows after validation.
     *
     * @param string $justification
     * @return void
     */
    protected function performSaveRequirements(string $justification): void
    {
        $this->authorize('update-availability', $this->venue);
        $this->authorize('update-requirements', $this->venue);

        if (empty($this->rows)) {
            $this->venueService->updateOrCreateVenueRequirements(
                $this->venue,
                [],
                Auth::user(),
                $justification
            );

            $this->dispatchGreenToast('All requirements have been cleared.');
            $this->refreshRequirements();

            return;
        }

        foreach ($this->rows as $i => &$row) {
            $row['position'] = $i;
        }
        unset($row);

        $this->validate([
            'rows'               => 'array|min:1',
            'rows.*.name'        => 'required|string|max:255',
            'rows.*.description' => 'nullable|string|max:2000',
            'rows.*.hyperlink'   => 'nullable|url|max:2048',
            'rows.*.position'    => 'integer|min:0',
        ], [], [
            'rows.*.name'      => 'requirement name',
            'rows.*.hyperlink' => 'document link',
        ]);

        $this->venueService->updateOrCreateVenueRequirements(
            $this->venue,
            $this->rows,
            Auth::user(),
            $justification
        );

        $this->dispatchGreenToast('Venue requirements saved.');
        $this->refreshRequirements();
    }

    /**
     * Clears all requirements with justification and updates state.
     *
     * @param string $justification
     * @return void
     */
    protected function performClearRequirements(string $justification): void
    {
        $this->authorize('update-requirements', $this->venue);

        $this->venueService->updateOrCreateVenueRequirements(
            $this->venue,
            [],
            Auth::user(),
            $justification
        );

        $this->rows = [];

        $this->dispatchGreenToast('All requirements have been cleared.');
        $this->updateRequirementsSnapshot();
    }

    /**
     * Removes a requirement row locally and reindexes positions.
     *
     * @param string $uuid
     * @return void
     */
    private function applyRemoveRow(string $uuid): void
    {
        foreach ($this->rows as $i => $row) {
            if (($row['uuid'] ?? null) === $uuid) {
                array_splice($this->rows, $i, 1);
                break;
            }
        }

        foreach ($this->rows as $i => &$row) {
            $row['position'] = $i;
        }
        unset($row);

        $this->recalculateRequirementsDirty();
    }

    /**
     * Helper to dispatch a success toast with consistent styling.
     *
     * @param string $message
     * @return void
     */
    private function dispatchGreenToast(string $message): void
    {
        $this->dispatch(
            'toast',
            message: $message,
            className: 'text-bg-success border-0 shadow-lg rounded-3',
            delay: 3200
        );
    }

    /**
     * Indicates whether venue details have unsaved changes.
     *
     * @return bool
     */
    public function getDetailsDirtyProperty(): bool
    {
        return $this->detailsDirtyFlag;
    }

    /**
     * Stores the current details snapshot and clears dirty flag.
     *
     * @return void
     */
    protected function updateDetailsSnapshot(): void
    {
        $this->detailsSnapshot  = $this->snapshotDetails();
        $this->detailsDirtyFlag = false;
    }

    /**
     * Generates a hash of description and availability form state.
     *
     * @return string
     */
    protected function snapshotDetails(): string
    {
        return md5(serialize([
            'description' => (string) $this->description,
            'availability' => $this->normalizedAvailabilityState(),
        ]));
    }

    /**
     * Generates a hash for availability state only.
     *
     * @return string
     */
    protected function snapshotAvailability(): string
    {
        return md5(serialize($this->normalizedAvailabilityState()));
    }

    /**
     * Indicates whether requirements have unsaved changes.
     *
     * @return bool
     */
    public function getRequirementsDirtyProperty(): bool
    {
        return $this->requirementsDirtyFlag;
    }

    /**
     * Stores the current requirements snapshot and clears dirty flag.
     *
     * @return void
     */
    protected function updateRequirementsSnapshot(): void
    {
        $this->requirementsSnapshot = $this->snapshotRows($this->rows);
        $this->requirementsDirtyFlag = false;
    }

    /**
     * Creates a hash of normalized requirement rows.
     *
     * @param array $rows
     * @return string
     */
    protected function snapshotRows(array $rows): string
    {
        return md5(serialize($this->normalizedRequirementRows($rows)));
    }

    /**
     * Normalizes availability form state for comparison/persistence.
     *
     * @return array<string,array{enabled:bool,opens_at:string,closes_at:string}>
     */
    protected function normalizedAvailabilityState(): array
    {
        $state = [];

        foreach (self::DAYS_OF_WEEK as $day) {
            $row = $this->availabilityForm[$day] ?? [];
            $state[$day] = [
                'enabled'   => (bool) ($row['enabled'] ?? false),
                'opens_at'  => (string) ($row['opens_at'] ?? ''),
                'closes_at' => (string) ($row['closes_at'] ?? ''),
            ];
        }

        return $state;
    }

    /**
     * Normalizes requirement rows for deterministic hashing and validation.
     *
     * @param array $rows
     * @return array<int,array{id:?int,name:string,description:string,hyperlink:string,position:int}>
     */
    protected function normalizedRequirementRows(array $rows): array
    {
        $normalized = [];

        foreach ($rows as $row) {
            $normalized[] = [
                'id'          => array_key_exists('id', $row) && $row['id'] !== null ? (int) $row['id'] : null,
                'name'        => (string) ($row['name'] ?? ''),
                'description' => (string) ($row['description'] ?? ''),
                'hyperlink'   => (string) ($row['hyperlink'] ?? ''),
                'position'    => (int) ($row['position'] ?? 0),
            ];
        }

        usort($normalized, function (array $a, array $b): int {
            return ($a['position'] <=> $b['position'])
                ?: (($a['id'] ?? 0) <=> ($b['id'] ?? 0))
                ?: strcmp($a['name'], $b['name']);
        });

        return array_values($normalized);
    }

    /**
     * Updates details dirty flag based on snapshot comparison.
     *
     * @return void
     */
    protected function recalculateDetailsDirty(): void
    {
        $this->detailsDirtyFlag = $this->snapshotDetails() !== $this->detailsSnapshot;
    }

    /**
     * Updates requirements dirty flag based on snapshot comparison.
     *
     * @return void
     */
    protected function recalculateRequirementsDirty(): void
    {
        $this->requirementsDirtyFlag = $this->snapshotRows($this->rows) !== $this->requirementsSnapshot;
    }
}
