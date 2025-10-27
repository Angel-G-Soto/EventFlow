<?php

namespace App\Livewire\Concerns;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

trait WithPaginationClamping
{
  /** Current page (1-indexed) */
  public int $page = 1;

  /** Rows per page */
  public int $pageSize = 10;

  /**
   * Keep $page within [1, lastPage] after a mutation (delete, bulk delete, etc.).
   * Pass a total count if you already have it; otherwise we’ll try to compute
   * it from $this->filtered() if that method exists.
   */
  protected function clampPageAfterMutation(?int $total = null): void
  {
    if ($total === null) {
      $total = $this->inferTotal();
    }

    $per   = max(1, (int) $this->pageSize);
    $last  = max(1, (int) ceil($total / $per));

    if ($this->page > $last) {
      $this->page = $last;
    }
    if ($this->page < 1) {
      $this->page = 1;
    }
  }

  /**
   * After creating a record, jump to the last page so the user sees the new row.
   * Pass a total if you already have it; otherwise we’ll try to compute it.
   */
  protected function jumpToLastPageAfterCreate(?int $total = null): void
  {
    if ($total === null) {
      $total = $this->inferTotal();
    }

    $per        = max(1, (int) $this->pageSize);
    $this->page = max(1, (int) ceil($total / $per));

    // If your component uses row selection, clear it when page changes
    if (property_exists($this, 'selected') && is_array($this->selected)) {
      $this->selected = [];
    }
  }

  /**
   * Reset to first page when page size changes; also clear selection.
   */
  public function updatedPageSize(): void
  {
    $this->page = 1;

    if (property_exists($this, 'selected') && is_array($this->selected)) {
      $this->selected = [];
    }
  }

  /**
   * Guard against out-of-bounds page when someone manually edits the URL.
   * If you call this and the page changes, re-run your paginate() right after.
   */
  protected function ensurePageInBounds(LengthAwarePaginator $paginator): LengthAwarePaginator
  {
    $last = max(1, (int) $paginator->lastPage());

    if ($this->page > $last) {
      $this->page = $last;
    } elseif ($this->page < 1) {
      $this->page = 1;
    }

    return $paginator;
  }

  /**
   * Try to compute the "total" count from a common source:
   * - filtered() Collection
   * - filtered() LengthAwarePaginator (use ->total())
   * - array
   * - direct int (already a total)
   */
  protected function inferTotal(): int
  {
    if (method_exists($this, 'filtered')) {
      $data = $this->filtered();

      if ($data instanceof LengthAwarePaginator) {
        return (int) $data->total();
      }
      if ($data instanceof Collection) {
        return (int) $data->count();
      }
      if (is_array($data)) {
        return (int) count($data);
      }
      if (is_int($data)) {
        return $data;
      }
    }

    // Fallback: unknown total
    return 0;
  }
}
