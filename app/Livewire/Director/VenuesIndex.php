<?php

namespace App\Livewire\Director;

use App\Livewire\Traits\HasJustification;
use App\Models\Department;
use App\Models\User;
use App\Services\DepartmentService;
use App\Services\UserService;
use App\Services\VenueService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class VenuesIndex extends Component
{
    use WithPagination;
    use HasJustification;

    public string $paginationTheme = 'bootstrap';

  public ?int $depID = null;
  public Department $department;
  public ?int $pendingManagerId = null;
  public string $pendingManagerEmail = '';
  public string $pendingManagerDepartment = '';
  public string $justification = '';
  public string $pendingAction = '';
  public ?int $pendingActionUserId = null;

  public string $email = '';

  public string $emailConfirmation = '';

  protected array $rules = [
      'email'             => 'required|email:rfc,dns|max:150',
      'emailConfirmation' => 'required|same:email|max:150',
  ];


  public function requestManagerRemoval(int $userId): void
  {
      $this->authorize('assign-manager', $this->department);

      $user = User::find($userId);
      if (!$user) {
          return;
      }

      $this->openJustificationModal('remove_manager', $user->id);
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

      $this->startAddManagerJustification($user);

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
      $this->startAddManagerJustification($user, ['confirmManagerTransferModal']);
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

  protected function startAddManagerJustification(User $user, array $additionalModalsToClose = []): void
  {
      $this->resetManagerConfirmation();

      $modals = array_unique(array_merge(['emailModal'], $additionalModalsToClose));

      $this->openJustificationModal('add_manager', $user->id, $modals);
  }

  protected function openJustificationModal(string $action, int $userId, array $closeModals = []): void
  {
      $this->pendingAction = $action;
      $this->pendingActionUserId = $userId;
      $this->justification = '';

      $this->resetErrorBag(['justification']);

      foreach ($closeModals as $modalId) {
          $this->dispatch('close-modal', id: $modalId);
      }

      $this->dispatch('open-modal', id: 'departmentJustificationModal');
  }

  public function confirmJustification()
  {
      $this->validate([
          'justification' => $this->justificationRules(true),
      ], [], [
          'justification' => 'justification',
      ]);

      $action = $this->pendingAction;
      $userId = $this->pendingActionUserId;
      $justification = $this->justification;

      $this->dispatch('close-modal', id: 'departmentJustificationModal');
      $this->resetJustificationState();

      return match ($action) {
          'add_manager'    => $this->completeManagerAssignmentById($userId, $justification),
          'remove_manager' => $this->completeManagerRemovalById($userId, $justification),
          default          => null,
      };
  }

  protected function resetJustificationState(): void
  {
      $this->pendingAction = '';
      $this->pendingActionUserId = null;
      $this->justification = '';
  }

  protected function completeManagerAssignmentById(?int $userId, string $justification)
  {
      if (!$userId) {
          return;
      }

      $user = User::find($userId);
      if (!$user) {
          return;
      }

      $user->loadMissing('department');

      return $this->completeManagerAssignment($user, $justification);
  }

  protected function completeManagerAssignment(User $user, string $justification)
  {
      $this->authorize('assign-manager', $this->department);

      app(DepartmentService::class)->addUserToDepartment($this->department, $user, $justification);
      $this->reset(['email', 'emailConfirmation']);
      $this->resetManagerForms();
      $this->dispatch('close-modal', id: 'emailModal');
      $this->dispatch('close-modal', id: 'confirmManagerTransferModal');

      return $this->redirect(route('director.venues.index'), navigate: false);
  }

  protected function completeManagerRemovalById(?int $userId, string $justification)
  {
      if (!$userId) {
          return;
      }

      $user = User::find($userId);
      if (!$user) {
          return;
      }

      return $this->finalizeManagerRemoval($user, $justification);
  }

  protected function finalizeManagerRemoval(User $user, string $justification)
  {
      $this->authorize('assign-manager', $this->department);

      app(DepartmentService::class)->removeUserFromDepartment($this->department, $user, $justification);
      $this->reset(['email', 'emailConfirmation']);

      return $this->redirect(route('director.venues.index'), navigate: false);
  }

  protected function resetManagerForms(): void
  {
      $this->email = '';
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
