<?php

namespace App\Livewire\Traits;

trait EventFilters
{
  // Text filters
  public string $search = '';
  public string $status = '';
  public string $venue = '';
  public ?string $from = null;
  public ?string $to   = null;
  public string $requestor = '';
  public string $category  = '';
  public string $organization = '';

  // Paging
  public int $page = 1;
  public int $pageSize = 10;
}
