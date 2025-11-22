<div>
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Departments</h1>

  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2">
        <div class="col-12 col-md-4">
          <label class="form-label" for="dept_search">Search</label>
          <form wire:submit.prevent="applySearch">
            <div class="input-group">
              <input id="dept_search" type="text" class="form-control" placeholder="Search by name or director"
                wire:model.defer="search">
              <button class="btn btn-secondary" type="submit" aria-label="Search" title="Search">
                <i class="bi bi-search"></i>
              </button>
            </div>
          </form>
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label" for="dept_code">Department Code</label>
          <select id="dept_code" class="form-select" wire:model.live="code">
            <option value="">All</option>
            @foreach($codes as $c)
            <option value="{{ $c }}">{{ $c }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-2 d-flex align-items-end">
          <button class="btn btn-secondary w-100" wire:click="clearFilters" type="button" aria-label="Clear filters">
            <i class="bi bi-x-circle me-1"></i> Clear
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- Page size --}}
  <div class="d-flex flex-wrap gap-2 align-items-center justify-content-end mb-2">
    <div class="d-flex align-items-center gap-2">
      <label class="text-secondary small mb-0 text-black" for="dept_rows">Rows</label>
      <select id="dept_rows" class="form-select form-select-sm" style="width:auto" wire:model.live="pageSize">
        <option>10</option>
        <option>25</option>
        <option>50</option>
      </select>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th scope="col" @if(($sortField ?? '' )==='name' )
              aria-sort="{{ (($sortDirection ?? '') === 'asc') ? 'ascending' : 'descending' }}" @else aria-sort="none"
              @endif>
              <button class="btn btn-link p-0 text-decoration-none text-black fw-bold" wire:click="sortBy('name')"
                aria-label="Sort by name">
                Name
                @if($sortField === 'name')
                @if($sortDirection === 'asc')
                <i class="bi bi-arrow-up-short" aria-hidden="true"></i>
                @else
                <i class="bi bi-arrow-down-short" aria-hidden="true"></i>
                @endif
                @else
                <i class="bi bi-arrow-down-up text-muted" aria-hidden="true"></i>
                @endif
              </button>
            </th>
            <th scope="col">Department Code</th>
            <th scope="col">Director</th>

            {{-- <th class="text-end" style="width:120px;">Actions</th> --}}
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $d)
          <tr>
            <td class="fw-medium">{{ $d['name'] }}</td>
            <td>{{ $d['code'] }}</td>
            <td>{{ trim($d['director'] ?? '') }}</td>
          </tr>
          @empty
          <tr>
            <td colspan="3" class="text-center text-secondary py-4">No departments found.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="card-footer d-flex align-items-center justify-content-between">
      <small class="text-secondary">
        {{ method_exists($rows, 'total') ? $rows->total() : count($rows) }} results
      </small>
      {{ $rows->onEachSide(1)->links('partials.pagination') }}
    </div>
  </div>

</div>