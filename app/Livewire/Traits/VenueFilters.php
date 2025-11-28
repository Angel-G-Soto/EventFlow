<?php

namespace App\Livewire\Traits;

trait VenueFilters
{
    public string $search = '';
    public string $department = '';
    public  $capMin = null;

    public int $page = 1;
    public int $pageSize = 10;
}
