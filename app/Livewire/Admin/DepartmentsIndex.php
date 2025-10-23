<?php

namespace App\Livewire\Admin;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class DepartmentsIndex extends Component
{
  public string $search = '';
  public int $page = 1;
  public int $pageSize = 25;

  public ?int $editId = null;
  public string $dName = '';
  public string $dCode = '';
  public string $dDirector = '';
  public string $dEmail = '';
  public string $dPhone = '';
  public string $dPolicies = '';
  public string $justification = '';
  public bool $isDeleting = false;

  protected function allDepartments(): Collection
  {
    return collect([
      ['id' => 1, 'name' => 'Biology', 'code' => 'BIO', 'director' => 'mruiz', 'email' => 'bio@upr.edu', 'phone' => '555-1001', 'policies' => 'No animals after 6pm.', 'venues' => 3, 'members' => 24],
      ['id' => 2, 'name' => 'Arts', 'code' => 'ART', 'director' => 'jdoe', 'email' => 'arts@upr.edu', 'phone' => '555-2010', 'policies' => 'Ticketing required.', 'venues' => 2, 'members' => 12],
      ['id' => 3, 'name' => 'Facilities', 'code' => 'FAC', 'director' => 'lortiz', 'email' => 'fac@upr.edu', 'phone' => '555-3000', 'policies' => 'Outdoor curfew 11pm.', 'venues' => 5, 'members' => 8],
    ]);
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

  public function updated($name, $value)
  {
    if (in_array($name, ['search', 'pageSize'])) $this->page = 1;
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

  public function save(): void
  {
    $this->isDeleting = false;
    $this->dispatch('bs:open', id: 'deptJustify');
  }
  public function confirmSave(): void
  {
    $this->dispatch('bs:close', id: 'deptJustify');
    $this->dispatch('bs:close', id: 'deptModal');
    $this->dispatch('toast', message: 'Department saved');
    $this->reset(['justification', 'isDeleting']);
  }

  public function delete(int $id): void
  {
    $this->editId = $id;
    $this->isDeleting = true;
    $this->dispatch('bs:open', id: 'deptJustify');
  }
  public function confirmDelete(): void
  {
    $this->dispatch('bs:close', id: 'deptJustify');
    $this->dispatch('toast', message: 'Department deleted');
    $this->reset(['editId', 'justification', 'isDeleting']);
  }

  public function render()
  {
    return view('livewire.admin.departments-index', ['rows' => $this->paginated()]);
  }
}
