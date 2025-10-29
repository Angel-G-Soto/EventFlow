<?php

namespace App\Livewire\Traits;

trait EventEditState
{
  // Edit state
  public ?int $editId = null;

  public string $eTitle = '';
  public string $ePurpose = '';
  public string $eVenue = '';
  public string $eFrom = '';
  public string $eTo = '';
  public int    $eAttendees = 0;
  public string $eCategory = '';
  // Policies
  public bool   $eHandlesFood = false;
  public bool   $eUseInstitutionalFunds = false;
  public bool   $eExternalGuest = false;

  // Organization and student info
  public string $eOrganization = '';
  public string $eAdvisorName = '';
  public string $eAdvisorEmail = '';
  public string $eAdvisorPhone = '';
  public string $eStudentNumber = '';
  public string $eStudentPhone = '';

  // Justification/action for save/delete
  public string $actionType = '';
  public string $justification = '';

  // Reroute target
  public string $rerouteTo = '';

  // Advance target
  public string $advanceTo = '';

  /**
   * Returns true if the current action type is 'delete'.
   */
  public function getIsDeletingProperty(): bool
  {
    return $this->actionType === 'delete';
  }
}
