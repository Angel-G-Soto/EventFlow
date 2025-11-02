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
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.header.public')]
/**
 * Class Configure
 *
 * Livewire component to configure a venue's requirements and availability hours.
 * Receives a Venue (or ID) on mount and exposes state/methods for the form.
 */
class Configure extends Component
{
/**
 * @var \App\Models\Venue
 */
    public Venue $venue;

    /** @var array<int,array{id?:int, uuid:string, name:string, description:?string, doc_url:?string, position:int}> */
    public array $rows = [];

    /** @var array<int> */
    public array $deleted = [];

    /** Public state for the time inputs (HH:MM) */
    public ?string $opens_at = null;
/**
 * @var ?string
 */
    public ?string $closes_at = null;
/**
 * Initialize component state from a given Venue or ID.
 * @param Venue $venue
 * @return void
 */

    public function mount(Venue $venue): void
    {
        $this->venue = $venue;
        $this->opens_at  = $venue->opening_time ? substr($venue->opening_time, 0, 5) : '';
        $this->closes_at = $venue->closing_time ? substr($venue->closing_time, 0, 5) : '';


        $this->rows = $venue->requirements()->get()->map(function (UseRequirement $r) {
            return [
                'id'          => $r->id,
                'uuid'        => (string) Str::uuid(), // stable wire:key per row
                'name'        => $r->name,
                'description' => $r->description,
                'doc_url'     => $r->hyperlink,
                'position'    => $r->position ?? 0,
            ];
        })->values()->all();

        if (empty($this->rows)) {
            $this->addRow(); // start with one empty row
        }
    }
/**
 * Rules action.
 * @return array
 */

    protected function rules(): array
    {
        // When is_24h is true, both nullable. Otherwise require HH:MM and closes after opens.
        return [
            'opens_at' => ['required', 'date_format:H:i'],
            'closes_at'=> ['required', 'date_format:H:i', 'after:opens_at'],
        ];
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
            'doc_url'     => '',
            'position'    => count($this->rows),
        ];
    }
/**
 * SaveAvailability action.
 * @return void
 */

    public function saveAvailability(): void
    {
        $this->validate();

        $this->venue->update([
            'opening_time'  => $this->opens_at,   // DB TIME will append :00 seconds
            'closing_time' => $this->closes_at,
        ]);

        $this->dispatch('notify', type: 'success', message: 'Availability updated.');
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
            'rows.*.doc_url'       => 'nullable|url|max:2048',
            'rows.*.position'      => 'integer|min:0',
        ], [], [
            'rows.*.name' => 'requirement name',
            'rows.*.doc_url' => 'document link',
        ]);

        DB::transaction(function () {
            // Delete removed ones
            if (!empty($this->deleted)) {
                UseRequirement::where('venue_id', $this->venue->id)
                    ->whereIn('id', $this->deleted)
                    ->delete();
                $this->deleted = [];
            }

            // Upsert rows
            foreach ($this->rows as $row) {
                if (isset($row['id'])) {
                    // update existing
                    UseRequirement::where('id', $row['id'])
                        ->where('venue_id', $this->venue->id)
                        ->update([
                            'name'        => $row['name'],
                            'description' => $row['description'],
                            'hyperlink'     => $row['doc_url'],
                            'position'    => $row['position'],
                        ]);
                } else {
                    // create new (ignore completely empty rows)
                    if (trim($row['name']) !== '' || trim((string) $row['doc_url']) !== '' || trim((string) $row['description']) !== '') {
                        $created = $this->venue->requirements()->create([
                            'name'        => $row['name'],
                            'description' => $row['description'],
                            'hyperlink'     => $row['doc_url'],
                            'position'    => $row['position'],
                        ]);
                        // carry the new id back so the row is now "existing"
                        $this->replaceUuidWithId($row['uuid'], $created->id);
                    }
                }
            }
        });

        session()->flash('success', 'Venue requirements saved.');
        // Optional: refresh from DB to get cleaned state
        $this->mount($this->venue);
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
        return view('livewire.venue.managers.configure');
    }
}



//public function render()
//{
//    return view('livewire.venue.managers.configure');
//}
