<?php

namespace App\Livewire\Admin;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Livewire\Traits\DepartmentFilters;
use App\Livewire\Traits\DepartmentEditState;
use App\Services\DepartmentService;

#[Layout('layouts.app')]
class DepartmentsIndex extends Component
{
  // Traits / shared state
  use DepartmentFilters, DepartmentEditState;

  // Properties / backing stores
  // Legacy in-memory store removed; data now comes directly from DB

  // Sorting
  public string $sortField = '';
  public string $sortDirection = 'asc';

  // Accessors and Mutators
  /**
   * Dynamic list of department codes for filter dropdown (unique, natural sort).
   *
   * @return array<int,string>
   */
  public function getCodesProperty(): array
  {
    $codes = $this->allDepartments()
      ->pluck('code')
      ->filter(fn($v) => is_string($v) && trim($v) !== '')
      ->map(fn($v) => strtoupper(trim($v)))
      ->all();

    $map = [];
    foreach ($codes as $c) {
      $k = mb_strtolower($c);
      if (!isset($map[$k])) $map[$k] = $c;
    }
    $values = array_values($map);
    usort($values, fn($a, $b) => strnatcasecmp($a, $b));
    return $values;
  }

  // Lifecycle
  /**
   * Initialize component; no in-memory preload when using DB.
   */
  public function mount(): void
  {
    // No preload required; queries read directly from DB
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
    // compute bounds from current filters
    $total = $this->filtered()->count();
    $last  = max(1, (int) ceil($total / max(1, $this->pageSize)));

    $this->page = max(1, min($target, $last));

    // selection removed
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

  // Edit/Create workflows
  /**
   * Resets the edit fields to their default values and opens the edit department modal.
   *
   * This function will reset the edit fields to their default values and open the edit department modal.
   */
  public function openCreate(): void
  {
    $this->reset(['editId', 'dName', 'dCode', 'dDirector']);
    $this->dispatch('bs:open', id: 'deptModal');
  }

  /**
   * Opens the edit department modal for the given department ID.
   *
   * This function will open the edit department modal and populate the fields with the department's information.
   * If the department ID does not exist, the function will return without performing any action.
   *
   * @param int $id The ID of the department to edit
   */
  public function openEdit(int $id): void
  {
    $departments = $this->filtered()->firstWhere('id', $id);
    if (!$departments) return;
    $this->editId = $departments['id'];
    $this->dName = $departments['name'];
    $this->dCode = $departments['code'];
    $this->dDirector = $departments['director'];
    $this->dispatch('bs:open', id: 'deptModal');
  }

  // Persist edits / session writes
  /**
   * Opens the justification modal for saving the department data.
   *
   * This function is called when the user wants to save the department data.
   * It sets the actionType to 'save' and then opens the justification modal.
   */
  public function save(): void
  {
    // Basic field validation (name/code required)
    $this->validate([
      'dName' => ['required', 'string', 'max:150'],
      'dCode' => ['required', 'string', 'max:50'],
    ]);
    $this->actionType = 'save';
    $this->dispatch('bs:open', id: 'deptJustify');
  }

  /**
   * Confirms the save action and updates the session with the new/edited department data.
   * If the department is being created, it updates the new_departments session.
   * If the department is being edited, it updates the edited_departments session.
   * Finally, it dispatches events to close the justification modal, edit department modal, and show a toast message with a success message.
   */
  public function confirmSave(): void
  {
    $this->validateJustification();

    // Use DepartmentService to upsert the department
    try {
      app(DepartmentService::class)->updateOrCreateDepartment([
        [
          'name' => (string)$this->dName,
          'code' => (string)$this->dCode,
        ]
      ]);
    } catch (\Throwable $e) {
      // Do not fallback to direct Eloquent writes; surface an error instead
      $this->addError('justification', 'Unable to save department.');
      return;
    }

    $this->dispatch('bs:close', id: 'deptJustify');
    $this->dispatch('bs:close', id: 'deptModal');
    $this->dispatch('toast', message: 'Department saved');
    $this->reset(['justification', 'actionType', 'editId', 'dName', 'dCode', 'dDirector']);
  }

  // Delete workflows
  /**
   * Opens the justification modal for deleting the department with the given ID.
   * This function should be called when the user wants to delete a department.
   * It sets the currently edited department ID and sets actionType to 'delete', then opens the justification modal.
   * @param int $id The ID of the department to delete
   */
  public function delete(int $id): void
  {
    $this->editId = $id;
    $this->actionType = 'delete';
    $this->dispatch('bs:open', id: 'deptConfirm');
  }

  /**
   * Proceeds from the delete confirmation to the justification modal.
   */
  public function proceedDelete(): void
  {
    $this->dispatch('bs:close', id: 'deptConfirm');
    $this->dispatch('bs:open', id: 'deptJustify');
  }

  /**
   * Confirms the deletion of a department.
   *
   * This function will validate the justification entered by the user, and then delete the department with the given ID.
   * After deletion, it clamps the current page to prevent the page from becoming out of bounds.
   * Finally, it shows a toast message indicating the department was deleted.
   */
  public function confirmDelete(): void
  {
    if ($this->editId) {
      $this->validateJustification();
      try {
        app(DepartmentService::class)->deleteDepartment((int)$this->editId);
      } catch (\Throwable $e) {
        // Do not fallback to direct Eloquent deletes; surface an error instead
        $this->addError('justification', 'Unable to delete department.');
        return;
      }
    }
    $this->dispatch('bs:close', id: 'deptJustify');
    $this->dispatch('toast', message: 'Department deleted');
    $this->reset(['editId', 'justification', 'actionType']);
  }

  /**
   * Unified confirmation entrypoint for the justification modal.
   * Routes to confirmDelete or confirmSave based on current actionType.
   */
  public function confirmJustify(): void
  {
    if (($this->actionType ?? '') === 'delete') {
      $this->confirmDelete();
    } else {
      $this->confirmSave();
    }
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
   * @return Response
   */
  public function render()
  {
    $paginator = $this->paginated();
    $visibleIds = $paginator->pluck('id')->all();
    return view('livewire.admin.departments-index', [
      'rows' => $paginator,
      'visibleIds' => $visibleIds,
      'codes' => $this->codes,
    ]);
  }

  // Private/Protected Helper Methods
  // Data aggregation / sources
  protected function allDepartments(): Collection
  {
    // Load via service and normalize shape expected by the view
    $rows = app(DepartmentService::class)
      ->getAllDepartments()
      ->map(function ($d) {
        // Compute director from eager-loaded employees/roles, preferring explicit Department Director
        $directorUser = null;
        foreach (($d->employees ?? []) as $emp) {
          $names = collect($emp->roles)->pluck('name')->filter()->map(fn($r) => mb_strtolower((string)$r));
          $codes = collect($emp->roles)->pluck('code')->filter()->map(fn($c) => mb_strtolower((string)$c));
          $hasExact = $names->contains('department director') || $codes->contains('department-director');
          $hasGeneric = $names->contains(function ($n) {
            return str_contains($n, 'director');
          });
          if ($hasExact || $hasGeneric) {
            $directorUser = $emp;
            break;
          }
        }
        $director = '';
        if ($directorUser) {
          $first = (string)($directorUser->first_name ?? '');
          $last  = (string)($directorUser->last_name ?? '');
          $full  = trim(trim($first . ' ' . $last));
          $director = $full !== '' ? $full : (string)($directorUser->email ?? '');
        }
        return [
          'id' => (int)$d->id,
          'name' => (string)$d->name,
          'code' => (string)($d->code ?? ''),
          'director' => $director,
        ];
      });

    return collect($rows);
  }

  /**
   * Filters the departments based on the search query.
   */
  protected function filtered(): Collection
  {
    $s = mb_strtolower(trim((string) ($this->search ?? '')));
    $activeCode = strtoupper(trim((string)($this->code ?? '')));
    $data = $this->allDepartments()->filter(function ($departments) use ($s, $activeCode) {
      $name = mb_strtolower(trim((string)($departments['name'] ?? '')));
      $director = mb_strtolower(trim((string)($departments['director'] ?? '')));
      $code = strtoupper(trim((string)($departments['code'] ?? '')));

      $hit = $s === '' ||
        str_contains($name, $s) ||
        str_contains($director, $s);

      $codeOk = ($activeCode === '') || ($code === $activeCode);

      return $hit && $codeOk;
    })->values();

    // Sort only when activated by user click
    if ($this->sortField !== '') {
      // Sort using natural, case-insensitive order by the active field
      $options = SORT_NATURAL | SORT_FLAG_CASE;
      $data = $data->sortBy(fn($row) => $row[$this->sortField] ?? '', $options, $this->sortDirection === 'desc')->values();
    }
    return $data;
  }

  /**
   * Paginates the filtered collection of departments.
   */
  protected function paginated(): LengthAwarePaginator
  {
    $data = $this->filtered();
    $items = $data->slice(($this->page - 1) * $this->pageSize, $this->pageSize)->values();
    return new LengthAwarePaginator($items, $data->count(), $this->pageSize, $this->page, ['path' => request()->url(), 'query' => request()->query()]);
  }

  /**
   * Returns the validation rules for the justification field.
   */
  protected function rules(): array
  {
    return [
      'justification' => ['required', 'string', 'min:3'],
    ];
  }

  /**
   * Validates only the justification field.
   */
  protected function validateJustification(): void
  {
    $this->validateOnly('justification');
  }
}
