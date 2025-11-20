{{-- View: Configure Venue (Livewire)--}}
{{-- Project: EventFlow (Laravel 12 + Livewire 3 + Bootstrap 5)--}}
{{-- Date: 2025-11-01--}}

{{-- Description:--}}
{{-- - Edit a venue's requirements and availability hours (single opening/closing time).--}}
{{-- - Pairs with the Configure Livewire component for validation and persistence.--}}

{{-- Variables (typical):--}}
{{-- - \App\Models\Venue $venue--}}
{{-- - \Illuminate\Support\Collection|array $requirements--}}
{{-- - string|null $opening_time--}}
{{-- - string|null $closing_time--}}

{{-- Accessibility notes:--}}
{{-- - Each input must have a <label for> and visible required indicator where applicable.--}}
    {{-- - Use input type="time" for hours and ensure 24h vs 12h labels are clear.--}}
    {{-- - Announce validation errors with role="alert" and aria-describedby pointing to error text.--}}
    {{-- - Buttons and links must have discernible text; icon-only buttons need aria-label.--}}

    <x-slot:pageActions>
        <ul class="navbar-nav mx-auto">
            <li class="nav-item">
                <a class="fw-bold nav-link {{ Route::is('public.calendar') ? 'active' : '' }}"
                    href="{{ route('public.calendar') }}">Home</a>
            </li>
            <li class="nav-item">
                <a class="fw-bold nav-link  {{ Route::is('approver.pending.index') ? 'active' : '' }}"
                    href="{{ route('approver.pending.index') }}">Pending Request</a>
            </li>

            <li class="nav-item">
                <a class="fw-bold nav-link {{ Route::is('approver.history.index') ? 'active' : '' }} "
                    href="{{ route('approver.history.index') }}">Request History</a>
            </li>
            <li class="nav-item">
                <a class="fw-bold nav-link ? 'active' : '' " href="{{ route('venues.manage') }}">My Venues</a>
            </li>

        </ul>

    </x-slot:pageActions>


    {{-- resources/views/livewire/venues/requirements-editor.blade.php --}}
    <div>
        <div class="container py-2">

            @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            {{-- Global validation errors --}}
            @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Please fix the errors below:</strong>
            </div>
            @endif
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h1 class="h4 mb-0">Configure Venue</h1>

                <div class="d-flex gap-2">
                    <a href="{{ route('venues.manage') }}" class="btn btn-secondary"
                        onclick="if (history.length > 1 && document.referrer?.startsWith(location.origin)) { history.back(); return false; }">
                        Back
                    </a>
                </div>
            </div>

            <div class="py-4">

                <div class="card shadow-sm mb-3">
                    <div class="card-header">
                        <h2 id="availability-title" class="h5 mb-0">Venue Description & Weekly Availability</h2>
                    </div>

                    <div class="card-body">
                        <div class="mb-4">
                            <label for="venue_description" class="form-label">Description</label>
                            <textarea id="venue_description"
                                class="form-control @error('description') is-invalid @enderror" rows="3"
                                wire:model.lazy="description"
                                placeholder="Describe the venue, its layout, or important rules."></textarea>
                            @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">Day</th>
                                        <th scope="col">Opens</th>
                                        <th scope="col">Closes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($weekDays as $day)
                                    @php($row = $availabilityForm[$day] ?? ['enabled' => false, 'opens_at' => '',
                                    'closes_at' => ''])
                                    @php($dayId = strtolower($day))
                                    <tr>
                                        <td class="fw-semibold">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="day-{{ $dayId }}"
                                                    wire:model.live="availabilityForm.{{ $day }}.enabled">
                                                <label class="form-check-label" for="day-{{ $dayId }}">{{ $day
                                                    }}</label>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="time"
                                                class="form-control form-control-sm @error('availabilityForm.'.$day.'.opens_at') is-invalid @enderror"
                                                wire:model.lazy="availabilityForm.{{ $day }}.opens_at"
                                                @if(empty($row['enabled'])) disabled @endif>
                                            @error('availabilityForm.'.$day.'.opens_at')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="time"
                                                class="form-control form-control-sm @error('availabilityForm.'.$day.'.closes_at') is-invalid @enderror"
                                                wire:model.lazy="availabilityForm.{{ $day }}.closes_at"
                                                @if(empty($row['enabled'])) disabled @endif>
                                            @error('availabilityForm.'.$day.'.closes_at')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="form-text">
                            Enable the days the venue can be booked and provide 24-hour times (HH:MM). End time must be
                            after the start time.
                        </div>
                        @error('availabilityForm')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="card-footer d-flex gap-2 align-items-center">
                        <button class="btn btn-primary ms-auto" wire:click="saveAvailability">
                            <i class="bi bi-save"></i>
                            Save details
                        </button>
                    </div>
                </div>
            </div>





            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="h4 mb-0">Requirements for: {{ $venue->name }}</h1>

                <div class="d-flex gap-2">
                    <button class="btn btn-danger" type="button" wire:click="confirmClearRequirements">
                        <i class="bi bi-trash"></i> Clear requirements
                    </button>
                    <button class="btn btn-secondary" type="button" wire:click="addRow">
                        <i class="bi bi-plus-lg"></i> Add requirement
                    </button>
                    <button class="btn btn-primary" type="button" wire:click="save">
                        <i class="bi bi-save"></i> Save changes
                    </button>
                </div>
            </div>


            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 20%">Name <span class="text-danger">*</span></th>
                            <th style="width: 45%">Description</th>
                            <th style="width: 25%">Document link (URL)</th>
                            <th style="width: 10%" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php($hasCreatedRequirements = !empty(array_filter($rows, fn($row) => !empty($row['id'] ?? null))))
                        @php($disableRemove = !$hasCreatedRequirements)
                        @foreach ($rows as $i => $row)
                        <tr wire:key="req-{{ $row['uuid'] }}">
                            <td>
                                <input
                                    type="text"
                                    class="form-control @error('rows.'.$i.'.name') is-invalid @enderror"
                                    placeholder="e.g., Safety Plan"
                                    wire:model.lazy="rows.{{ $i }}.name"
                                >
                                @error('rows.'.$i.'.name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </td>

                            <td>
                                <textarea
                                    rows="2"
                                    class="form-control @error('rows.'.$i.'.description') is-invalid @enderror"
                                    placeholder="Brief description…"
                                    wire:model.lazy="rows.{{ $i }}.description"
                                ></textarea>
                                @error('rows.'.$i.'.description')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </td>

                            <td>
                                <div class="input-group">
                                    <input
                                        type="url"
                                        class="form-control @error('rows.'.$i.'.hyperlink') is-invalid @enderror"
                                        placeholder="https://…"
                                        wire:model.lazy="rows.{{ $i }}.hyperlink"
                                    >
                                    @if (!empty($row['hyperlink']))

                                    <a class="btn btn-secondary" href="{{ $row['hyperlink'] }}" target="_blank"
                                        rel="noopener noreferrer">
                                        <i class="bi bi-box-arrow-up-right ms-1" aria-hidden="true"></i>
                                        Open
                                    </a>
                                    @endif
                                    @error('rows.'.$i.'.hyperlink')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </td>

            <td class="text-end">
                <button type="button"
                    class="btn btn-sm {{ $disableRemove ? 'btn-outline-danger' : 'btn-danger' }}"
                    wire:click="confirmRemoveRow('{{ $row['uuid'] }}')"
                    wire:loading.attr="disabled"
                    title="Remove Requirement"
                    @disabled($disableRemove)>
                    <i class="bi bi-trash"></i>
                </button>
            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3 text-muted small">
                <span class="text-danger">*</span> Required field. Empty rows are skipped automatically.
            </div>
        </div>

        <div class="modal fade" id="requirementsConfirm" tabindex="-1" aria-hidden="true"
            aria-labelledby="requirementsConfirmLabel" wire:ignore.self>
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="requirementsConfirmLabel">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            Confirm deletion
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                            wire:click="cancelConfirmDelete"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">{{ $confirmDeleteMessage ?: 'Are you sure you want to continue?' }}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                            wire:click="cancelConfirmDelete">
                            Cancel
                        </button>
                        <button type="button" class="btn btn-danger" wire:click="confirmDelete">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <x-justification id="sharedJustification" submit="submitJustification" model="justification"
            cancelLabel="Cancel" />
    </div>
