<?php

/**
 * Livewire Component: Filters
 *
 * Part of the EventFlow project (Laravel 12 + Livewire 3 + Bootstrap 5).
 * Provides multi-select filters (e.g., organization, category/type, venue)
 * and emits change events or updates bound state for parent components/pages.
 *
 * Responsibilities:
 * - Hold UI state for filter selections and search terms.
 * - Sanitize and serialize filter values (CSV/array) for queries.
 * - Emit Livewire events (or use URL query string) to notify list components.
 *
 * @author  EventFlow
 * @license MIT
 * @since   2025-11-01
 */

namespace App\Livewire\Request\History;

use App\Models\Category;
use App\Models\Event;
use App\Models\Role;
use App\Models\Venue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

class Filters extends Component
{
    public array $roles = [];

//    #[Url(as: 'role', history: true)]
    public string $selectedRole = '';

    public string $searchTitle = '';
    public string $sortDirection = 'desc';

    public function mount(): void
    {
        // Load roles for current user
//        $this->roles = Auth::user()->roles()->where('name', '<>', 'user')->get(['name'])->toArray();
            $this->roles = Role::whereNotIn('name', ['user', 'system-admin', 'department-director'])->get(['name'])->toArray();
    }

    public function apply(): void
    {
//        dd($this->selectedRole);

        $this->dispatch('filters-changed',
            $this->selectedRole,
            $this->searchTitle,
            $this->sortDirection
        );
//        dd($this->selectedRole, $this->searchTitle, $this->sortDirection);
        $this->dispatch('filters-applied');
    }

    public function resetFilters(): void
    {
        $this->selectedRole = '';
        $this->searchTitle = '';
        $this->sortDirection = 'desc';
        $this->apply();
    }

    public function render()
    {
        return view('livewire.request.history.filters');
    }
}
