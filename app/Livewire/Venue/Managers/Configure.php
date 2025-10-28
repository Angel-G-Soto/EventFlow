<?php

namespace App\Livewire\Venue\Managers;

use App\Models\Venue;
use App\Models\UseRequirement;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.header.public')]
class Configure extends Component
{
    public Venue $venue;

    /** @var array<int,array{id?:int, uuid:string, name:string, description:?string, doc_url:?string, position:int}> */
    public array $rows = [];

    /** @var array<int> */
    public array $deleted = [];

    public function mount(Venue $venue): void
    {
        $this->venue = $venue;

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

    public function render()
    {
        return view('livewire.venue.managers.configure');
    }
}



//public function render()
//{
//    return view('livewire.venue.managers.configure');
//}
