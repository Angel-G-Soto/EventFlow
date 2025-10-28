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
    public string $deleteType = 'soft'; // 'soft' or 'hard'

    public function getIsDeletingProperty(): bool
    {
        return $this->actionType === 'delete';
    }
    // Removed: getIsBulkDeletingProperty no longer needed after bulk actions removal
}
