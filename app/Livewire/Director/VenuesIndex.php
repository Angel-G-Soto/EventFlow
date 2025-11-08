<?php

namespace App\Livewire\Director;

use App\Models\Department;
use App\Models\User;
use App\Services\DepartmentService;
use App\Services\UserService;
use App\Services\VenueService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Attributes\Validate;

#[Layout('layouts.app')]
class VenuesIndex extends Component
{

  public ?User $selectedEmployee= null;
  public ?int $depID = null;
  public Department $department;

  #[Validate('required|email:rfc,dns|max:255')]
  public string $email = '';

  public function boot(){
        $this->depID = Auth::user()->department_id;
        $this->department = app(DepartmentService::class)->getDepartmentByID($this->depID);

    }

  public function openModal(User $employee): void
  {
        $this->selectedEmployee = $employee;
        // tell the browser to show the modal
        $this->dispatch('open-modal', id: 'actionModal');
  }
  public function removeManager()
  {

      app(DepartmentService::class)->removeUserFromDepartment($this->department, $this->selectedEmployee);
      $this->email = '';
      $this->selectedEmployee = null;
      return $this->redirect(route('director.venues.index'), navigate: false);
  }

  public function addManager()
  {

      $this->validate();
      $user = app(UserService::class)->findOrCreateUser(email: $this->email);
      app(DepartmentService::class)->addUserToDepartment($this->department, $user);
      $this->email = '';
      $this->selectedEmployee = null;
      return $this->redirect(route('director.venues.index'), navigate: false);

  }
  public function saveAssign(): void
  {
    $this->validate([
      'assignManager' => 'required|email', // later: user selector of role "Venue Manager"
    ]);

    $user = app(UserService::class)->findOrCreateUser($this->assignManager);
    app(VenueService::class)->assignManager(app(VenueService::class)->findByID($this->assignId), $user, Auth::user());

    $this->dispatch('bs:close', id: 'assignManager');
    $this->dispatch('toast', message: 'Venue manager assigned');
    $this->reset(['assignId', 'assignManager']);
  }

  public function render()
  {

      $venues = app(DepartmentService::class)->getDepartmentVenues($this->department);
      $employees = app(DepartmentService::class)->getVenueManagers(Auth::id());

    return view('livewire.director.venues-index', compact('venues', 'employees'));
  }
}
