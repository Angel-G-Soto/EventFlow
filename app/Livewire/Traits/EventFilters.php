<?php

namespace App\Livewire\Traits;

trait EventFilters
{
  // Text filters
  public string $search = '';
  public string $status = '';
  // Venue filter can be bound to a searchable dropdown (venue id) or a plain text value
  public int|string|null $venue = null;
  public ?string $from = null;
  public ?string $to   = null;
  public string $requestor = '';
  public string $category  = '';

  // Paging
  public int $page = 1;
  public int $pageSize = 10;
}
