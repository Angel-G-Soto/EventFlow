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
            {{-- Global validation errors --}}
            @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Please fix the errors below:</strong>
            </div>
            @endif
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h1 class="h4 mb-0 d-flex flex-wrap align-items-center gap-2">
                    <span>Configure Venue:</span>
                    <span class="text-muted fw-normal">
                        {{ $venue->name }}@if(!empty($venue->code)) ({{ $venue->code }})@endif
                    </span>
                </h1>

                <div class="d-flex gap-2">

                    <a href="{{ route('venues.manage') }}"
                       class="btn btn-secondary"
                        onclick="if (history.length > 1 && document.referrer?.startsWith(location.origin)) { history.back(); return false; }">
                        <i class="bi bi-arrow-left"></i>
                        Back
                    </a>
                </div>
            </div>

            <div class="py-4">

                <div class="card shadow-sm mb-3">
                    <div class="card-header d-flex align-items-center gap-2 justify-content-between flex-wrap">
                        <h2 id="availability-title" class="h5 mb-0">Venue Description & Weekly Availability</h2>
                        <button class="btn btn-primary" wire:click="saveAvailability" @disabled(! $this->detailsDirty)>
                            <i class="bi bi-save me-1"></i>
                            Save details
                        </button>
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
                                    @php
                                    $row = $availabilityForm[$day] ?? ['enabled' => false, 'opens_at' => '', 'closes_at' => ''];
                                    $dayId = strtolower($day);
                                    @endphp
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

                    <div class="card-footer p-0 border-0" aria-hidden="true"></div>
                </div>
            </div>


            <div class="card shadow-sm">
                <div class="card-header">
                    <div class="d-flex flex-column flex-xl-row align-items-start align-items-xl-center justify-content-between gap-3">
                        <div>
                            <h2 class="h5 mb-0">Use Requirements</h2>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn btn-secondary" type="button" wire:click="addRow"
                                wire:loading.attr="disabled" wire:target="addRow">
                                <i class="bi bi-plus-lg"></i>
                                Add requirement
                            </button>
                            {{-- <button class="btn btn-danger" type="button"
                                wire:click="confirmClearRequirements" wire:loading.attr="disabled"
                                wire:target="confirmClearRequirements">
                                <i class="bi bi-trash"></i>
                                Clear all
                            </button> --}}
                            <button class="btn btn-primary" type="button" wire:click="save" wire:loading.attr="disabled"
                                wire:target="save" @disabled(! $this->requirementsDirty)>
                                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"
                                    wire:loading wire:target="save"></span>
                                <i class="bi bi-save me-1" wire:loading.remove wire:target="save"></i>
                                Save changes
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="vstack gap-3">
                                @forelse ($rows as $i => $row)
                                @php
                                $rowUuid = $row['uuid'];
                                @endphp
                                <div class="card border-0 shadow-sm" wire:key="req-{{ $rowUuid }}">
                                    <div class="card-header bg-white d-flex flex-column flex-md-row justify-content-between gap-2">
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <span class="text-uppercase small text-muted fw-semibold">Requirement {{ $i + 1 }}</span>
                                            @if (!empty($row['id']))
                                            <span class="text-success small">
                                                <i class="bi bi-check-circle-fill me-1"></i>Saved to venue
                                            </span>
                                            @else
                                            <span class="text-muted small">Draft only. Don't forget to save.</span>
                                            @endif
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-sm btn-danger"
                                                wire:click="confirmRemoveRow('{{ $rowUuid }}')"
                                                wire:loading.attr="disabled"
                                                title="Remove requirement {{ $i + 1 }}">
                                                <i class="bi bi-trash"></i>
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="req-name-{{ $rowUuid }}" class="form-label">Requirement title
                                                <span class="text-danger">*</span></label>
                                            <input id="req-name-{{ $rowUuid }}" type="text"
                                                class="form-control @error('rows.'.$i.'.name') is-invalid @enderror"
                                                placeholder="e.g., Safety Plan, Risk Assessment"
                                                wire:model.lazy="rows.{{ $i }}.name">
                                            @error('rows.'.$i.'.name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="req-description-{{ $rowUuid }}" class="form-label">Guidance or
                                                checklist</label>
                                            <textarea id="req-description-{{ $rowUuid }}" rows="3"
                                                class="form-control @error('rows.'.$i.'.description') is-invalid @enderror"
                                                placeholder="Briefly describe what the requester must do."
                                                wire:model.lazy="rows.{{ $i }}.description"></textarea>
                                            @error('rows.'.$i.'.description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-2">
                                            <label for="req-link-{{ $rowUuid }}" class="form-label">Document link
                                                (URL)</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-link-45deg"
                                                        aria-hidden="true"></i></span>
                                                <input id="req-link-{{ $rowUuid }}" type="url"
                                                    class="form-control @error('rows.'.$i.'.hyperlink') is-invalid @enderror"
                                                    placeholder="https://example.edu/requirements.pdf"
                                                    wire:model.lazy="rows.{{ $i }}.hyperlink">
                                                @if (!empty($row['hyperlink']))
                                                <a class="btn btn-outline-secondary" href="{{ $row['hyperlink'] }}"
                                                    target="_blank" rel="noopener noreferrer">
                                                    Open
                                                </a>
                                                @endif
                                            </div>
                                            @error('rows.'.$i.'.hyperlink')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">Attach a policy PDF, web page, or shared doc the
                                                requester must review.</div>
                                        </div>
                                    </div>
                                </div>
                                @empty
                                <div class="text-center text-muted py-5 border rounded-3">
                                    <p class="mb-1 fw-semibold">No requirements yet</p>
                                    <p class="mb-0">Use the buttons above to add your first requirement.</p>
                                </div>
                                @endforelse

                        <p class="text-muted small mb-0">
                            <span class="text-danger">*</span> Required field. All updates are saved to the venue
                            after you provide justification and select <strong>Save changes</strong>.
                        </p>
                    </div>
                </div>
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
