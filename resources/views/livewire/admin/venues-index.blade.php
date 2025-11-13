<div>
  {{-- Local helper for 12-hour time formatting (display only) --}}
  @php
  $fmtTime = function($t) {
  if(!is_string($t) || trim($t) === '') return '';
  try {
  return \Carbon\Carbon::createFromFormat('H:i', $t)->format('g:i A');
  } catch (\Exception $e) {
  return $t; // fallback if parse fails
  }
  };
  @endphp
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Venues</h1>

    <div class="d-none d-md-flex gap-2">
      <button class="btn btn-primary btn-sm" wire:click="openCsvModal" type="button" aria-label="Open CSV upload modal">
        <i class="bi bi-upload me-1"></i> Add Venues by CSV
      </button>
    </div>
  </div>

  {{-- Import status banner (polls until finished) --}}
  @if($importKey)
  <div class="alert alert-info d-flex align-items-center gap-2" role="status" wire:poll.2s="checkImportStatus">
    <div class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></div>
    <div>
      Import in progress: <strong>{{ $importStatus ?? 'queued' }}</strong>
    </div>
  </div>
  @endif

  {{-- Import error banner (dismissible) --}}
  @if(!empty($importErrorMsg))
  <div class="alert alert-danger d-flex align-items-start justify-content-between" role="alert">
    <div class="me-3">
      <strong>Import failed:</strong>
      <strong><span class="ms-1">{{ $importErrorMsg }}</span></strong>
    </div>
    <button type="button" class="btn-close" aria-label="Close" wire:click="$set('importErrorMsg', null)"></button>
  </div>
  @endif

  {{-- Filters --}}
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2">
        <div class="col-12 col-md-4">
          <label class="form-label" for="venue_search">Search</label>
          <form wire:submit.prevent="applySearch">
            <div class="input-group">
              <input id="venue_search" type="text" class="form-control"
                placeholder="Search by name, code, or manager..." wire:model.defer="search">
              <button class="btn btn-secondary" type="submit" aria-label="Search">
                <i class="bi bi-search"></i>
              </button>
            </div>
          </form>
        </div>

        <div class="col-6 col-md-2">
          <label class="form-label" for="venue_department">Department</label>
          <select id="venue_department" class="form-select" wire:model.live="department">
            <option value="">All</option>
            @foreach($departments as $dept)
            <option value="{{ $dept }}">{{ $dept }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-6 col-md-2">
          <label class="form-label" for="venue_cap_min">Cap. Min</label>
          <input id="venue_cap_min" type="number" class="form-control" wire:model.live="capMin" min="0"
            placeholder="Min capacity">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label" for="venue_cap_max">Cap. Max</label>
          <input id="venue_cap_max" type="number" class="form-control" wire:model.live="capMax" min="0"
            placeholder="Max capacity">
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
      <label class="text-secondary small mb-0 text-black" for="venue_rows">Rows</label>
      <select id="venue_rows" class="form-select form-select-sm" style="width:auto" wire:model.live="pageSize">
        <option>10</option>
        <option>25</option>
        <option>50</option>
      </select>
    </div>
  </div>

  {{-- Table --}}
  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th scope="col">
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
            <th>Department</th>
            <th>Venue Code</th>
            <th>
              <button class="btn btn-link p-0 text-decoration-none text-black text-nowrap fw-bold"
                wire:click="sortBy('capacity')" aria-label="Sort by capacity">
                <span class="d-inline-flex align-items-center gap-1">
                  Capacity
                  @if($sortField === 'capacity')
                  @if($sortDirection === 'asc')
                  <i class="bi bi-arrow-up-short" aria-hidden="true"></i>
                  @else
                  <i class="bi bi-arrow-down-short" aria-hidden="true"></i>
                  @endif
                  @else
                  <i class="bi bi-arrow-down-up text-muted" aria-hidden="true"></i>
                  @endif
                </span>
              </button>
            </th>
            {{--<th>Manager</th>--}}
            <th>Availability</th>
            <th class="text-end" style="width:140px;">Actions</th>
          </tr>
        </thead>

        <tbody>
          @forelse($rows as $v)
          <tr>
            <td>{{ $v['name'] }}</td>
            <td>{{ $v['department'] }}</td>
            <td>{{ $v['room'] }}</td>
            <td>{{ $v['capacity'] }}</td>
            {{-- <td> {{ $v['manager'] }} </td> --}}
            <td class="text-truncate" style="max-width:220px;">
              @php $hasOC = !empty($v['opening'] ?? '') || !empty($v['closing'] ?? ''); @endphp
              @if ($hasOC)
              <div class="small">{{ $fmtTime($v['opening'] ?? '') }} – {{ $fmtTime($v['closing'] ?? '') }}</div>
              @elseif (isset($v['timeRanges']) && is_array($v['timeRanges']) && count($v['timeRanges']) > 0)
              @foreach ($v['timeRanges'] as $tr)
              <div class="small">
                {{ $fmtTime($tr['from'] ?? '') }} – {{ $fmtTime($tr['to'] ?? '') }}
                @if (!empty($tr['reason']))
                ({{ $tr['reason'] }})
                @endif
              </div>
              @endforeach
              @else
              <span class="text-muted small">No availability</span>
              @endif
            </td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-secondary" wire:click="showDetails({{ $v['id'] }})"
                  aria-label="Show details for venue {{ $v['name'] }}" title="Show details">
                  <i class="bi bi-info-circle"></i>
                </button>
                {{--<button class="btn btn-outline-secondary" wire:click="openEdit({{ $v['id'] }})"
                  aria-label="Edit venue {{ $v['name'] }}" title="Edit venue {{ $v['name'] }}">
                  <i class="bi bi-pencil"></i>
                </button>--}}
                <button class="btn btn-outline-danger" wire:click="delete({{ $v['id'] }})"
                  aria-label="Delete venue {{ $v['name'] }}" title="Delete venue {{ $v['name'] }}">
                  <i class="bi bi-trash3"></i>
                </button>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="6" class="text-center text-secondary py-4">No venues found.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Footer / Pager --}}
    <div class="card-footer d-flex align-items-center justify-content-between">
      <small class="text-secondary">
        {{ method_exists($rows, 'total') ? $rows->total() : count($rows) }} results
      </small>
      {{ $rows->onEachSide(1)->links('partials.pagination') }}
    </div>
  </div>

  {{-- Create/Edit Venue Modal with inline Availability editor --}}
  {{-- CSV Upload Modal --}}
  <div class="modal fade" id="csvModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
      <form class="modal-content" wire:submit.prevent="uploadCsv">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Add Venues by CSV</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label required" for="csv_file">CSV File</label>
            <input id="csv_file" type="file" class="form-control" accept=".csv,text/csv" wire:model="csvFile">
            <small class="text-muted d-block mt-1">Required headers: name, room_code, department_name, capacity,
              final_exams_capacity. Optional flags: allow_teaching_with_multimedia, allow_teaching_with_computers,
              allow_teaching, allow_teaching_online.</small>
            <div class="mt-2">
              <!-- True percent-based upload progress -->
              <div id="csvProgressContainer" class="progress d-none" aria-hidden="true">
                <div id="csvProgressBar" class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0"
                  aria-valuemin="0" aria-valuemax="100">0%</div>
              </div>
              <!-- Server-side processing indicator (after upload submit).
                   Guarded by $csvFile so it doesn't show unless a file was selected. -->
              {{-- @if($csvFile)
              <div class="d-flex align-items-center gap-2 mt-2" wire:loading.delay wire:target="uploadCsv">
                <div class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></div>
                <small class="text-muted">Processing import…</small>
              </div>
              @endif --}}
            </div>
            <script>
              (function attachCsvProgress() {
                const bind = () => {
                  const input = document.getElementById('csv_file');
                  const container = document.getElementById('csvProgressContainer');
                  const bar = document.getElementById('csvProgressBar');
                  if (!input || !container || !bar) return;
                  if (input.dataset.progressBound === '1') return; // guard against duplicate listeners
                  input.dataset.progressBound = '1';

                  const show = () => { container.classList.remove('d-none'); container.removeAttribute('aria-hidden'); };
                  const hide = () => { container.classList.add('d-none'); container.setAttribute('aria-hidden', 'true'); };
                  const setProgress = (p) => {
                    const pct = Math.max(0, Math.min(100, parseInt(p || 0, 10)));
                    bar.style.width = pct + '%';
                    bar.setAttribute('aria-valuenow', pct);
                    bar.textContent = pct + '%';
                  };

                  input.addEventListener('livewire-upload-start', () => {
                    setProgress(0);
                    show();
                  });
                  input.addEventListener('livewire-upload-progress', (e) => {
                    setProgress(e.detail && e.detail.progress);
                  });
                  input.addEventListener('livewire-upload-error', () => {
                    hide();
                  });
                  input.addEventListener('livewire-upload-finish', () => {
                    setProgress(100);
                    // Briefly show 100% then hide
                    setTimeout(hide, 700);
                  });
                };

                // Livewire v3 fires 'livewire:init'; keep v2 fallback for safety
                document.addEventListener('livewire:init', bind);
                document.addEventListener('livewire:load', bind);
                // In case the script runs after init, try immediate bind too
                if (document.readyState === 'complete' || document.readyState === 'interactive') {
                  setTimeout(bind, 0);
                }
              })();
            </script>
          </div>
          @error('csvFile') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal"
            aria-label="Cancel and close">Cancel</button>
          <button class="btn btn-primary" type="submit" aria-label="Upload CSV" @disabled(!$csvFile)
            wire:loading.attr="disabled" wire:target="csvFile,uploadCsv">
            <i class="bi bi-upload me-1"></i>Upload
          </button>
        </div>
      </form>
    </div>
  </div>
  <div class="modal fade" id="venueModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <form class="modal-content" wire:submit.prevent="save">
        <div class="modal-header">
          <h5 class="modal-title">{{ $editId ? 'Edit Venue' : 'New Venue' }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label required" for="v_name">Name</label>
              <input id="v_name" class="form-control" required wire:model.live="vName"
                placeholder="Venue name (e.g., Main Auditorium)">
            </div>
            <div class="col-md-3">
              <label class="form-label required" for="v_department">Department</label>
              <select id="v_department" class="form-select" wire:model.live="vDepartment" required>
                <option value="">Select department</option>
                @foreach($departments as $dept)
                <option value="{{ $dept }}">{{ $dept }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label required" for="v_room">Venue Code</label>
              <input id="v_room" class="form-control" required wire:model.live="vRoom"
                placeholder="Code (e.g., EN-101)">
            </div>
            <div class="col-md-2">
              <label class="form-label required" for="v_capacity">Capacity</label>
              <input id="v_capacity" type="number" min="0" class="form-control" required wire:model.live="vCapacity"
                placeholder="0+">
            </div>
            <div class="col-md-3">
              <label class="form-label" for="v_manager">Manager</label>
              <input id="v_manager" class="form-control" wire:model.live="vManager" placeholder="username/email">
            </div>
            <div class="col-md-3">
              <label class="form-label" for="v_status">Status</label>
              <select id="v_status" class="form-select" wire:model.live="vStatus">
                <option>Active</option>
                <option>Inactive</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label">Features/Resources</label>
              <div class="row g-2">
                @php $features = ['Allow Teaching Online','Allow Teaching With Multimedia','Allow Teaching with
                computer','Allow Teaching']; @endphp
                @foreach($features as $f)
                <div class="col-6 col-md-4 col-lg-3">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="{{ $f }}" wire:model.live="vFeatures"
                      id="feat_{{ $loop->index }}">
                    <label class="form-check-label" for="feat_{{ $loop->index }}">{{ $f }}</label>
                  </div>
                </div>
                @endforeach
              </div>
            </div>

            <div class="col-12">
              <div class="d-flex align-items-center justify-content-between">
                <h6 class="mb-2">Availability dates</h6>
                <button class="btn btn-outline-secondary btn-sm" type="button" wire:click="addTimeRange">
                  <i class="bi bi-plus-lg me-1"></i>Add
                </button>
              </div>
              <div class="table-responsive">
                <table class="table table-sm align-middle">
                  <thead>
                    <tr>
                      <th style="width:180px;">From</th>
                      <th style="width:180px;">To</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($timeRanges as $i=>$b)
                    <tr>
                      <td><input type="time" class="form-control form-control-sm" aria-label="From time"
                          wire:model.live="timeRanges.{{ $i }}.from"></td>
                      <td><input type="time" class="form-control form-control-sm" aria-label="To time"
                          wire:model.live="timeRanges.{{ $i }}.to">
                      </td>
                      <td class="align-top">
                        @error('timeRanges.'.$i.'.from')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                        @error('timeRanges.'.$i.'.to')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                      </td>
                      <td>
                        <button type="button" class="btn btn-outline-danger btn-sm"
                          aria-label="Remove time range {{ $i }}" wire:click="removeTimeRange({{ $i }})">
                          <i class="bi bi-x-lg"></i>
                        </button>
                      </td>
                    </tr>
                    @empty
                    <tr>
                      <td colspan="4" class="text-secondary">No Availability dates.</td>
                    </tr>
                    @endforelse
                  </tbody>
                </table>
                <div class="form-text">
                  Enter availability times as 24-hour HH:MM. End time must be after start time.
                </div>
              </div>
            </div>

          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal"
            aria-label="Cancel and close">Cancel</button>
          <button class="btn btn-primary" type="submit" aria-label="Save venue"><i class="bi me-1"></i>Save</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Justification for save/delete --}}
  <x-justification id="venueJustify" submit="confirmJustify" model="justification" />

  {{-- Confirm delete --}}
  <x-confirm-delete id="venueConfirm" title="Delete venue" message="Are you sure you want to delete this venue?"
    confirm="proceedDelete" />

  {{-- Toast --}}
  <div class="position-fixed top-0 end-0 p-3" style="z-index:1080;" wire:ignore>
    <div id="venueToast" class="toast text-bg-success" role="alert">
      <div class="d-flex">
        <div class="toast-body" id="venueToastMsg">Done</div>
        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  </div>

  {{-- Venue Details Modal --}}
  <div class="modal fade" id="venueDetails" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Venue Details @if(!empty($details['id']))#{{ $details['id'] }}@endif</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          @if(!empty($details))
          <dl class="row">
            <dt class="col-sm-3">Name</dt>
            <dd class="col-sm-9">{{ $details['name'] ?? '—' }}</dd>

            <dt class="col-sm-3">Department</dt>
            <dd class="col-sm-9">{{ $details['department'] ?? '—' }}</dd>

            <dt class="col-sm-3">Venue Code</dt>
            <dd class="col-sm-9">{{ $details['code'] ?? '—' }}</dd>

            <dt class="col-sm-3">Capacity</dt>
            <dd class="col-sm-9">{{ $details['capacity'] ?? '—' }}</dd>

            <dt class="col-sm-3">Manager</dt>
            <dd class="col-sm-9">{{ $details['manager'] ?? '—' }}</dd>

            <dt class="col-sm-3">Features</dt>
            <dd class="col-sm-9">
              @php($fs = $details['features'] ?? [])
              @if(is_array($fs) && count($fs))
              <ul class="mb-0 ps-3">
                @foreach($fs as $f)
                <li>{{ $f }}</li>
                @endforeach
              </ul>
              @else
              <span class="text-muted">None</span>
              @endif
            </dd>

            <dt class="col-sm-3">Availability</dt>
            <dd class="col-sm-9">
              @php($availabilitySlots = $details['availabilities'] ?? [])
              @if(!empty($availabilitySlots))
              <ul class="list-unstyled mb-0">
                @foreach($availabilitySlots as $slot)
                <li class="d-flex justify-content-between border-bottom py-1">
                  <span class="fw-semibold">{{ $slot['day'] }}</span>
                  <span>{{ $fmtTime($slot['opens']) }} – {{ $fmtTime($slot['closes']) }}</span>
                </li>
                @endforeach
              </ul>
              @else
              <span class="text-muted">No availability configured.</span>
              @endif
            </dd>
          </dl>
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
    (function () {
      function ensureBodyScrollable() {
        try {
          // If no modal remains visible, restore scrolling and remove stray backdrops
          const anyVisible = document.querySelector('.modal.show');
          if (!anyVisible) {
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
            document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
          }
        } catch (_) { /* noop */ }
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
          // Safety: ensure scrolling restored after hide
          setTimeout(ensureBodyScrollable, 0);
        });
      });
    })();
  </script>
</div>
