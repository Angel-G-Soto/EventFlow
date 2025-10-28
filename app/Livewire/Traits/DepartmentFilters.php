<?php

namespace App\Livewire\Traits;

trait DepartmentFilters
{
  public string $search = '';
  public string $code = '';
  public int $page = 1;
  public int $pageSize = 10;
}
