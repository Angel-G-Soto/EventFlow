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

    /** @var array<int,array{id?:int, uuid:string, name:string, description:?string, user.index:?string, position:int}> */
    public array $rows = [];

    /** @var array<int> */
    public array $deleted = [];

    public string $description = '';
    public array $availabilityForm = [];
    public array $weekDays = [];
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
                'hyperlink'     => $r->hyperlink,
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
            'user.index'     => '',
            'position'    => count($this->rows),
        ];
    }
/**
 * SaveAvailability action.
 * @return void
 */

    public function saveAvailability(): void
    {
        $this->authorize('update-availability', $this->venue);

        $this->validate([
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $payload = $this->normalizeAvailabilityInput();

        $this->venue->description = $this->description;
        $this->venue->save();

        app(VenueService::class)->updateVenueOperatingHours($this->venue, $payload, Auth::user());

        $this->venue->refresh();
        $this->availabilityForm = $this->buildAvailabilityForm();

        session()->flash('success', 'Venue details updated.');

        $this->dispatch('notify', type: 'success', message: 'Venue details updated.');
    }
/**
 * RemoveRow action.
 * @param string $uuid
 * @return void
 */

    public function removeRow(string $uuid): void
    {
        foreach ($this->rows as $i => $row) {
            if ($row['uuid'] === $uuid) {
                if (isset($row['id'])) {
                    $this->deleted[] = $row['id']; // mark for deletion on save
                }
                array_splice($this->rows, $i, 1);
                break;
            }
        }

        if (empty($this->rows)) {
            $this->addRow();
        }
    }
    /**
     * Validate input and persist configuration changes.
     * @return void
     */

    public function save(): void
    {
        $this->authorize('update-availability', $this->venue);
        $this->authorize('update-requirements', $this->venue);

        // Normalize positions to current order
        foreach ($this->rows as $i => &$row) {
            $row['position'] = $i;
        }
        unset($row);

        // Validation rules per row
        $this->validate([
            'rows'                 => 'array|min:1',
            'rows.*.name'          => 'required|string|max:255',
            'rows.*.description'   => 'nullable|string|max:2000',
            'rows.*.user.index'       => 'nullable|url|max:2048',
            'rows.*.position'      => 'integer|min:0',
        ], [], [
            'rows.*.name' => 'requirement name',
            'rows.*.user.index' => 'document link',
        ]);

//        dd($this->venue,$this->rows);

        app(VenueService::class)->updateOrCreateVenueRequirements($this->venue, $this->rows, Auth::user());

//        DB::transaction(function () {
//            // Delete removed ones
//            if (!empty($this->deleted)) {
//                UseRequirement::where('venue_id', $this->venue->id)
//                    ->whereIn('id', $this->deleted)
//                    ->delete();
//                $this->deleted = [];
//            }
//
//            // Upsert rows
//            foreach ($this->rows as $row) {
//                if (isset($row['id'])) {
//                    // update existing
//                    UseRequirement::where('id', $row['id'])
//                        ->where('venue_id', $this->venue->id)
//                        ->update([
//                            'name'        => $row['name'],
//                            'description' => $row['description'],
//                            'hyperlink'     => $row['user.index'],
//                            'position'    => $row['position'],
//                        ]);
//                } else {
//                    // create new (ignore completely empty rows)
//                    if (trim($row['name']) !== '' || trim((string) $row['user.index']) !== '' || trim((string) $row['description']) !== '') {
//                        $created = $this->venue->requirements()->create([
//                            'name'        => $row['name'],
//                            'description' => $row['description'],
//                            'hyperlink'     => $row['user.index'],
//                            'position'    => $row['position'],
//                        ]);
//                        // carry the new id back so the row is now "existing"
//                        $this->replaceUuidWithId($row['uuid'], $created->id);
//                    }
//                }
//            }
//        });

        session()->flash('success', 'Venue requirements saved.');
        // Optional: refresh from DB to get cleaned state
        $this->mount($this->venue);
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
}



//public function render()
//{
//    return view('livewire.venue.managers.configure');
//}
