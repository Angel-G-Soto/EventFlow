<div>
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Event Oversight</h1>
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2">
        <div class="col-md-4">
          <label class="form-label" for="ev_search">Search</label>
          <form wire:submit.prevent="applySearch">
            <div class="input-group">
              <input id="ev_search" class="form-control" placeholder="title, requestor" wire:model.defer="search">
              <button class="btn btn-secondary" type="submit" aria-label="Search">
                <i class="bi bi-search"></i>
              </button>
            </div>
          </form>
        </div>
        <div class="col-md-2">
          <label class="form-label" for="ev_status">Status</label>
          <select id="ev_status" class="form-select" wire:model.live="status">
            <option value="">All</option>
            @foreach($statuses as $st)
            <option value="{{ $st }}">{{ $st }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label" for="ev_venue">Venue</label>
          <select id="ev_venue" class="form-select" wire:model.live="venue">
            <option value="">All</option>
            @foreach($venues as $vName)
            <option value="{{ $vName }}">{{ $vName }}</option>
            @endforeach
          </select>
        </div>
        {{-- Category filter removed intentionally --}}
        <div class="col-md-2">
          <label class="form-label" for="ev_org">Organization</label>
          <select id="ev_org" class="form-select" wire:model.live="organization">
            <option value="">All</option>
            @foreach($organizations as $org)
            <option value="{{ $org }}">{{ $org }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2"><label class="form-label" for="ev_from">From</label><input id="ev_from"
            type="datetime-local" class="form-control" wire:model.live="from"></div>
        <div class="col-md-2"><label class="form-label" for="ev_to">To</label><input id="ev_to" type="datetime-local"
            class="form-control" wire:model.live="to"></div>
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
      <label class="text-secondary small mb-0" for="ev_rows">Rows</label>
      <select id="ev_rows" class="form-select form-select-sm" style="width:auto" wire:model.live="pageSize">
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
            <th>ID</th>
            <th>Title</th>
            <th>Requestor</th>
            <th>Organization</th>
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
            <td>{{ $r['organization'] ?? ($r['organization_nexo_name'] ?? '') }}</td>
            <td>{{ $r['venue'] }}</td>
            <td>
              <div>{{ \Illuminate\Support\Str::before($r['from'],' ') }} {{ \Illuminate\Support\Str::after($r['from'],'
                ') }}</div>
              <div class="text-secondary small">→ {{ \Illuminate\Support\Str::before($r['to'],' ') }} {{
                \Illuminate\Support\Str::after($r['to'],' ') }}</div>
            </td>
            <td>
              <span class="badge text-bg-light me-1 {{ $this->statusBadgeClass($r['status']) }}">{{ $r['status']
                }}</span>
            </td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-info" wire:click="openView({{ $r['id'] }})"
                  aria-label="View request {{ $r['id'] }}">
                  <i class="bi bi-info-lg"></i>
                </button>
                <button class="btn btn-outline-secondary" wire:click="openEdit({{ $r['id'] }})"
                  aria-label="Edit request {{ $r['id'] }}" title="Edit request #{{ $r['id'] }}">
                  <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-outline-danger" wire:click="delete({{ $r['id'] }})"
                  aria-label="Delete request {{ $r['id'] }}" title="Delete request #{{ $r['id'] }}">
                  <i class="bi bi-trash3"></i>
                </button>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="8" class="text-center text-secondary py-4">No requests found.</td>
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

  {{-- View modal --}}
  <div class="modal fade" id="oversightView" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-eye me-2"></i>View Request #{{ $editId }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label" for="ev_v_title">Title</label><input id="ev_v_title"
                class="form-control" readonly value="{{ $eTitle }}"></div>
            <div class="col-md-3"><label class="form-label" for="ev_v_org">Organization</label><input id="ev_v_org"
                class="form-control" readonly value="{{ $eOrganization }}"></div>
            <div class="col-md-3"><label class="form-label" for="ev_v_venue">Venue</label><input id="ev_v_venue"
                class="form-control" readonly value="{{ $eVenue }}"></div>
            <div class="col-md-3"><label class="form-label" for="ev_v_advisor">Advisor Name</label><input
                id="ev_v_advisor" class="form-control" readonly value="{{ $eAdvisorName }}"></div>
            <div class="col-md-3"><label class="form-label" for="ev_v_advisor_email">Advisor Email</label><input
                id="ev_v_advisor_email" class="form-control" readonly value="{{ $eAdvisorEmail }}"></div>
            <div class="col-md-3"><label class="form-label" for="ev_v_advisor_phone">Advisor Phone</label><input
                id="ev_v_advisor_phone" class="form-control" readonly value="{{ $eAdvisorPhone }}"></div>
            <div class="col-md-3"><label class="form-label">Student Number</label><input class="form-control" readonly
                value="{{ $eStudentNumber }}"></div>
            <div class="col-md-3"><label class="form-label">Student Phone</label><input class="form-control" readonly
                value="{{ $eStudentPhone }}"></div>
            <div class="col-md-3"><label class="form-label" for="ev_v_from">From</label><input id="ev_v_from"
                type="datetime-local" class="form-control" readonly value="{{ $eFrom }}"></div>
            <div class="col-md-3"><label class="form-label" for="ev_v_to">To</label><input id="ev_v_to"
                type="datetime-local" class="form-control" readonly value="{{ $eTo }}"></div>
            <div class="col-md-3"><label class="form-label" for="ev_v_attendees">Attendees</label><input
                id="ev_v_attendees" type="number" class="form-control" readonly value="{{ $eAttendees }}"></div>
            <div class="col-md-3"><label class="form-label" for="ev_v_category">Category</label><input
                id="ev_v_category" class="form-control" readonly value="{{ $eCategory }}"></div>
            <div class="col-12"><label class="form-label" for="ev_v_purpose">Description</label><textarea
                id="ev_v_purpose" class="form-control" rows="3" readonly>{{ $ePurpose }}</textarea></div>
            <div class="col-12">
              <label class="form-label">Policies</label>
              <div class="row g-2">
                <div class="col-12 col-md-4">
                  <div class="form-check">
                    <input id="ev_v_handles_food" class="form-check-input" type="checkbox" disabled {{ $eHandlesFood
                      ? 'checked' : '' }}>
                    <label class="form-check-label" for="ev_v_handles_food">Handles food</label>
                  </div>
                </div>
                <div class="col-12 col-md-4">
                  <div class="form-check">
                    <input id="ev_v_use_funds" class="form-check-input" type="checkbox" disabled {{
                      $eUseInstitutionalFunds ? 'checked' : '' }}>
                    <label class="form-check-label" for="ev_v_use_funds">Uses institutional funds</label>
                  </div>
                </div>
                <div class="col-12 col-md-4">
                  <div class="form-check">
                    <input id="ev_v_external_guest" class="form-check-input" type="checkbox" disabled {{ $eExternalGuest
                      ? 'checked' : '' }}>
                    <label class="form-check-label" for="ev_v_external_guest">External guests</label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" type="button" data-bs-dismiss="modal"
            aria-label="Close details">Close</button>
        </div>
      </div>
    </div>
  </div>

  {{-- Edit/Action drawer (modal) --}}
  <div class="modal fade" id="oversightEdit" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <form class="modal-content" wire:submit.prevent="save">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-journal-text me-2"></i>Request #{{ $editId }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label" for="ev_e_title">Title</label><input id="ev_e_title"
                class="form-control" wire:model.live="eTitle" placeholder="Event title"></div>
            <div class="col-md-3"><label class="form-label" for="ev_e_org">Organization</label><input id="ev_e_org"
                class="form-control" wire:model.live="eOrganization" placeholder="Organization name"></div>
            <div class="col-md-3"><label class="form-label" for="ev_e_venue">Venue</label><input id="ev_e_venue"
                class="form-control" wire:model.live="eVenue" placeholder="Venue name"></div>
            <div class="col-md-3"><label class="form-label" for="ev_e_advisor">Advisor Name</label><input
                id="ev_e_advisor" class="form-control" wire:model.live="eAdvisorName" placeholder="Advisor's full name">
            </div>
            <div class="col-md-3"><label class="form-label" for="ev_e_advisor_email">Advisor Email</label><input
                id="ev_e_advisor_email" class="form-control" wire:model.live="eAdvisorEmail"
                placeholder="advisor@example.edu"></div>
            <div class="col-md-3"><label class="form-label" for="ev_e_advisor_phone">Advisor Phone</label><input
                id="ev_e_advisor_phone" class="form-control" wire:model.live="eAdvisorPhone" placeholder="###-###-####">
            </div>
            <div class="col-md-3"><label class="form-label" for="ev_e_student_number">Student Number</label><input
                id="ev_e_student_number" class="form-control" wire:model.live="eStudentNumber" placeholder="Student ID">
            </div>
            <div class="col-md-3"><label class="form-label" for="ev_e_student_phone">Student Phone</label><input
                id="ev_e_student_phone" class="form-control" wire:model.live="eStudentPhone" placeholder="###-###-####">
            </div>
            <div class="col-md-3"><label class="form-label" for="ev_e_from">From</label><input id="ev_e_from"
                type="datetime-local" class="form-control" wire:model.live="eFrom"></div>
            <div class="col-md-3"><label class="form-label" for="ev_e_to">To</label><input id="ev_e_to"
                type="datetime-local" class="form-control" wire:model.live="eTo"></div>
            <div class="col-md-3"><label class="form-label" for="ev_e_attendees">Attendees</label><input
                id="ev_e_attendees" type="number" class="form-control" min="0" wire:model.live="eAttendees"
                placeholder="0+"></div>
            <div class="col-md-3">
              <label class="form-label" for="ev_e_category">Category</label>
              <select id="ev_e_category" class="form-select" wire:model.live="eCategory">
                @foreach($categories as $cat)
                <option value="{{ $cat }}">{{ $cat }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-12"><label class="form-label" for="ev_e_purpose">Description</label><textarea
                id="ev_e_purpose" class="form-control" rows="3" wire:model.live="ePurpose"
                placeholder="What is this event about?"></textarea></div>

            <div class="col-12">
              <label class="form-label">Policies</label>
              <div class="row g-2">
                <div class="col-12 col-md-4">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="handles_food" wire:model.live="eHandlesFood">
                    <label class="form-check-label" for="handles_food">Handles food</label>
                  </div>
                </div>
                <div class="col-12 col-md-4">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="use_funds"
                      wire:model.live="eUseInstitutionalFunds">
                    <label class="form-check-label" for="use_funds">Uses institutional funds</label>
                  </div>
                </div>
                <div class="col-12 col-md-4">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="external_guest"
                      wire:model.live="eExternalGuest">
                    <label class="form-check-label" for="external_guest">External guests</label>
                  </div>
                </div>
              </div>
            </div>

            {{-- Attached docs, approval history, route/step — placeholders for now --}}
            <div class="col-12">
              <div class="alert alert-secondary small mb-0">
                <strong>Attachments:</strong> (links) • <strong>History:</strong> approvals/denials • <strong>Current
                  Step:</strong> Department/Role
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer d-flex justify-content-between">
          <div class="btn-group">
            <button type="button" class="btn btn-outline-success" wire:click.prevent="approve"
              aria-label="Approve request"><i class="bi bi-check2-circle me-1"></i>Approve</button>
            <button type="button" class="btn btn-outline-danger" wire:click.prevent="deny" aria-label="Deny request"><i
                class="bi bi-x-octagon me-1"></i>Deny</button>
            <button type="button" class="btn btn-outline-secondary" wire:click.prevent="advance"
              aria-label="Advance request"><i class="bi bi-arrow-right-circle me-1"></i>Advance</button>
            <button type="button" class="btn btn-outline-warning" wire:click.prevent="reroute"
              aria-label="Re-route request"><i class="bi bi-shuffle me-1"></i>Re-route</button>
          </div>
          <button class="btn btn-primary" type="submit" aria-label="Save request"><i class="bi me-1"></i>Save</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Justification for save/delete/approve/deny --}}
  <x-justification id="oversightJustify" submit="confirmJustify" model="justification" />

  {{-- Confirm delete --}}
  <x-confirm-delete id="oversightConfirm" title="Delete request" message="Are you sure you want to delete this request?"
    confirm="proceedDelete" />

  {{-- Reroute modal --}}
  <div class="modal fade" id="oversightReroute" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog">
      <form class="modal-content" wire:submit.prevent="confirmReroute">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-shuffle me-2"></i>Re-route Request</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Route to</label>
            <input type="text" class="form-control" placeholder="e.g., Advisor, Manager, Jane Doe"
              wire:model.live="rerouteTo">
            @error('rerouteTo')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal"
            aria-label="Cancel and close">Cancel</button>
          <button class="btn btn-warning" type="submit" aria-label="Confirm re-route"><i
              class="bi bi-shuffle me-1"></i>Re-route</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Advance modal --}}
  <div class="modal fade" id="oversightAdvance" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog">
      <form class="modal-content" wire:submit.prevent="confirmAdvance">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-arrow-right-circle me-2"></i>Advance Request</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Advance to</label>
            <input type="text" class="form-control" placeholder="e.g., Next approver, Advisor, Jane Doe"
              wire:model.live="advanceTo">
            @error('advanceTo')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal"
            aria-label="Cancel and close">Cancel</button>
          <button class="btn btn-secondary" type="submit" aria-label="Confirm advance"><i
              class="bi bi-arrow-right-circle me-1"></i>Advance</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Toast --}}
  <div class="position-fixed top-0 end-0 p-3" style="z-index:1080;" wire:ignore>
    <div id="ovToast" class="toast text-bg-success" role="alert">
      <div class="d-flex">
        <div class="toast-body" id="ovToastMsg">Done</div>
        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('livewire:init', () => {
      const focusMap = new Map();

      function attachHiddenOnce(modalEl, modalId) {
        const onHidden = () => {
          const opener = focusMap.get(modalId);
          if (opener && typeof opener.focus === 'function') {
            setTimeout(() => opener.focus(), 30);
          }
          focusMap.delete(modalId);
          modalEl.removeEventListener('hidden.bs.modal', onHidden);
        };
        modalEl.addEventListener('hidden.bs.modal', onHidden, { once: true });
      }

      Livewire.on('bs:open', ({ id }) => {
        const el = document.getElementById(id);
        if (!el) return;
        const opener = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        focusMap.set(id, opener);
        const modal = new bootstrap.Modal(el);
        modal.show();
        queueMicrotask(() => {
          const first = el.querySelector('input, select, textarea, button, [tabindex]:not([tabindex="-1"])');
          if (first && typeof first.focus === 'function') first.focus();
        });
        attachHiddenOnce(el, id);
      });

      Livewire.on('bs:close', ({ id }) => {
        const el = document.getElementById(id);
        if(!el) return;
        const m = bootstrap.Modal.getInstance(el);
        if(m) m.hide();
      });

      Livewire.on('toast', ({ message }) => {
        document.getElementById('ovToastMsg').textContent = message || 'Done';
        const toastEl = document.getElementById('ovToast');
        const toast = new bootstrap.Toast(toastEl, { autohide: true, delay: 2200 });
        toast.show();
      });

      // No selection logic needed anymore
    });
  </script>
</div>