<?php

namespace App\Livewire\Traits;

trait EventEditState
{
  // Edit state
  public ?int $editId = null;

  public string $eTitle = '';
  public string $ePurpose = '';
  public string $eNotes = '';
  public string $eDepartment = '';
  public string $eVenue = '';
  public string $eFrom = '';
  public string $eTo = '';
  public int    $eAttendees = 0;
  public string $eCategory = '';
  public bool   $ePolicyAlcohol = false;
  public bool   $ePolicyCurfew  = false;

  // Justification/action for save/delete
  public string $actionType = '';
  public string $justification = '';

  /**
   * Returns true if the current action type is 'delete'.
   */
  public function getIsDeletingProperty(): bool
  {
    return $this->actionType === 'delete';
  }
}
