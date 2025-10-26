<?php

namespace App\Livewire\Admin;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Livewire\Concerns\TableSelection;

#[Layout('layouts.app')]
class DepartmentsIndex extends Component
{
  use TableSelection;

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

  public function getIsDeletingProperty(): bool
  {
    return $this->actionType === 'delete';
  }

  public function getIsBulkDeletingProperty(): bool
  {
    return $this->actionType === 'bulkDelete';
  }

  private static array $departments = [
    ['id' => 1, 'name' => 'Biology', 'code' => 'BIO', 'director' => 'mruiz', 'email' => 'bio@upr.edu', 'phone' => '555-1001', 'policies' => 'No animals after 6pm.', 'venues' => 3, 'members' => 24],
    ['id' => 2, 'name' => 'Arts', 'code' => 'ART', 'director' => 'jdoe', 'email' => 'arts@upr.edu', 'phone' => '555-2010', 'policies' => 'Ticketing required.', 'venues' => 2, 'members' => 12],
    ['id' => 3, 'name' => 'Facilities', 'code' => 'FAC', 'director' => 'lortiz', 'email' => 'fac@upr.edu', 'phone' => '555-3000', 'policies' => 'Outdoor curfew 11pm.', 'venues' => 5, 'members' => 8],
  ];

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

  public function updatedSearch()
  {
    $this->page = 1;
    $this->selected = [];
  }

  public function updatedPageSize()
  {
    $this->page = 1;
  }

  public function clearFilters(): void
  {
    $this->search = '';
    $this->page = 1;
  }

  public function openCreate(): void
  {
    $this->reset(['editId', 'dName', 'dCode', 'dDirector', 'dEmail', 'dPhone', 'dPolicies']);
    $this->dispatch('bs:open', id: 'deptModal');
  }

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

  protected function rules(): array
  {
    return [
      'justification' => ['required', 'string', 'min:3'],
    ];
  }

  protected function validateJustification(): void
  {
    $this->validateOnly('justification');
  }

  public function save(): void
  {
    $this->actionType = 'save';
    $this->dispatch('bs:open', id: 'deptJustify');
  }

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

  protected function jumpToLastPageAfterCreate(): void
  {
    $total = $this->filtered()->count();
    $last = max(1, (int) ceil($total / max(1, $this->pageSize)));
    $this->page = $last;
    $this->selected = [];
  }

  public function delete(int $id): void
  {
    $this->editId = $id;
    $this->actionType = 'delete';
    $this->dispatch('bs:open', id: 'deptJustify');
  }

  public function confirmDelete(): void
  {
    if ($this->editId) {
      $this->validateJustification();
      session()->push($this->deleteType === 'hard' ? 'hard_deleted_department_ids' : 'soft_deleted_department_ids', $this->editId);
      unset($this->selected[$this->editId]);
    }
    $this->dispatch('bs:close', id: 'deptJustify');
    $this->dispatch('toast', message: 'Department ' . ($this->deleteType === 'hard' ? 'permanently deleted' : 'deleted'));
    $this->reset(['editId', 'justification', 'actionType']);
  }

  public function bulkDelete(): void
  {
    if (empty($this->selected)) return;
    $this->actionType = 'bulkDelete';
    $this->dispatch('bs:open', id: 'deptJustify');
  }

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
    $this->dispatch('bs:close', id: 'deptJustify');
    $this->dispatch('toast', message: count($selectedIds) . ' departments ' . ($this->deleteType === 'hard' ? 'permanently deleted' : 'deleted'));
    $this->reset(['justification', 'actionType']);
  }

  public function restoreUsers(): void
  {
    session(['soft_deleted_department_ids' => []]);
    $this->dispatch('toast', message: 'All deleted departments restored');
  }

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

  protected function paginated(): LengthAwarePaginator
  {
    $data = $this->filtered();
    $items = $data->slice(($this->page - 1) * $this->pageSize, $this->pageSize)->values();
    return new LengthAwarePaginator($items, $data->count(), $this->pageSize, $this->page, ['path' => request()->url(), 'query' => request()->query()]);
  }

  public function render()
  {
    $rows = $this->paginated();
    $visibleIds = $rows->pluck('id')->all();
    return view('livewire.admin.departments-index', [
      'rows' => $rows,
      'visibleIds' => $visibleIds,
    ]);
  }
}
