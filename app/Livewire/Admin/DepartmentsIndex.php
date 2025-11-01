<?php

namespace App\Livewire\Admin;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Livewire\Traits\DepartmentFilters;
use App\Livewire\Traits\DepartmentEditState;
use App\Repositories\DepartmentRepository;

#[Layout('layouts.app')]
class DepartmentsIndex extends Component
{
  // Traits / shared state
  use DepartmentFilters, DepartmentEditState;

  // Properties / backing stores
  /** @var array<int,array{name:string,code:string,director:string,id:int}> */
  public array $departments = [];

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
   * Load departments from CSV on mount.
   */
  public function mount(): void
  {
    $this->departments = DepartmentRepository::all();
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
    $isCreating = !$this->editId;
    $this->dispatch('bs:close', id: 'deptJustify');
    $this->dispatch('bs:close', id: 'deptModal');
    $this->dispatch('toast', message: 'Department saved');
    $this->reset(['justification', 'actionType']);
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
      session()->push('soft_deleted_department_ids', $this->editId);
    }
    $this->dispatch('bs:close', id: 'deptJustify');
    $this->dispatch('toast', message: 'Department deleted');
    $this->reset(['editId', 'justification', 'actionType']);
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
    $deletedIndex = array_flip(array_unique(
      array_map('intval', session('soft_deleted_department_ids', []))
    ));

    $combined = array_values(array_filter(
      $this->departments,
      function (array $departments) use ($deletedIndex) {
        return !isset($deletedIndex[(int) $departments['id']]);
      }
    ));

    return collect($combined);
  }

  /**
   * Filters the departments based on the search query.
   */
  protected function filtered(): Collection
  {
    $s = mb_strtolower(trim((string) ($this->search ?? '')));
    return $this->allDepartments()->filter(function ($departments) use ($s) {
      $name = mb_strtolower(trim((string)($departments['name'] ?? '')));
      $code = mb_strtolower(trim((string)($departments['code'] ?? '')));
      $director = mb_strtolower(trim((string)($departments['director'] ?? '')));

      $hit = $s === '' ||
        str_contains($name, $s) ||
        str_contains($code, $s) ||
        str_contains($director, $s);

      $codeOk = ($this->code ?? '') === '' || ($departments['code'] ?? '') === $this->code;
      return $hit && $codeOk;
    })->values();
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
