<?php

namespace App\Livewire\Traits;

trait EventFilters
{
  // Text filters
  public string $search = '';
  public string $status = '';
  public string $department = '';
  public string $venue = '';
  public ?string $from = null;
  public ?string $to   = null;
  public string $requestor = '';
  public string $category  = '';

  // Paging
  public int $page = 1;
  public int $pageSize = 10;
}
