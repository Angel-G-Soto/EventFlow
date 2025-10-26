<?php

namespace App\Livewire\Concerns;

trait TableSelection
{
  /** @var array<int,bool> */
  public array $selected = [];

  /**
   * Toggle a row as selected in the component's selection.
   *
   * @param int $rowId The ID of the row to toggle.
   * @param bool $checked Whether the row should be selected (true) or deselected (false).
   */
  public function toggleSelect(int $rowId, bool $checked): void
  {
    if ($checked) $this->selected[$rowId] = true;
    else unset($this->selected[$rowId]);
  }

  /**
   * Select all rows on the current page.
   *
   * @param bool $checked Whether all rows on the page should be selected (true) or deselected (false).
   * @param array $ids The IDs of all rows on the current page.
   */
  public function selectAllOnPage(bool $checked, array $ids): void
  {
    foreach ($ids as $id) {
      if ($checked) $this->selected[$id] = true;
      else unset($this->selected[$id]);
    }
  }

  /**
   * Resets the component's selection when the page is updated.
   *
   * This method is called whenever the component's page is updated.
   */
  public function updatedPage(): void
  {
    $this->selected = [];
  }
}
