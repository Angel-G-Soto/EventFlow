<?php

namespace App\Livewire\Traits;

trait UserEditState
{
  public ?int $editId = null;
  public string $editName = '';
  public string $editEmail = '';
  public string $editDepartment = '';
  public array $editRoles = [];

  public string $justification = '';
  public string $actionType = '';

  /**
   * Returns true if the current action type is 'delete'.
   *
   * This property is used to conditionally render delete confirmation modals.
   *
   * @return bool True if the action type is 'delete', false otherwise.
   */
  public function getIsDeletingProperty(): bool
  {
    return $this->actionType === 'delete';
  }
}
