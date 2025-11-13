<?php

namespace App\Livewire\Auth;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.app')]
class ChooseRole extends Component
{
  /** @var array<int,string> */
  public array $roles = [];  // e.g., ['Department Director','Venue Manager']

  public string $selected = '';

  public function mount(): void
  {
    $user = Auth::user();
    $this->roles = method_exists($user, 'roles')
      ? $user->roles->pluck('name')->all()
      : (array) ($user->roles ?? []);

    // safety: if only one, bounce (defense in depth; controller should've done this already)
    if (count($this->roles) <= 1) {
      redirect()->route('choose-role'); // no-op, controller already handled
    }
  }
  public function select(string $role): void // NEW 
  {
    if (!in_array($role, $this->roles, true)) return;

    session(['active_role' => $role]);

    // centralized role-home mapping
    $route = match ($role) {
      'System Admin'          => 'admin.events',
      'Department Director'   => 'admin.departments',
      'Venue Manager'         => 'admin.venues',
      'DSCA Staff'            => 'admin.events',
      default                 => 'calendar.public',
    };

    $this->redirectRoute($route);
  }

  public function render()
  {
    return view('livewire.auth.choose-role');
  }
}
