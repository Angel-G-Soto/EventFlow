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
              <input id="ev_search" class="form-control" placeholder="Search title, requestor, or organization"
                wire:model.defer="search">
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
            <option value="{{ $st }}">{{ ucwords($st) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label" for="ev_venue">Venue</label>
          <select id="ev_venue" class="form-select" wire:model.live="venue">
            <option value="">All</option>
            @foreach($venues as $v)
            <option value="{{ $v['id'] }}">{{ $v['label'] }}</option>
            @endforeach
          </select>
        </div>
        {{-- Category filter removed intentionally --}}
        {{-- Organization filter removed; included in search --}}
        <div class="col-md-4">
          <label class="form-label" for="ev_from">From</label>
          <input id="ev_from" type="datetime-local"
            class="form-control @error('from') is-invalid @enderror"
            wire:model.defer="from">
          @error('from')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
          <label class="form-label" for="ev_to">To</label>
          <input id="ev_to" type="datetime-local"
            class="form-control @error('to') is-invalid @enderror"
            wire:model.defer="to">
          @error('to')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-md-2 d-flex align-items-end">
          <button class="btn btn-primary w-100" wire:click="applyDateRange" type="button" aria-label="Apply date range">
            Apply Date Range
          </button>
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
      <label class="text-secondary small mb-0 text-black" for="ev_rows">Rows</label>
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
            <th scope="col">ID</th>
            <th scope="col">Title</th>
            <th scope="col">Requestor</th>
            <th scope="col">Organization</th>
            <th scope="col">Venue</th>
            <th scope="col">Status</th>
            <th scope="col">Date/Time</th>
            <th scope="col" class="text-end" style="width:120px;">Actions</th>
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
              @php($st = $r['status'] ?? '')
              <span class="badge {{ $this->statusBadgeClass($st) }}">{{ $st !== '' ? ucwords($st) : 'Unknown' }}</span>
            </td>
            <td>
              <div>{{ $r['from'] }}</div>
              <div class="text-secondary small">→ {{ Str::before($r['to'],' ') }} {{
                Str::after($r['to'],' ') }}</div>
            </td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <button class="btn btn-info" wire:click="openView({{ $r['id'] }})"
                  aria-label="View request {{ $r['id'] }}" title="View request #{{ $r['id'] }}">
                  <i class="bi bi-info-circle"></i>
                </button>
                <button @class([ 'btn' , 'btn-outline-secondary'=> $r['status_is_cancelled'] || $r['status_is_denied']
                  ||
                  $r['status_is_completed'] || $r['status_is_approved'] || $r['status_is_withdrawn'],
                  'btn-secondary' => !($r['status_is_cancelled'] || $r['status_is_denied'] ||
                  $r['status_is_completed'] || $r['status_is_approved'] || $r['status_is_withdrawn']),
                  ]) wire:click="openEdit({{ $r['id'] }})"
                  aria-label="Edit request {{ $r['id'] }}" title="Edit request #{{ $r['id'] }}"
                  @disabled(
                  $r['status_is_cancelled'] || $r['status_is_denied'] || $r['status_is_completed'] ||
                  $r['status_is_approved'] || $r['status_is_withdrawn']
                  )>
                  <i class="bi bi-pencil"></i>
                </button>
                <button @class([ 'btn' , 'btn-outline-danger'=> !$r['status_is_approved'] ||
                  $r['status_is_cancelled'] || $r['status_is_denied'] || $r['status_is_withdrawn'] ||
                  $r['status_is_completed'],
                  'btn-danger' => !(!$r['status_is_approved'] ||
                  $r['status_is_cancelled'] || $r['status_is_denied'] || $r['status_is_withdrawn'] ||
                  $r['status_is_completed']),
                  ]) wire:click="delete({{ $r['id'] }})"
                  @disabled(
                  !$r['status_is_approved'] || $r['status_is_cancelled'] ||
                  $r['status_is_denied'] || $r['status_is_withdrawn'] || $r['status_is_completed']
                  )
                  aria-label="Cancel request {{ $r['id'] }}" title="Cancel request #{{ $r['id'] }}">
                  <i class="bi bi-x-circle"></i>
                </button>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="7" class="text-center text-secondary py-4">No requests found.</td>
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
          <div>
            <h5 class="modal-title mb-0"><i class="bi bi-eye me-2"></i>{{ $eTitle ?: 'View Request' }}</h5>
            <small class="text-muted">Request #{{ $editId }}</small>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-4">
            {{-- Summary --}}
            <div class="col-12">
              <div class="d-flex flex-wrap align-items-baseline gap-2">
                <h5 class="mb-0">{{ $eTitle ?: 'Untitled event' }}</h5>
                <span class="text-muted">Request #{{ $editId }}</span>
              </div>
              <div class="text-muted mt-1">
                <span class="fw-bold">Attendees:</span>
                {{ $eAttendees ?: 0 }} • <span class="fw-bold">Status:</span> {{ ucwords($eStatus) ?: 'Unknown' }}
              </div>
            </div>

            {{-- When & Where --}}
            <div class="col-md-6">
              <h6 class="text-uppercase text-muted small mb-2">When</h6>
              <div class="border rounded p-3">
                <div class="mb-2">
                  <span class="fw-semibold">From:</span>
                  <span class="ms-1">
                    @if($eFrom)
                    {{ \Carbon\Carbon::parse(str_replace('T',' ', $eFrom))->format('M j, Y g:i A') }}
                    @else
                    Not set
                    @endif
                  </span>
                </div>
                <div>
                  <span class="fw-semibold">To:</span>
                  <span class="ms-1">
                    @if($eTo)
                    {{ \Carbon\Carbon::parse(str_replace('T',' ', $eTo))->format('M j, Y g:i A') }}
                    @else
                    Not set
                    @endif
                  </span>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <h6 class="text-uppercase text-muted small mb-2">Where</h6>
              <div class="border rounded p-3">
                <div class="fw-semibold mb-1">{{ $eVenue ?: 'No venue selected' }}</div>
              </div>
            </div>

            {{-- People & Organization --}}
            <div class="col-md-6">
              <h6 class="text-uppercase text-muted small mb-2">Organization & Advisor</h6>
              <div class="border rounded p-3">
                <div class="mb-2">
                  <span class="fw-semibold">Organization:</span>
                  <span class="ms-1">{{ $eOrganization ?: 'N/A' }}</span>
                </div>
                <div class="mb-1">
                  <span class="fw-semibold">Advisor:</span>
                  <span class="ms-1">{{ $eAdvisorName ?: 'N/A' }}</span>
                </div>
                <div class="text-muted small">
                  @if($eAdvisorEmail)
                  <span class="me-2"><i class="bi bi-envelope me-1"></i>{{ $eAdvisorEmail }}</span>
                  @endif
                  @if($eAdvisorPhone)
                  <span><i class="bi bi-telephone me-1"></i>{{ $eAdvisorPhone }}</span>
                  @endif
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <h6 class="text-uppercase text-muted small mb-2">Student</h6>
              <div class="border rounded p-3">
                <div class="mb-1">
                  <span class="fw-semibold">Student Number:</span>
                  <span class="ms-1">{{ $eStudentNumber ?: 'N/A' }}</span>
                </div>
                <div class="mb-0">
                  <span class="fw-semibold">Student Phone:</span>
                  <span class="ms-1">{{ $eStudentPhone ?: 'N/A' }}</span>
                </div>
              </div>
            </div>

            {{-- Categories & Policies --}}
            <div class="col-md-6">
              <h6 class="text-uppercase text-muted small mb-2">Categories</h6>
              <div class="border rounded p-3">
                @php($viewCategories = !empty($selectedCategoryLabels) ? $selectedCategoryLabels : ($eCategory ?
                [$eCategory] : []))
                @if(!empty($viewCategories))
                @foreach($viewCategories as $label)
                <span class="badge text-bg-light border me-1 mb-1">{{ $label }}</span>
                @endforeach
                @else
                <span class="text-muted small">No categories selected.</span>
                @endif
              </div>
            </div>
            <div class="col-md-6">
              <h6 class="text-uppercase text-muted small mb-2">Policies</h6>
              <div class="border rounded p-3">
                <div class="small mb-2">
                  <i class="{{ $eHandlesFood ? 'bi-check-circle-fill' : 'bi-circle text-muted' }} me-1"></i>
                  Handles food: <strong>{{ $eHandlesFood ? 'Yes' : 'No' }}</strong>
                </div>
                <div class="small mb-2">
                  <i class="{{ $eUseInstitutionalFunds ? 'bi-check-circle-fill' : 'bi-circle text-muted' }} me-1"></i>
                  Uses institutional funds: <strong>{{ $eUseInstitutionalFunds ? 'Yes' : 'No' }}</strong>
                </div>
                <div class="small">
                  <i class="{{ $eExternalGuest ? 'bi-check-circle-fill' : 'bi-circle text-muted' }} me-1"></i>
                  External guests: <strong>{{ $eExternalGuest ? 'Yes' : 'No' }}</strong>
                </div>
              </div>
            </div>

            {{-- Description --}}
            <div class="col-12">
              <h6 class="text-uppercase text-muted small mb-2">Description</h6>
              <div class="border rounded p-3 bg-light-subtle">
                <p class="mb-0">{{ $ePurpose ?: 'No description provided.' }}</p>
              </div>
            </div>

            {{-- Documents --}}
            <div class="col-12">
              <h6 class="text-uppercase text-muted small mb-2">Documents</h6>
              <div class="border rounded px-3 py-2 bg-light">
                @if(count($eDocuments))
                <ul class="list-unstyled mb-0 small">
                  @foreach($eDocuments as $doc)
                  <li class="d-flex justify-content-between align-items-center py-1 border-bottom">
                    <span class="text-break">{{ $doc['label'] }}</span>
                    @if(!empty($doc['url']))
                    <a class="text-decoration-none small" href="{{ $doc['url'] }}" target="_blank" rel="noreferrer">
                      View
                    </a>
                    @endif
                  </li>
                  @endforeach
                </ul>
                @else
                <p class="mb-0 small text-muted">No documents uploaded.</p>
                @endif
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
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="ev_e_title">Title</label>
              <input id="ev_e_title" class="form-control @error('eTitle') is-invalid @enderror" wire:model.live="eTitle"
                placeholder="Event title">
              @error('eTitle')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
              <label class="form-label" for="ev_e_org">Organization</label>
              <input id="ev_e_org" class="form-control @error('eOrganization') is-invalid @enderror"
                wire:model.live="eOrganization" placeholder="Organization name">
              @error('eOrganization')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
              <label class="form-label" for="ev_e_venue">Venue</label>
              <select id="ev_e_venue" class="form-select @error('eVenueId') is-invalid @enderror"
                wire:model.live="eVenueId">
                <option value="0">Select a venue</option>
                @foreach(($venues ?? []) as $v)
                <option value="{{ $v['id'] }}">{{ $v['label'] }}</option>
                @endforeach
              </select>
              @error('eVenueId')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3"><label class="form-label" for="ev_e_advisor">Advisor Name</label><input
                id="ev_e_advisor" class="form-control" wire:model.live="eAdvisorName" placeholder="Advisor's full name">
            </div>
            <div class="col-md-3"><label class="form-label" for="ev_e_advisor_email">Advisor Email</label><input
                id="ev_e_advisor_email" class="form-control" wire:model.live="eAdvisorEmail"
                placeholder="advisor@example.edu"></div>
            <div class="col-md-3">
              <label class="form-label" for="ev_e_advisor_phone">Advisor Phone</label>
              <input id="ev_e_advisor_phone" class="form-control @error('eAdvisorPhone') is-invalid @enderror"
                wire:model.live="eAdvisorPhone" placeholder="###-###-####">
              @error('eAdvisorPhone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3"><label class="form-label" for="ev_e_student_number">Student Number</label><input
                id="ev_e_student_number" class="form-control" wire:model.live="eStudentNumber" placeholder="Student ID">
            </div>
            <div class="col-md-3"><label class="form-label" for="ev_e_student_phone">Student Phone</label><input
                id="ev_e_student_phone" class="form-control" wire:model.live="eStudentPhone" placeholder="###-###-####">
            </div>
            <div class="col-md-3">
              <label class="form-label" for="ev_e_from">From</label>
              <input id="ev_e_from" type="datetime-local" class="form-control @error('eFrom') is-invalid @enderror"
                wire:model.live="eFrom">
              @error('eFrom')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
              <label class="form-label" for="ev_e_to">To</label>
              <input id="ev_e_to" type="datetime-local" class="form-control @error('eTo') is-invalid @enderror"
                wire:model.live="eTo">
              @error('eTo')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
              <label class="form-label" for="ev_e_attendees">Attendees</label>
              <input id="ev_e_attendees" type="number" class="form-control @error('eAttendees') is-invalid @enderror"
                min="1" wire:model.live="eAttendees" placeholder="0+">
              @error('eAttendees')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <style>
                .compact-multiselect .badge,
                .compact-multiselect input,
                .compact-multiselect .form-control {
                  font-size: 0.85rem;
                  padding: 0.12rem 0.4rem;
                }
              </style>
              <div class="compact-multiselect">
                <div class="d-flex justify-content-between align-items-baseline">
                  <label class="form-label mb-1">Event Categories</label>
                  <button type="button" class="btn btn-link btn-sm p-0" wire:click="clearCategories"
                    @disabled(empty($eCategoryIds))>
                    Clear selection
                  </button>
                </div>
                <div class="card border shadow-sm">
                  <div class="card-body">
                    <div class="row g-2 align-items-center mb-2">
                      <div class="col-md-8">
                        <input type="search" class="form-control"
                          placeholder="Search categories (e.g., Workshop, Fundraiser)"
                          wire:model.live.debounce.300ms="categorySearch">
                      </div>
                      <div class="col-md-4 text-md-end">
                        <small class="text-muted">
                          {{ count($eCategoryIds) }} selected
                        </small>
                      </div>
                    </div>
                    @if (!empty($selectedCategoryLabels))
                    <div class="mb-2">
                      <small class="text-muted d-block mb-1">Selected</small>
                      @foreach ($selectedCategoryLabels as $id => $label)
                      <span class="badge rounded-pill text-bg-light border me-1 mb-1">
                        {{ $label }}
                        <button type="button" class="btn btn-link btn-sm text-decoration-none ps-1"
                          wire:click="removeCategory({{ (int) $id }})" aria-label="Remove {{ $label }}">&times;</button>
                      </span>
                      @endforeach
                    </div>
                    @endif
                    <div class="row row-cols-1 row-cols-md-2 g-2">
                      @forelse ($filteredCategories as $cat)
                      <div class="col">
                        <label class="border rounded p-2 h-100 d-flex gap-2 align-items-center shadow-sm">
                          <input type="checkbox" class="form-check-input-md mt-1" value="{{ $cat['id'] }}"
                            wire:model.live="eCategoryIds">
                          <span>
                            <span class="fw-semibold d-block">{{ $cat['name'] }}</span>
                            @if (!empty($cat['description'] ?? null))
                            <small class="text-muted">{{ $cat['description'] }}</small>
                            @endif
                          </span>
                        </label>
                      </div>
                      @empty
                      <div class="col">
                        <p class="text-muted mb-0">No categories match your search.</p>
                      </div>
                      @endforelse
                    </div>
                  </div>
                </div>
                @error('eCategoryIds') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
              </div>
            </div>
            <div class="col-12">
              <label class="form-label" for="ev_e_purpose">Description</label>
              <textarea id="ev_e_purpose" class="form-control @error('ePurpose') is-invalid @enderror" rows="3"
                wire:model.live="ePurpose" placeholder="What is this event about?"></textarea>
              @error('ePurpose')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

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

            {{-- Attached docs, approval history, route/step placeholder removed --}}
          </div>
        </div>
        <div class="modal-footer d-flex justify-content-between">
          <div class="btn-group">
            <button type="button" class="btn btn-success" wire:click.prevent="approve" aria-label="Approve request"><i
                class="bi bi-check2-circle me-1"></i>Approve</button>
            <button type="button" class="btn btn-danger" wire:click.prevent="deny" aria-label="Deny request"><i
                class="bi bi-x-octagon me-1"></i>Deny</button>
            <button type="button" class="btn btn-secondary" wire:click.prevent="advance" aria-label="Advance request"><i
                class="bi bi-arrow-right-circle me-1"></i>Advance</button>
          </div>
          <button class="btn btn-primary" type="submit" aria-label="Save request"><i class="bi me-1"></i>Save</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Justification for save/delete/approve/deny --}}
  <x-justification id="oversightJustify" submit="confirmJustify" model="justification" />

  {{-- Confirm cancel --}}
  <x-confirm-cancel
    id="oversightConfirm"
    title="Cancel request"
    message="Are you sure you want to cancel this request?"
    confirm="proceedDelete"
  />

  {{-- Reroute disabled --}}

  {{-- Advance modal (confirmation) --}}
  <div class="modal fade" id="oversightAdvance" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog">
      <form class="modal-content" wire:submit.prevent="confirmAdvance">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-arrow-right-circle me-2"></i>Confirm Advance</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="mb-0">Are you sure you want to advance this request to the next approval step?</p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" type="button" data-bs-dismiss="modal"
            aria-label="Cancel and close">Cancel</button>
          <button class="btn btn-secondary" type="submit" aria-label="Confirm advance"><i
              class="bi bi-arrow-right-circle me-1"></i>Confirm Advance</button>
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
