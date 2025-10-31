<?php

namespace App\Livewire\Venue\Director;

use App\Models\Department;
use App\Models\Venue;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Illuminate\Validation\Rule;
use Livewire\WithPagination;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;



class Venues extends Component
{
    use WithPagination;

    public int $perPage = 10;

    /** If provided, we use this instead of auth()->user()->department_id */
    public ?int $departmentId = null;

    public array $selectedManagers = [];
    public $managers;

    public function mount(?int $departmentId = null): void
    {
        $this->departmentId = $departmentId ?? $this->departmentId
            ?? auth()->user()?->department_id;

        if (! $this->departmentId) {
            abort(403, 'Department is required.');
        }

        $this->managers = User::query()
            ->where('department_id', $this->departmentId)
            ->when(Schema::hasColumn('users','role'), fn ($q) => $q->where('role', 'venue_manager'))
            ->orderBy('first_name')
            ->get(['id','first_name', 'last_name']);
    }

    public function updateManager(int $venueId): void
    {
        $this->validateOnly("selectedManagers.$venueId");

        $venue = Venue::query()
            ->where('department_id', $this->departmentId)
            ->findOrFail($venueId);

        $venue->manager_id = $this->selectedManagers[$venueId] ?? null;
        $venue->save();

        session()->flash('message', 'Manager updated successfully.');
    }

    public function rules(): array
    {
        return [
            'selectedManagers.*' => [
                'nullable','integer',
                \Illuminate\Validation\Rule::exists('users', 'id')
                    ->where(fn ($q) => $q->where('department_id', $this->departmentId)
                        ->when(Schema::hasColumn('users','role'), fn ($q) => $q->where('role','venue_manager'))),
            ],
        ];
    }

    public function render()
    {
        $venues = Venue::query()
            ->where('department_id', $this->departmentId)
            ->with('manager:id,first_name,last_name')
            ->orderBy('name')
            ->paginate($this->perPage)
            ->onEachSide(1);

        return view('livewire.venue.director.venues', compact('venues'));
    }
}





//public function render()
//{
//    return view('livewire.venue.director.venues');
//}
