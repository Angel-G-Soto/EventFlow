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
use App\Models\UseRequirement;
use App\Services\VenueService;
use Illuminate\Support\Facades\Auth;
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

    /** @var array<int> */
    public array $deleted = [];

    public string $description = '';
    public array $availabilityForm = [];
    public array $weekDays = [];

    public ?string $confirmDeleteUuid = null;
    public string $confirmDeleteAction = '';
    public string $confirmDeleteMessage = '';
    public string $justification = '';
    public string $pendingAction = '';
    public ?string $pendingUuid = null;
/**
 * Initialize component state from a given Venue or ID.
 * @param Venue $venue
 * @return void
 */

    public function mount(Venue $venue): void
    {
        $this->venue = $venue->load('availabilities');
        $this->description = (string)($this->venue->description ?? '');
        $this->availabilityForm = $this->buildAvailabilityForm();
        $this->weekDays = self::DAYS_OF_WEEK;

        $req = app(VenueService::class)->getVenueRequirements($venue->id);
        $this->rows = $req->map(function (UseRequirement $r) {
            return [
                'id'          => $r->id,
                'uuid'        => (string) Str::uuid(), // stable wire:key per row
                'name'        => $r->name,
                'description' => $r->description,
                'hyperlink'   => (string) ($r->hyperlink ?? ''),
                'position'    => $r->position ?? 0,
            ];
        })->values()->all();



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
        // Update in-memory rows + deleted list
        foreach ($this->rows as $i => $row) {
            if (($row['uuid'] ?? null) === $uuid) {
                if (isset($row['id'])) {
                    $this->deleted[] = $row['id']; // mark for deletion semantics
                }
                array_splice($this->rows, $i, 1);
                break;
            }
        }

        if (empty($this->rows)) {
            $this->addRow();
        }

        // Persist requirements change immediately so navigation won't restore the row
        $this->authorize('update-requirements', $this->venue);

        // Normalize positions before saving
        foreach ($this->rows as $i => &$row) {
            $row['position'] = $i;
        }
        unset($row);

        app(VenueService::class)->updateOrCreateVenueRequirements($this->venue, $this->rows, Auth::user());

        // Refresh component state from DB (rebuild rows/uuids/availability)
        $this->mount($this->venue);
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
        $this->confirmDeleteMessage = "Remove {$labelText}? This will delete it immediately.";

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
            $this->startJustification('remove_requirement', $this->confirmDeleteUuid);
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

        app(VenueService::class)->updateOrCreateVenueRequirements($this->venue, [], Auth::user());

        $this->rows = [];
        $this->deleted = [];
        $this->addRow();

        session()->flash('success', 'All requirements for this venue have been cleared.');
        $this->dispatch('notify', type: 'success', message: 'All requirements have been cleared.');
    }
/**
 * ReplaceUuidWithId action.
 * @param string $uuid
 * @param int $id
 * @return void
 */

    private function replaceUuidWithId(string $uuid, int $id): void
    {
        foreach ($this->rows as &$row) {
            if ($row['uuid'] === $uuid) {
                $row['id'] = $id;
                break;
            }
        }
        unset($row);
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

    protected function buildAvailabilityForm(): array
    {
        $existing = $this->venue->availabilities->keyBy('day');
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
        } elseif ($action === 'remove_requirement' && $uuid) {
            $this->performRemoveRequirement($uuid, $justification);
        }
    }

    protected function performSaveAvailability(string $justification): void
    {
        $this->authorize('update-availability', $this->venue);

        $this->validate([
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $payload = $this->normalizeAvailabilityInput();

        $this->venue->description = $this->description;
        $this->venue->save();

        app(VenueService::class)->updateVenueOperatingHours($this->venue, $payload, Auth::user(), $justification);

        $this->venue->refresh();
        $this->availabilityForm = $this->buildAvailabilityForm();

        session()->flash('success', 'Venue details updated.');

        $this->dispatch('notify', type: 'success', message: 'Venue details updated.');
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

        app(VenueService::class)->updateOrCreateVenueRequirements($this->venue, $this->rows, Auth::user());

        session()->flash('success', 'Venue requirements saved.');
        $this->mount($this->venue);
    }

    protected function performClearRequirements(string $justification): void
    {
        $this->authorize('update-requirements', $this->venue);

        app(VenueService::class)->updateOrCreateVenueRequirements($this->venue, [], Auth::user());

        $this->rows = [];
        $this->deleted = [];
        $this->addRow();

        session()->flash('success', 'All requirements for this venue have been cleared.');
        $this->dispatch('notify', type: 'success', message: 'All requirements have been cleared.');
    }

    protected function performRemoveRequirement(string $uuid, string $justification): void
    {
        $this->authorize('update-requirements', $this->venue);

        $this->applyRemoveRow($uuid);

        app(VenueService::class)->updateOrCreateVenueRequirements($this->venue, $this->rows, Auth::user());

        $this->mount($this->venue);
    }

    private function applyRemoveRow(string $uuid): void
    {
        foreach ($this->rows as $i => $row) {
            if (($row['uuid'] ?? null) === $uuid) {
                if (isset($row['id'])) {
                    $this->deleted[] = $row['id'];
                }
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
}



//public function render()
//{
//    return view('livewire.venue.managers.configure');
//}
