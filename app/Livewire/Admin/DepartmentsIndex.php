<?php

namespace App\Livewire\Admin;

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Livewire\Traits\DepartmentFilters;
use App\Services\DepartmentService;

#[Layout('layouts.app')]
class DepartmentsIndex extends Component
{
  // Traits / shared state
  use DepartmentFilters;

  // Properties / backing stores
  // Legacy in-memory store removed; data now comes directly from DB

  // Sorting
  public string $sortField = 'id';
  public string $sortDirection = 'asc';

  // Accessors and Mutators
  /**
   * Dynamic list of department codes for filter dropdown (unique, natural sort).
   *
   * @return array<int,string>
   */
  public function getCodesProperty(): array
  {
    try {
      return app(DepartmentService::class)->listDepartmentCodes();
    } catch (\Throwable $e) {
      return [];
    }
  }


  // Pagination & filter reactions
  /**
   * Navigates to a given page number.
   *
   * @param int $target The target page number.
   *
   * This function will compute bounds from the current filters, and then
   * set the page number to the maximum of 1 and the minimum of the
   * target and the last page number. If the class has a 'selected'
   * property, it will be cleared when the page changes.
   */
  public function goToPage(int $target): void
  {
    $this->page = max(1, $target);
  }

  // Filters: search update reaction
  /**
   * Resets the current page to 1 when the search filter is updated.
   *
   * This function will be called whenever the search filter is updated,
   * and will reset the current page to 1.
   */
  public function applySearch()
  {
    $this->page = 1;
  }

  // Filters: clear/reset
  /**
   * Resets the search filter and the current page to 1.
   *
   * Clears the search filter and resets the current page to 1.
   */
  public function clearFilters(): void
  {
    $this->search = '';
    $this->code = '';
    $this->page = 1;
  }

  /**
   * Toggle or set the active sort column and direction.
   */
  public function sortBy(string $field): void
  {
    if ($field === $this->sortField) {
      $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
      $this->sortField = $field;
      $this->sortDirection = 'asc';
    }
    $this->page = 1;
  }

  // Render
  /**
   * Renders the departments index page.
   *
   * This function renders the departments index page and provides the necessary data
   * to the view. It paginates the filtered collection of departments and ensures
   * that the current page is within the bounds of the paginator. It then
  * returns the view with the paginated data and the visible IDs.
  *
   * @return \Illuminate\Contracts\View\View
   */
  public function render()
  {
      $this->authorize('access-dashboard');

    try {
      $this->validate();
    } catch (\Throwable $e) {
      $empty = collect();
      $paginator = new LengthAwarePaginator($empty, 0, $this->pageSize, 1, [
        'path'  => request()->url(),
        'query' => request()->query(),
      ]);
      $visibleIds = [];
      return view('livewire.admin.departments-index', [
        'rows' => $paginator,
        'visibleIds' => $visibleIds,
        'codes' => $this->codes,
      ]);
    }

    $paginator = $this->departmentsPaginator();
    $visibleIds = $paginator->pluck('id')->all();
    return view('livewire.admin.departments-index', [
      'rows' => $paginator,
      'visibleIds' => $visibleIds,
      'codes' => $this->codes,
    ]);
  }

  protected function departmentsPaginator(): LengthAwarePaginator
  {
    $svc = app(DepartmentService::class);
    $sort = $this->sortField !== '' ? ['field' => $this->sortField, 'direction' => $this->sortDirection] : null;
    $paginator = $svc->paginateDepartmentRows(
      [
        'search' => $this->search,
        'code' => $this->code,
      ],
      $this->pageSize,
      $this->page,
      $sort
    );

    $last = max(1, (int)$paginator->lastPage());
    if ($this->page > $last) {
      $this->page = $last;
      if ((int)$paginator->currentPage() !== $last) {
        $paginator = $svc->paginateDepartmentRows(
          [
            'search' => $this->search,
            'code' => $this->code,
          ],
          $this->pageSize,
          $this->page,
          $sort
        );
      }
    }

    return $paginator;
  }

  /**
   * Validation rules for department filters and pagination.
   *
   * @return array<string, array<int,string>>
   */
  protected function rules(): array
  {
    return [
      'search'   => ['nullable', 'string', 'max:255'],
      'code'     => ['nullable', 'string', 'max:50'],
      'pageSize' => ['integer', 'in:10,25,50'],
    ];
  }

}
