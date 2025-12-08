{{-- resources/views/livewire/admin/audit-trail-index.blade.php --}}
{{-- Local helper for audit timestamp formatting --}}
@php
$fmtAudit = function ($dt) {
if (empty($dt)) return '—';
try {
return \Carbon\Carbon::parse($dt)
->timezone(config('app.timezone'))
->format('D, M j, Y g:i A');
} catch (\Exception $e) {
return $dt;
}
};

$userSearch = $userSearch ?? null;
$action = $action ?? '';
$from = $from ?? null;
$to = $to ?? null;
$pageSize = $pageSize ?? 25;

$downloadParams = array_filter([
'user' => $userSearch,
'action' => $action,
'date_from' => $from,
'date_to' => $to,
'limit' => $pageSize,
], fn ($v) => $v !== null && $v !== '');
@endphp

<div>
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Audit Trail</h1>
  </div>

  {{-- Filters --}}
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-12 col-md-3 col-lg-2">
          <label class="form-label" for="audit_from">From</label>
          <input id="audit_from" type="date" class="form-control" wire:model.defer="from">
        </div>

        <div class="col-12 col-md-3 col-lg-2">
          <label class="form-label" for="audit_to">To</label>
          <input id="audit_to" type="date" class="form-control" wire:model.defer="to">
        </div>

        <div class="col-12 col-md-4 col-lg-4">
          <label class="form-label" for="audit_search">Search</label>
          <form wire:submit.prevent="applySearch">
            <div class="input-group">
              <input id="audit_search" type="text" class="form-control" wire:model.defer="search"
                placeholder="Search by user, action, target, or IP…">
              <button class="btn btn-secondary" type="submit" aria-label="Search" title="Search">
                <i class="bi bi-search"></i>
              </button>
            </div>
          </form>
        </div>

        <div class="col-12 col-md-auto d-flex align-items-end gap-2">
          <button class="btn btn-primary text-nowrap" wire:click="applyDateRange" type="button"
            aria-label="Apply date range">
            Apply Date Range
          </button>
          <button class="btn btn-secondary text-nowrap" wire:click="clearFilters" type="button"
            aria-label="Clear filters">
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
      <select id="audit_rows" class="form-select form-select-sm" style="width:auto" wire:model.live="pageSize">
        <option>25</option>
        <option>50</option>
        <option>100</option>
      </select>
      <a class="btn btn-primary btn-sm" href="{{ route('admin.audit.download', $downloadParams) }}" target="_blank"
        rel="noopener">
        <i class="bi bi-download me-1"></i> PDF
      </a>
    </div>
  </div>

  {{-- Table --}}
  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th scope="col">When</th>
            <th scope="col">User</th>
            <th scope="col">Action</th>
            <th scope="col">Target</th>
            <th scope="col">IP</th>
            <th scope="col">Details</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $r)
          <tr>
            <td class="text-nowrap">
              {{ $fmtAudit($r->created_at ?? null) }}
            </td>
            <td>
              @php
              $actor = $r->actor ?? null;
              $name = null;
              if ($actor) {
              $full = trim(($actor->first_name ?? '').' '.($actor->last_name ?? ''));
              $name = $full !== '' ? $full : ($actor->name ?? ($actor->email ?? null));
              }
              @endphp
              @if($name)
              {{ $name }}
              @if(!empty($r->user_id))
              <span class="text-muted small">(#{{ $r->user_id }})</span>
              @endif
              @elseif(!empty($r->user_id))
              #{{ $r->user_id }}
              @else
              —
              @endif
            </td>
            <td><span class="badge text-bg-light">{{ $r->action }}</span></td>
            <td class="text-truncate" style="max-width:220px;">
              {{ $r->target_type ? class_basename($r->target_type) : '—' }}:
              @if($r->target_id)
              {{ $r->target_id }}
              @endif
            </td>
            <td class="text-truncate" style="max-width:130px;">
              <small class="fw-bold">{{ $r->ip ?? '—' }}</small>
            </td>
            <td>
              <button class="btn btn-secondary btn-sm" wire:click="showDetails({{ $r->id }})"
                aria-label="View details for audit #{{ $r->id }}" title="View details for audit #{{ $r->id }}">
                <i class="bi bi-info-circle"></i>
              </button>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="6" class="text-center text-secondary py-4">No audit entries found.</td>
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
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          @if(!empty($details))
          <dl class="row">
            <dt class="col-sm-3">User</dt>
            <dd class="col-sm-9">
              @if(!empty($details['user_label']))
              {{ $details['user_label'] }}
              @if(!empty($details['user_id']))
              <span class="text-muted small">(#{{ $details['user_id'] }})</span>
              @endif
              @elseif(!empty($details['user_id']))
              <span class="text-muted small">#{{ $details['user_id'] }}</span>
              @else
              —
              @endif
            </dd>
            <dt class="col-sm-3">Action</dt>
            <dd class="col-sm-9">{{ $details['action'] ?? '' }}</dd>
            <dt class="col-sm-3">Target</dt>
            <dd class="col-sm-9">{{ $details['target'] ?? '—' }}</dd>
            <dt class="col-sm-3">User Agent</dt>
            <dd class="col-sm-9"><small class="text-break">{{ $details['ua'] ?? '—' }}</small></dd>
            <dt class="col-sm-3">IP Address</dt>
            <dd class="col-sm-9"><small class="text-break">{{ $details['ip'] ?? 'Unknown' }}</small></dd>
            <dt class="col-sm-3">Created</dt>
            <dd class="col-sm-9">{{ $fmtAudit($details['created_at']) }}</dd>
            @if(!empty($details['justification']))
            <dt class="col-sm-3">Justification</dt>
            <dd class="col-sm-9">{{ $details['justification'] }}</dd>
            @endif
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
          <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
