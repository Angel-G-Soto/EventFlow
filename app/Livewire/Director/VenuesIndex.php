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
use Livewire\WithPagination;

#[Layout('layouts.app')]
class VenuesIndex extends Component
{
    use WithPagination;

    public string $paginationTheme = 'bootstrap';

  public ?User $selectedEmployee= null;
  public ?int $depID = null;
  public Department $department;
  public ?int $pendingManagerId = null;
  public string $pendingManagerEmail = '';
  public string $pendingManagerDepartment = '';

  #[Validate('required|email:rfc,dns|max:150')]
  public string $email = '';

  #[Validate('required|same:email|max:150')]
  public string $emailConfirmation = '';


  public function openModal(User $employee): void
  {
        $this->selectedEmployee = $employee;
        // tell the browser to show the modal
        $this->dispatch('open-modal', id: 'actionModal');
  }
  public function removeManager()
  {
      $this->authorize('assign-manager', $this->department);

      app(DepartmentService::class)->removeUserFromDepartment($this->department, $this->selectedEmployee);
      $this->reset(['email', 'emailConfirmation']);
      $this->selectedEmployee = null;
      return $this->redirect(route('director.venues.index'), navigate: false);
  }

  public function addManager()
  {
//      dd($this->department, Auth::user()->getRoleNames());
      $this->authorize('assign-manager', $this->department);

      $this->validate();
      $user = app(UserService::class)->findOrCreateUser(email: $this->email);
      $user->loadMissing('department');

      if ($this->needsDepartmentChangeConfirmation($user)) {
          $this->prepareDepartmentChangeConfirmation($user);
          return;
      }

      return $this->completeManagerAssignment($user);

  }

  public function confirmManagerTransfer()
  {
      if (!$this->pendingManagerId) {
          return;
      }

      $user = User::find($this->pendingManagerId);
      if (!$user) {
          $this->resetManagerConfirmation();
          return;
      }

      $user->loadMissing('department');
      return $this->completeManagerAssignment($user);
  }

  public function cancelManagerTransfer(): void
  {
      $this->resetManagerConfirmation();
      $this->dispatch('close-modal', id: 'confirmManagerTransferModal');
      $this->dispatch('open-modal', id: 'emailModal');
  }

  protected function needsDepartmentChangeConfirmation(User $user): bool
  {
      return !empty($user->department_id)
          && $user->department_id !== $this->department->id;
  }

  protected function prepareDepartmentChangeConfirmation(User $user): void
  {
      $this->pendingManagerId = $user->id;
      $this->pendingManagerEmail = $user->email;
      $this->pendingManagerDepartment = $user->department?->name ?? 'another department';
      $this->dispatch('close-modal', id: 'emailModal');
      $this->dispatch('open-modal', id: 'confirmManagerTransferModal');
  }

  protected function completeManagerAssignment(User $user)
  {
      app(DepartmentService::class)->addUserToDepartment($this->department, $user);
      $this->reset(['email', 'emailConfirmation']);
      $this->resetManagerForms();
      $this->dispatch('close-modal', id: 'emailModal');
      $this->dispatch('close-modal', id: 'confirmManagerTransferModal');
      return $this->redirect(route('director.venues.index'), navigate: false);
  }

  protected function resetManagerForms(): void
  {
      $this->email = '';
      $this->selectedEmployee = null;
      $this->resetManagerConfirmation();
  }

  protected function resetManagerConfirmation(): void
  {
      $this->pendingManagerId = null;
      $this->pendingManagerEmail = '';
      $this->pendingManagerDepartment = '';
  }
  public function saveAssign(): void
  {
      $this->authorize('assign-manager', $this->department);

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
      $this->authorize('view-department');

      $this->depID = Auth::user()->department_id;
      $this->department = app(DepartmentService::class)->getDepartmentByID($this->depID);

      $venues = app(DepartmentService::class)->getDepartmentVenues($this->department);
      $employees = app(DepartmentService::class)->getDepartmentManagers($this->department);

    return view('livewire.director.venues-index', compact('venues', 'employees'));
  }
}
