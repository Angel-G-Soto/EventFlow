<?php

namespace App\Livewire\Director;

use App\Services\UserService;
use App\Services\VenueService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

#[Layout('layouts.app')]
class VenuesIndex extends Component
{
  // Mock data until DB arrives
//  protected function allVenues(): Collection
//  {
//    return Auth::user()->department->venues;
//  }

  public string $search = '';
  public string $department = '';
  public int $page = 1;
  public int $pageSize = 10;

  // Sorting
  public string $sortField = '';
  public string $sortDirection = 'asc';

  // Edit modal
  public ?int $editId = null;
  public string $vName = '';
  public string $vRoom = '';
  public int $vCapacity = 0;
  public string $vStatus = 'Active';

  // Assign manager modal
  public ?int $assignId = null;
  public string $assignManager = '';

//  public function openEdit(int $id): void
//  {
//    $v = $this->allVenues()->firstWhere('id', $id);
//    if (!$v) return;
//    $this->editId    = $v['id'];
//    $this->vName     = $v['name'];
//    $this->vRoom     = $v['code'];
//    $this->vCapacity = (int) $v['capacity'];
//    $this->vStatus   = 'X';//$v['status'];
//    $this->resetErrorBag();
//    $this->resetValidation();
//    $this->dispatch('bs:open', id: 'editVenue');
//  }

//  public function saveEdit(): void
//  {
//    $this->validate([
//      'vName'     => 'required|string|max:120',
//      'vRoom'     => 'required|string|max:60',
//      'vCapacity' => 'required|integer|min:1|max:99999',
//      'vStatus'   => 'required|in:Active,Suspended,Inactive',
//    ]);
//    // persist later
//    $this->dispatch('bs:close', id: 'editVenue');
//    $this->dispatch('toast', message: 'Venue updated');
//    $this->reset(['editId']);
//  }

  public function openAssign(int $id): void
  {
    $this->assignId = $id;
    //this->assignManager = '';
    $this->dispatch('bs:open', id: 'assignManager');
  }

  public function saveAssign(): void
  {
    $this->validate([
      'assignManager' => 'required|email', // later: user selector of role "Venue Manager"
    ]);

    $user = app(UserService::class)->findOrCreateUser($this->assignManager);
    app(VenueService::class)->assignManager(app(VenueService::class)->findByID($this->assignId), $user, Auth::user());

    $this->dispatch('bs:close', id: 'assignManager');
    $this->dispatch('toast', message: 'Venue manager assigned');
    $this->reset(['assignId', 'assignManager']);
  }
//  protected function filtered(): Collection
//  {
//    $s = mb_strtolower(trim($this->search));
//    return $this->allVenues()->filter(function ($v) use ($s) {
//      $hit = $s === '' ||
//        str_contains(mb_strtolower($v['name']), $s) ||
//        str_contains(mb_strtolower($v['room']), $s) ||
//        str_contains(mb_strtolower($v['department']), $s);
//      $deptOk = $this->department === '' || $v['department'] === $this->department;
//      return $hit && $deptOk;
//    })->values();
//  }

  protected function paginated(): LengthAwarePaginator
  {
    $data  = $this->filtered();
    // Apply sorting only when activated by user click
    if ($this->sortField !== '') {
      $options = SORT_NATURAL | SORT_FLAG_CASE;
      $data = $data->sortBy(fn($row) => $row[$this->sortField] ?? '', $options, $this->sortDirection === 'desc')->values();
    }
    $total = $data->count();
    $last  = max(1, (int) ceil($total / max(1, $this->pageSize)));
    if ($this->page > $last) $this->page = $last;
    if ($this->page < 1)     $this->page = 1;

    $items = $data->slice(($this->page - 1) * $this->pageSize, $this->pageSize)->values();

    return new LengthAwarePaginator(
      items: $items,
      total: $total,
      perPage: $this->pageSize,
      currentPage: $this->page,
      options: ['path' => request()->url(), 'query' => request()->query()]
    );
  }

  public function updatedSearch()
  {
    $this->page = 1;
  }
  public function updatedDepartment()
  {
    $this->page = 1;
  }
  public function updatedPageSize()
  {
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

  /**
   * Clear all filters and reset pagination.
   */
  public function clearFilters(): void
  {
    $this->search = '';
    $this->department = '';
    $this->page = 1;
  }

  public function render()
  {

//    $rows = $this->paginated();
//    $departments = $this->allVenues()->pluck('department')->unique()->values()->all();

      $rows = Auth::user()->department->venues()->orderBy('name', $this->sortDirection)->paginate(15);
      //$departments = new Collection();
      $employees = Auth::user()->department->employees;

    return view('livewire.director.venues-index', compact('rows', 'employees'));
  }
}
