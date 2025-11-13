<?php

namespace App\Livewire\Traits;

trait EventEditState
{
  // Edit state
  public ?int $editId = null;

  public string $eTitle = '';
  public string $ePurpose = '';
  public string $eVenue = '';
  public int $eVenueId = 0;
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
  public string $eStudentNumber = '';
  public string $eStudentPhone = '';
  public string $eStatus = '';
  public array $eDocuments = [];

  // Justification/action for save/delete
  public string $actionType = '';
  public string $justification = '';


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
