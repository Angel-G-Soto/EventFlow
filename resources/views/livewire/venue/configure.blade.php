
{{--    View: Configure Venue (Livewire)--}}
{{--    Project: EventFlow (Laravel 12 + Livewire 3 + Bootstrap 5)--}}
{{--    Date: 2025-11-01--}}

{{--    Description:--}}
{{--    - Edit a venue's requirements and availability hours (single opening/closing time).--}}
{{--    - Pairs with the Configure Livewire component for validation and persistence.--}}

{{--    Variables (typical):--}}
{{--    - \App\Models\Venue $venue--}}
{{--    - \Illuminate\Support\Collection|array $requirements--}}
{{--    - string|null $opening_time--}}
{{--    - string|null $closing_time--}}

{{--    Accessibility notes:--}}
{{--    - Each input must have a <label for> and visible required indicator where applicable.--}}
{{--    - Use input type="time" for hours and ensure 24h vs 12h labels are clear.--}}
{{--    - Announce validation errors with role="alert" and aria-describedby pointing to error text.--}}
{{--    - Buttons and links must have discernible text; icon-only buttons need aria-label.--}}


{{-- resources/views/livewire/venues/requirements-editor.blade.php --}}
<div class="container py-4">

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
        <div class="container">
        <a href="{{ route('home') }}"
           class="btn btn-outline-secondary"
           onclick="if (history.length > 1 && document.referrer?.startsWith(location.origin)) { history.back(); return false; }">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header">
                <h2 id="availability-title" class="h5 mb-0">Venue Availability</h2>
            </div>

            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="opens_at" class="form-label">Opens at <span class="text-danger" aria-hidden="true">*</span></label>
                        <input
                            id="opens_at"
                            type="time"
                            class="form-control @error('opens_at') is-invalid @enderror"
                            wire:model.live="opens_at"
                            aria-describedby="opensAtHelp {{ $errors->has('opens_at') ? 'opensAtError' : '' }}"
                            required
                        >
                        <div id="opensAtHelp" class="form-text">Use 24-hour format (e.g., 08:00, 13:30).</div>
                        @error('opens_at')
                        <div id="opensAtError" class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="closes_at" class="form-label">Closes at <span class="text-danger" aria-hidden="true">*</span></label>
                        <input
                            id="closes_at"
                            type="time"
                            class="form-control @error('closes_at') is-invalid @enderror"
                            wire:model.live="closes_at"
                            aria-describedby="closesAtHelp {{ $errors->has('closes_at') ? 'closesAtError' : '' }}"
                            required
                        >
                        <div id="closesAtHelp" class="form-text">Must be after the opening time.</div>
                        @error('closes_at')
                        <div id="closesAtError" class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="card-footer d-flex gap-2 align-items-center">
                <button class="btn btn-primary" wire:click="saveAvailability">
                    Save availability
                </button>
                <span class="text-muted" role="status" aria-live="polite">
            @if ($opens_at && $closes_at)
                        Currently {{ $opens_at }} – {{ $closes_at }}.
                    @else
                        Not configured yet.
                    @endif
        </span>
            </div>
        </div>





    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Requirements for: {{ $venue->name }}</h1>

        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary" type="button" wire:click="addRow">
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
            @foreach ($rows as $i => $row)
                <tr wire:key="req-{{ $row['uuid'] }}">
                    <td>
                        <input
                            type="text"
                            class="form-control @error("rows.$i.name") is-invalid @enderror"
                            placeholder="e.g., Safety Plan"
                            wire:model.lazy="rows.{{ $i }}.name"
                        >
                        @error("rows.$i.name")
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </td>

                    <td>
                        <textarea
                            rows="2"
                            class="form-control @error("rows.$i.description") is-invalid @enderror"
                            placeholder="Brief description…"
                            wire:model.lazy="rows.{{ $i }}.description"
                        ></textarea>
                        @error("rows.$i.description")
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </td>

                    <td>
                        <div class="input-group">
                            <input
                                type="url"
                                class="form-control @error("rows.$i.doc_url") is-invalid @enderror"
                                placeholder="https://…"
                                wire:model.lazy="rows.{{ $i }}.doc_url"
                            >
                            @if (!empty($row['doc_url']))
                                <a class="btn btn-outline-secondary" href="{{ $row['doc_url'] }}" target="_blank" rel="noopener">
                                    Open
                                </a>
                            @endif
                            @error("rows.$i.doc_url")
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </td>

                    <td class="text-end">
                        <button
                            type="button"
                            class="btn btn-outline-danger btn-sm"
                            onclick="if(!confirm('Remove this requirement?')) return;"
                            wire:click="removeRow('{{ $row['uuid'] }}')"
                            title="Remove"
                        >
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
