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
  public string $deleteType = 'soft'; // 'soft' or 'hard'

  public function getIsDeletingProperty(): bool
  {
    return $this->actionType === 'delete';
  }
  // Removed: getIsBulkDeletingProperty no longer needed after bulk actions removal
}
