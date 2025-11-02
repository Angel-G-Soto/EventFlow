<?php

namespace App\Livewire\Traits;

trait VenueEditState
{
    public ?int $editId = null;

    public string $vName = '';
    public string $vDepartment = '';
    public string $vRoom = '';
    public ?int $vCapacity = 0;
    public string $vManager = '';
    public string $vStatus = 'Active';
    public array  $vFeatures = [];

    public array $timeRanges = [];
    /** @var array<int,array{from:string,to:string}> */

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
