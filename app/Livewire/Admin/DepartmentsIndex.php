<?php

namespace App\Livewire\Admin;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Livewire\Concerns\TableSelection;
use App\Livewire\Concerns\WithPaginationClamping;

#[Layout('layouts.app')]
class DepartmentsIndex extends Component
{
  use TableSelection, WithPaginationClamping;

  public string $search = '';
  public int $page = 1;
  public int $pageSize = 10;

  public ?int $editId = null;
  public string $dName = '';
  public string $dCode = '';
  public string $dDirector = '';
  public string $dEmail = '';
  public string $dPhone = '';
  public string $dPolicies = '';
  public string $justification = '';
  public string $actionType = '';
  public string $deleteType = 'soft'; // 'soft' or 'hard'

  /**
   * Check if the current action type is 'delete'.
   * 
   * @return bool True if action type is 'delete'
   */
  public function getIsDeletingProperty(): bool
  {
    return $this->actionType === 'delete';
  }

  /**
   * Check if the current action type is 'bulkDelete'.
   * 
   * @return bool True if action type is 'bulkDelete'
   */
  public function getIsBulkDeletingProperty(): bool
  {
    return $this->actionType === 'bulkDelete';
  }

  private static array $departments = [
    ['id' => 1, 'name' => 'Biology', 'code' => 'BIO', 'director' => 'mruiz', 'email' => 'bio@upr.edu', 'phone' => '555-1001', 'policies' => 'No animals after 6pm.', 'venues' => 3, 'members' => 24],
    ['id' => 2, 'name' => 'Arts', 'code' => 'ART', 'director' => 'jdoe', 'email' => 'arts@upr.edu', 'phone' => '555-2010', 'policies' => 'Ticketing required.', 'venues' => 2, 'members' => 12],
    ['id' => 3, 'name' => 'Facilities', 'code' => 'FAC', 'director' => 'lortiz', 'email' => 'fac@upr.edu', 'phone' => '555-3000', 'policies' => 'Outdoor curfew 11pm.', 'venues' => 5, 'members' => 8],
  ];

  /**
   * Returns a collection of all departments that are not deleted.
   *
   * This function takes into account both soft and hard deleted departments,
   * and also applies any edits that have been made to the departments.
   *
   * @return Collection
   */
  protected function allDepartments(): Collection
  {
    $deletedIndex = array_flip(array_unique(array_merge(
      array_map('intval', session('soft_deleted_department_ids', [])),
      array_map('intval', session('hard_deleted_department_ids', []))
    )));

    $combined = array_filter(
      self::$departments,
      function (array $d) use ($deletedIndex) {
        return !isset($deletedIndex[(int) $d['id']]);
      }
    );

    return collect($combined);
  }

  /**
   * Resets the current page to 1 when the search filter is updated.
   *
   * Also clears all current selections when the search filter is updated.
   */
  public function updatedSearch()
  {
    $this->page = 1;
    $this->selected = [];
  }



  /**
   * Resets the search filter and the current page to 1.
   *
   * Clears the search filter and resets the current page to 1.
   */
  public function clearFilters(): void
  {
    $this->search = '';
    $this->page = 1;
  }

  /**
   * Resets the edit fields to their default values and opens the edit department modal.
   *
   * This function will reset the edit fields to their default values and open the edit department modal.
   */
  public function openCreate(): void
  {
    $this->reset(['editId', 'dName', 'dCode', 'dDirector', 'dEmail', 'dPhone', 'dPolicies']);
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
    $d = $this->filtered()->firstWhere('id', $id);
    if (!$d) return;
    $this->editId = $d['id'];
    $this->dName = $d['name'];
    $this->dCode = $d['code'];
    $this->dDirector = $d['director'];
    $this->dEmail = $d['email'];
    $this->dPhone = $d['phone'];
    $this->dPolicies = $d['policies'];
    $this->dispatch('bs:open', id: 'deptModal');
  }

  /**
   * Returns the validation rules for the justification field.
   *
   * The rules are as follows:
   * - required: The justification field is required.
   * - string: The justification field must be a string.
   * - min:3: The justification field must be at least 3 characters in length.
   *
   * @return array The validation rules for the justification field.
   */
  protected function rules(): array
  {
    return [
      'justification' => ['required', 'string', 'min:3'],
    ];
  }

  /**
   * Validates only the justification field.
   *
   * This function is a helper to validate only the justification field by calling
   * `validateOnly` with the justification field as the parameter.
   */
  protected function validateJustification(): void
  {
    $this->validateOnly('justification');
  }

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
    if ($isCreating) {
      $this->jumpToLastPageAfterCreate();
    }
  }

  /**
   * Opens the justification modal for deleting the department with the given ID.
   * This function should be called when the user wants to delete a department.
   * It sets the currently edited department ID and the isDeleting flag to true, and then opens the justification modal.
   * @param int $id The ID of the department to delete
   */
  public function delete(int $id): void
  {
    $this->editId = $id;
    $this->actionType = 'delete';
    $this->dispatch('bs:open', id: 'deptJustify');
  }

  /**
   * Confirms the deletion of a department.
   *
   * This function will validate the justification entered by the user, and then delete the department with the given ID.
   * After deletion, it clamps the current page to prevent the page from becoming out of bounds.
   * Finally, it shows a toast message indicating whether the department was permanently deleted or just deleted.
   */
  public function confirmDelete(): void
  {
    if ($this->editId) {
      $this->validateJustification();
      session()->push($this->deleteType === 'hard' ? 'hard_deleted_department_ids' : 'soft_deleted_department_ids', $this->editId);
      unset($this->selected[$this->editId]);
    }
    $this->clampPageAfterMutation();
    $this->dispatch('bs:close', id: 'deptJustify');
    $this->dispatch('toast', message: 'Department ' . ($this->deleteType === 'hard' ? 'permanently deleted' : 'deleted'));
    $this->reset(['editId', 'justification', 'actionType']);
  }

  /**
   * Opens the justification modal for bulk deletion of departments.
   *
   * This function is called when the user wants to delete multiple departments at once.
   * It sets the isBulkDeleting flag to true, and then opens the justification modal.
   */
  public function bulkDelete(): void
  {
    if (empty($this->selected)) return;
    $this->actionType = 'bulkDelete';
    $this->dispatch('bs:open', id: 'deptJustify');
  }

  /**
   * Confirms the bulk deletion of departments.
   *
   * This function will validate the justification entered by the user, and then delete the departments with the given IDs.
   * After deletion, it clamps the current page to prevent the page from becoming out of bounds.
   * Finally, it shows a toast message indicating whether the departments were permanently deleted or just deleted.
   */
  public function confirmBulkDelete(): void
  {
    $selectedIds = array_keys($this->selected);
    if (empty($selectedIds)) return;
    $this->validateJustification();
    $sessionKey = $this->deleteType === 'hard' ? 'hard_deleted_department_ids' : 'soft_deleted_department_ids';
    $existingIds = session($sessionKey, []);
    $newIds = array_merge($existingIds, $selectedIds);
    session([$sessionKey => array_values(array_unique($newIds))]);
    $this->selected = [];
    $this->clampPageAfterMutation();
    $this->dispatch('bs:close', id: 'deptJustify');
    $this->dispatch('toast', message: count($selectedIds) . ' departments ' . ($this->deleteType === 'hard' ? 'permanently deleted' : 'deleted'));
    $this->reset(['justification', 'actionType']);
  }

  /**
   * Restore all soft deleted departments.
   *
   * This function will reset the soft_deleted_department_ids session key to an empty array,
   * effectively restoring all soft deleted departments.
   */
  public function restoreUsers(): void
  {
    session(['soft_deleted_department_ids' => []]);
    $this->dispatch('toast', message: 'All deleted departments restored');
  }

  /**
   * Filters the departments based on the search query.
   *
   * The function takes the search query and filters the departments based on the name, code, or director.
   * If the search query is empty, all departments are returned.
   * The function returns a collection of filtered departments.
   */
  protected function filtered(): Collection
  {
    $s = mb_strtolower(trim($this->search));
    return $this->allDepartments()->filter(function ($d) use ($s) {
      $hit = $s === '' ||
        str_contains(mb_strtolower($d['name']), $s) ||
        str_contains(mb_strtolower($d['code']), $s) ||
        str_contains(mb_strtolower($d['director']), $s);
      return $hit;
    })->values();
  }

  /**
   * Paginates the filtered collection of departments.
   *
   * This function takes the filtered collection of departments, slices it based on the current page and page size,
   * and returns a LengthAwarePaginator object. The paginator is configured to use the current URL and query string.
   *
   * @return LengthAwarePaginator
   */
  protected function paginated(): LengthAwarePaginator
  {
    $data = $this->filtered();
    $items = $data->slice(($this->page - 1) * $this->pageSize, $this->pageSize)->values();
    return new LengthAwarePaginator($items, $data->count(), $this->pageSize, $this->page, ['path' => request()->url(), 'query' => request()->query()]);
  }

  /**
   * Renders the Livewire view for the departments index page.
   *
   * The view is passed the following variables:
   * - $rows: The paginated collection of Department objects
   * - $visibleIds: The array of visible department IDs
   *
   * @return \Illuminate\Contracts\View\View
   */
  public function render()
  {
    $paginator = $this->paginated();
    $paginator = $this->ensurePageInBounds($paginator);
    if ($this->page !== $paginator->currentPage()) {
      $paginator = $this->paginated();
    }
    $visibleIds = $paginator->pluck('id')->all();
    return view('livewire.admin.departments-index', [
      'rows' => $paginator,
      'visibleIds' => $visibleIds,
    ]);
  }
}
