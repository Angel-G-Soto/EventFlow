<?php

namespace App\Livewire\Traits;

trait UserFilters
{
  public string $search = '';
  public string $role   = '';
  public int $page      = 1;
  public int $pageSize  = 10;
}
