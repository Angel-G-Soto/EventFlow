{{-- resources/views/livewire/admin/audit-trail-index.blade.php --}}
{{-- Local helper for 12-hour time formatting (display only) --}}
@php
$fmtAudit = function ($dt) {
if (empty($dt)) return '—';
try {
return \Carbon\Carbon::parse($dt)
->timezone(config('app.timezone'))
->format('M j, Y • g:i A T');
} catch (\Exception $e) {
return $dt;
}
};
@endphp

<div>
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Audit Trail</h1>
  </div>

  {{-- Filters --}}
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2">
        <div class="col-6 col-md-2">
          <label class="form-label" for="audit_user_id">User ID</label>
          <input id="audit_user_id" type="number" class="form-control" wire:model.live="userId" min="1"
            placeholder="e.g. 12">
        </div>

        <div class="col-6 col-md-3">
          <label class="form-label" for="audit_action">Action Code</label>
          <input id="audit_action" type="text" class="form-control" wire:model.live="action"
            placeholder="e.g. USER_UPDATE">
        </div>

        <div class="col-6 col-md-2">
          <label class="form-label" for="audit_from">From</label>
          <input id="audit_from" type="date" class="form-control" wire:model.live="from">
        </div>

        <div class="col-6 col-md-2">
          <label class="form-label" for="audit_to">To</label>
          <input id="audit_to" type="date" class="form-control" wire:model.live="to">
        </div>

        {{--<div class="col-12 col-md-2 d-flex align-items-end">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="adminOnly" wire:model.live="adminOnly">
            <label class="form-check-label" for="adminOnly">Admin only</label>
          </div>
        </div>--}}

        <div class="col-6 col-md-1 d-flex align-items-end">
          <button class="btn btn-secondary w-100" wire:click="clearFilters" type="button" aria-label="Clear filters">
            <i class="bi bi-x-circle me-1"></i> Clear
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- Top bar --}}
  <div class="d-flex align-items-center justify-content-between mb-2">
    <small class="text-secondary">
      {{ method_exists($rows, 'total') ? $rows->total() : count($rows) }} results
    </small>
    <div class="d-flex align-items-center gap-2">
      <label class="text-secondary small mb-0 text-black" for="audit_rows">Rows</label>
      <select id="audit_rows" class="form-select form-select-sm" style="width:auto" wire:model.live="perPage">
        <option>25</option>
        <option>50</option>
        <option>100</option>
      </select>
    </div>
  </div>

  {{-- Table --}}
  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>When</th>
            <th>User</th>
            <th>Action</th>
            <th>Target</th>
            <th class="text-end">Details</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $r)
          <tr>
            <td class="text-nowrap">
              {{ $fmtAudit($r->created_at ?? null) }}
            </td>
            <td>{{ $r->user_id ?? '—' }}</td>
            <td><span class="badge text-bg-light">{{ $r->action }}</span></td>
            <td class="text-truncate" style="max-width:220px;">
              {{ $r->target_type ? class_basename('User') : '—' }}
              @if($r->target_id)#{{ $r->target_id }}@endif
            </td>
            <td class="text-end">
              <button class="btn btn-outline-secondary btn-sm" wire:click="showDetails({{ $r->id }})"
                aria-label="Show details for audit #{{ $r->id }}" title="Show details for audit #{{ $r->id }}">
                <i class="bi bi-info-circle"></i>
              </button>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="7" class="text-center text-secondary py-4">No audit entries found.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="card-footer d-flex justify-content-end">
      {{ $rows->onEachSide(1)->links('partials.pagination') }}
    </div>
  </div>

  {{-- Details Modal --}}
  <div class="modal fade" id="auditDetails" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Audit Details #{{ $details['id'] ?? '' }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          @if(!empty($details))
          <dl class="row">
            <dt class="col-sm-3">User</dt>
            <dd class="col-sm-9">{{ $details['user_id'] ?? '—' }}</dd>
            <dt class="col-sm-3">Action</dt>
            <dd class="col-sm-9">{{ $details['action'] ?? '' }}</dd>
            <dt class="col-sm-3">Target</dt>
            <dd class="col-sm-9">{{ $details['target'] ?? '—' }}</dd>
            <dt class="col-sm-3">User Agent</dt>
            <dd class="col-sm-9"><small class="text-break">{{ $details['ua'] ?? '—' }}</small></dd>
            <dt class="col-sm-3">Created</dt>
            <dd class="col-sm-9">{{ $fmtAudit($details['created_at']) }}</dd>
          </dl>

          @if(!empty($details['meta']) && is_array($details['meta']))
          <hr>
          <h6 class="mb-2">Meta</h6>
          <pre
            class="small bg-body-secondary p-2 rounded-2 mb-0">{{ json_encode($details['meta'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
          @endif
          @endif
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  {{-- JS hook for Livewire modal open --}}
  <script>
    (function(){
      function ensureBodyScrollable(){
        try {
          const anyVisible = document.querySelector('.modal.show');
          if(!anyVisible){
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
            document.querySelectorAll('.modal-backdrop').forEach(b=>b.remove());
          }
        } catch(_) { /* noop */ }
      }
      document.addEventListener('hidden.bs.modal', ensureBodyScrollable);
      document.addEventListener('livewire:init', () => {
        Livewire.on('bs:open', ({ id }) => {
          const el = document.getElementById(id);
          if (el) bootstrap.Modal.getOrCreateInstance(el).show();
        });
        Livewire.on('bs:close', ({ id }) => {
          const el = document.getElementById(id);
          if (!el) return;
          const inst = bootstrap.Modal.getInstance(el) || bootstrap.Modal.getOrCreateInstance(el);
          inst.hide();
          setTimeout(ensureBodyScrollable, 0);
        });
      });
    })();
  </script>
</div>