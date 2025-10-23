<div>
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Departments</h1>

    <div class="d-none d-md-flex gap-2">
      {{-- <a href="{{ route('admin.departments.export') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-download me-1"></i> Export CSV
      </a> --}}
      <button class="btn btn-primary btn-sm" wire:click="openCreate">
        <i class="bi bi-plus-lg me-1"></i> New Department
      </button>
    </div>
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2">
        <div class="col-md-6">
          <label class="form-label">Search</label>
          <input class="form-control" wire:model.live.debounce.300ms="search" placeholder="name, code, director">
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Name</th>
            <th>Code</th>
            <th>Director</th>
            <th>Contact</th>
            <th>Venues</th>
            <th>Members</th>
            <th class="text-end" style="width:120px;">Actions</th>
          </tr>
        </thead>
        <tbody>
        @forelse($rows as $d)
          <tr>
            <td class="fw-medium">{{ $d['name'] }}</td>
            <td>{{ $d['code'] }}</td>
            <td>{{ $d['director'] }}</td>
            <td>
              <div>{{ $d['email'] }}</div>
              <div class="text-secondary small">{{ $d['phone'] }}</div>
            </td>
            <td >
              <a class="text-decoration-none" href="{{ route('admin.venues') }}?department={{ urlencode($d['name']) }}">{{ $d['venues'] }}</a>
            </td>
            <td>{{ $d['members'] }}</td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-secondary" wire:click="openEdit({{ $d['id'] }})"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-outline-danger" wire:click="delete({{ $d['id'] }})"><i class="bi bi-trash3"></i></button>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="8" class="text-center text-secondary py-4">No departments found.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>

    <div class="card-footer d-flex align-items-center justify-content-between">
      <small class="text-secondary">{{ $rows->total() }} total</small>
      <div class="btn-group btn-group-sm">
        <button class="btn btn-outline-secondary" wire:click="$set('page',1)" @disabled($rows->currentPage()===1)>&laquo;</button>
        <span class="btn btn-outline-secondary disabled">Page {{ $rows->currentPage() }} / {{ $rows->lastPage() }}</span>
        <button class="btn btn-outline-secondary"
                wire:click="$set('page', min($rows->lastPage(), $page+1))"
                @disabled($rows->currentPage()===$rows->lastPage())>&raquo;</button>
      </div>
    </div>
  </div>

  {{-- Create/Edit --}}
  <div class="modal fade" id="deptModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <form class="modal-content" wire:submit.prevent="save">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-building-gear me-2"></i>{{ $editId ? 'Edit Department' : 'New Department' }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Name</label><input class="form-control" wire:model.live="dName" required></div>
            <div class="col-md-3"><label class="form-label">Code</label><input class="form-control" wire:model.live="dCode" required></div>
            <div class="col-md-3"><label class="form-label">Director (user)</label><input class="form-control" wire:model.live="dDirector"></div>
            <div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" wire:model.live="dEmail"></div>
            <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" wire:model.live="dPhone"></div>
            <div class="col-12"><label class="form-label">Default Policies</label><textarea class="form-control" rows="4" wire:model.live="dPolicies"></textarea></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Save</button>
        </div>
      </form>
    </div>
  </div>

  <x-justification id="deptJustify" submit="{{ $isDeleting ? 'confirmDelete' : 'confirmSave' }}" model="justification" />

  <div class="position-fixed top-0 end-0 p-3" style="z-index:1080;" wire:ignore>
    <div id="deptToast" class="toast text-bg-success"><div class="toast-body" id="deptToastMsg">Done</div></div>
  </div>

  <script>
    document.addEventListener('livewire:init', () => {
      Livewire.on('bs:open', ({ id }) => { const el=document.getElementById(id); if(el) new bootstrap.Modal(el).show(); });
      Livewire.on('bs:close', ({ id }) => { const el=document.getElementById(id); if(!el) return; const m=bootstrap.Modal.getInstance(el); if(m) m.hide(); });
      Livewire.on('toast', ({ message }) => {
        document.getElementById('deptToastMsg').textContent = message || 'Done';
        new bootstrap.Toast(document.getElementById('deptToast'), { delay: 2200 }).show();
      });
    });
  </script>
</div>
