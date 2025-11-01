<?php

namespace App\Livewire\Auth;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ChooseRole extends Component
{
  /** @var array<int,string> */
  public array $roles = [];  // e.g., ['Department Director','Venue Manager']

  public string $selected = '';

  public function mount(): void
  {
    // Mock user roles for now; replace with $request->user()->roles()->pluck('name')->all() later
    $this->roles = session('mock_user_roles', ['Department Director', 'Administrator']); // change as needed

    if (count($this->roles) === 1) {
      $this->selected = $this->roles[0];
      $this->continue();
    }
  }

  public function continue()
  {
    $map = [
      'Department Director' => route('director.venues'),
      //'Venue Manager'       => route('manager.venues', [], false) ?: url('/manager/venues'),
      'DSCA Staff'          => url('/dsca'),
      'Administrator'       => url('/admin/events'),
      'Student Org Rep'     => url('/org'),
      'Student Org Advisor' => url('/advisor'),
      'Dean of Administration' => url('/dean'),
    ];

    $dest = $map[$this->selected] ?? url('/');
    return redirect()->to($dest);
  }

  public function render()
  {
    return view('livewire.auth.choose-role');
  }
}
