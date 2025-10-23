<div>
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Event Oversight</h1>

    <div class="d-none d-md-flex gap-2">
      {{-- <a href="{{ route('admin.oversight.export') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-download me-1"></i> Export CSV
      </a> --}}
    </div>
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2">
        <div class="col-md-4">
          <label class="form-label">Search</label>
          <input class="form-control" placeholder="title, requestor" wire:model.live.debounce.300ms="search">
        </div>
        <div class="col-md-2">
          <label class="form-label">Status</label>
          <select class="form-select" wire:model.live="status">
            <option value="">All</option>
            <option>Pending</option><option>Approved</option><option>Denied</option><option>Rerouted</option><option>Returned</option>
          </select>
        </div>
        <div class="col-md-2"><label class="form-label">Department</label><input class="form-control" wire:model.live="department"></div>
        <div class="col-md-2"><label class="form-label">Venue</label><input class="form-control" wire:model.live="venue"></div>
        <div class="col-md-2"><label class="form-label">Category</label><input class="form-control" wire:model.live="category"></div>
        <div class="col-md-2"><label class="form-label">From</label><input type="datetime-local" class="form-control" wire:model.live="from"></div>
        <div class="col-md-2"><label class="form-label">To</label><input type="datetime-local" class="form-control" wire:model.live="to"></div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Requestor</th>
            <th>Department</th>
            <th>Venue</th>
            <th>Date/Time</th>
            <th>Status</th>
            <th class="text-end" style="width:120px;">Actions</th>
          </tr>
        </thead>
        <tbody>
        @forelse($rows as $r)
          <tr>
            <td class="text-secondary">#{{ $r['id'] }}</td>
            <td class="fw-medium">{{ $r['title'] }}</td>
            <td>{{ $r['requestor'] }}</td>
            <td>{{ $r['department'] }}</td>
            <td>{{ $r['venue'] }}</td>
            <td>
              <div>{{ \Illuminate\Support\Str::before($r['from'],' ') }} {{ \Illuminate\Support\Str::after($r['from'],' ') }}</div>
              <div class="text-secondary small">→ {{ \Illuminate\Support\Str::before($r['to'],' ') }} {{ \Illuminate\Support\Str::after($r['to'],' ') }}</div>
            </td>
            <td>
              <span class="badge {{ $r['status']==='Pending' ? 'text-bg-primary' : 
              ($r['status']==='Approved' ? 'text-bg-success' : 
              ($r['status']==='Denied' ? 'text-bg-danger' : 'text-bg-warning')) }}">
                {{ $r['status'] }}
              </span>
            </td>
            <td class="text-end">
              <button class="btn btn-outline-secondary btn-sm" wire:click="openEdit({{ $r['id'] }})">
                <i class="bi bi-eye"></i>
              </button>
            </td>
          </tr>
        @empty
          <tr><td colspan="8" class="text-center text-secondary py-4">No requests found.</td></tr>
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

  {{-- Edit/Action drawer (modal) --}}
  <div class="modal fade" id="oversightEdit" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <form class="modal-content" wire:submit.prevent="saveEdits">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-journal-text me-2"></i>Request #{{ $editId }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Title</label><input class="form-control" wire:model.live="eTitle"></div>
            <div class="col-md-3"><label class="form-label">Department</label><input class="form-control" wire:model.live="eDepartment"></div>
            <div class="col-md-3"><label class="form-label">Venue</label><input class="form-control" wire:model.live="eVenue"></div>
            <div class="col-md-3"><label class="form-label">From</label><input type="datetime-local" class="form-control" wire:model.live="eFrom"></div>
            <div class="col-md-3"><label class="form-label">To</label><input type="datetime-local" class="form-control" wire:model.live="eTo"></div>
            <div class="col-md-3"><label class="form-label">Attendees</label><input type="number" class="form-control" min="0" wire:model.live="eAttendees"></div>
            <div class="col-md-3"><label class="form-label">Category</label><input class="form-control" wire:model.live="eCategory"></div>
            <div class="col-12"><label class="form-label">Purpose</label><textarea class="form-control" rows="3" wire:model.live="ePurpose"></textarea></div>
            <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" rows="3" wire:model.live="eNotes"></textarea></div>

            <div class="col-12">
              <label class="form-label">Policy flags</label>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="alcohol" wire:model.live="ePolicyAlcohol">
                <label class="form-check-label" for="alcohol">Alcohol policy acknowledged</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="curfew" wire:model.live="ePolicyCurfew">
                <label class="form-check-label" for="curfew">Curfew policy acknowledged</label>
              </div>
            </div>

            {{-- Attached docs, approval history, route/step — placeholders for now --}}
            <div class="col-12">
              <div class="alert alert-secondary small mb-0">
                <strong>Attachments:</strong> (links) • <strong>History:</strong> approvals/denials • <strong>Current Step:</strong> Department/Role
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer d-flex justify-content-between">
          <div class="btn-group">
            <button type="button" class="btn btn-outline-success" wire:click.prevent="approve"><i class="bi bi-check2-circle me-1"></i>Approve</button>
            <button type="button" class="btn btn-outline-danger" wire:click.prevent="deny"><i class="bi bi-x-octagon me-1"></i>Deny</button>
            <button type="button" class="btn btn-outline-secondary" wire:click.prevent="advance"><i class="bi bi-arrow-right-circle me-1"></i>Advance</button>
            <button type="button" class="btn btn-outline-warning" wire:click.prevent="reroute"><i class="bi bi-shuffle me-1"></i>Re-route</button>
            <button type="button" class="btn btn-outline-primary" wire:click.prevent="override"><i class="bi bi-sliders me-1"></i>Override</button>
          </div>
          <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Save changes</button>
        </div>
      </form>
    </div>
  </div>

  <x-justification id="oversightJustify" submit="confirmAction" model="justification" />

  <div class="position-fixed top-0 end-0 p-3" style="z-index:1080;" wire:ignore>
    <div id="ovToast" class="toast text-bg-success"><div class="toast-body" id="ovToastMsg">Done</div></div>
  </div>

  <script>
    document.addEventListener('livewire:init', () => {
      Livewire.on('bs:open', ({ id }) => { const el=document.getElementById(id); if(el) new bootstrap.Modal(el).show(); });
      Livewire.on('bs:close', ({ id }) => { const el=document.getElementById(id); if(!el) return; const m=bootstrap.Modal.getInstance(el); if(m) m.hide(); });
      Livewire.on('toast', ({ message }) => {
        document.getElementById('ovToastMsg').textContent = message || 'Done';
        new bootstrap.Toast(document.getElementById('ovToast'), { delay: 2200 }).show();
      });
    });
  </script>
</div>
