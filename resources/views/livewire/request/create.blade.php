
{{--    View: Create (Livewire)--}}
{{--    Project: EventFlow (Laravel 12 + Livewire 3 + Bootstrap 5)--}}
{{--    Date: 2025-11-01--}}

{{--    Description:--}}
{{--    - Presents an accessible form to create a new resource (e.g., Event, Venue, Requirement).--}}
{{--    - Works with a Livewire component that validates and persists data.--}}

{{--    Variables (typical):--}}
{{--    - array<string,mixed> $form                   Reactive form state--}}
{{--    - \Illuminate\Support\Collection|array $venues      Options for venue select--}}
{{--    - \Illuminate\Support\Collection|array $categories  Options for categories--}}
{{--    - bool $isBusy                                Optional loading flag--}}

{{--    Accessibility notes:--}}
{{--    - Use <label for> for each input; include required asterisks via ::after and aria-required="true".--}}
{{--    - Announce validation errors near inputs with role="alert" and aria-describedby.--}}
{{--    - Ensure the primary submit <button> has type="submit" and discernible text.--}}



<x-slot:pageActions>
    <ul class="navbar-nav mx-auto">
        <li class="nav-item">
            <a class="fw-bold nav-link ? 'active' : '' " href="{{ route('public.calendar') }}">Home</a>
        </li>

        <li class="nav-item">
            <a class="fw-bold nav-link ? 'active' : '' " href="{{ route('approver.history.index') }}">Request History</a>
        </li>
    </ul>
</x-slot:pageActions>

<div>
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @csrf

    {{-- Step pills --}}
    <style>
        .request-step-nav .nav-link {
            font-weight: 600;
            border: none;
            color: #0a214a;
            background-color: transparent;
        }

        .request-step-nav .nav-link:not(.active) {
            background-color: transparent;
        }

        .request-step-nav .nav-link:focus-visible {
            outline: none;
            box-shadow: 0 0 0 2px rgba(10, 33, 74, 0.35);
        }

        .request-step-nav .nav-link.active {
            background-color: #0a214a;
            color: #fff;
        }

        .event-form-link-btn {
            color: #1557b0;
            text-decoration: none;
        }

        .event-form-link-btn:hover,
        .event-form-link-btn:focus-visible {
            color: #0d3f7a;
            text-decoration: underline;
        }
    </style>
    <ul class="nav nav-pills mb-4 request-step-nav" aria-label="Event creation steps">
        <li class="nav-item"><span class="nav-link {{ $step === 1 ? 'active' : '' }}">1. Event</span></li>
        <li class="nav-item"><span class="nav-link {{ $step === 2 ? 'active' : '' }}">2. Venue</span></li>
        <li class="nav-item"><span class="nav-link {{ $step === 3 ? 'active' : '' }}">3. Documents</span></li>
    </ul>
    {{-- STEP 1 --}}
    @if ($step === 1)

        <h2 id="eventRequestStep1Heading" class="visually-hidden">Event details</h2>
        <p class="text-muted small">
            <span class="text-danger" aria-hidden="true">*</span>
            <span class="visually-hidden">required</span>
            Fields marked with an asterisk are required.
        </p>
        <form wire:submit.prevent="next" data-prevent-enter-submit aria-labelledby="eventRequestStep1Heading">

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label required">Student Phone</label>
                    <input type="text" class="form-control" wire:model.defer="creator_phone_number" placeholder="e.g., 787-777-7777">
                    @error('creator_phone_number') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label required">Student ID / Number</label>
                    <input type="text" class="form-control" wire:model.defer="creator_institutional_number" placeholder="e.g., 802201234">
                    @error('creator_institutional_number') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label class="form-label required">Event Title</label>
                    <input type="text" class="form-control" wire:model.defer="title" placeholder="e.g., Campus Leadership Summit">
                    @error('title') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label class="form-label required">Event description</label>
                    <textarea class="form-control" rows="6" maxlength="2000" wire:model.defer="description" placeholder="e.g., Describe the event purpose and highlights"></textarea>
                    @error('description') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Multimedia equipment needed</label>
                    <textarea
                        class="form-control"
                        rows="4"
                        maxlength="2000"
                        wire:model.defer="multimedia_equipment"
                        placeholder="List any audio/visual or other multimedia equipment you need ready before the event (e.g., projector, microphones, speakers)."
                    ></textarea>
                    <small class="text-muted">Optional: share the multimedia setup you need pre-installed.</small>
                    @error('multimedia_equipment') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-12">
                    <label class="form-label">Number of Guests</label>
                    <input type="text" class="form-control" wire:model.defer="guest_size" placeholder="e.g., 40">
                    @error('guest_size')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror

                </div>

                <div class="col-md-6">
                    <label class="form-label required">Start time</label>
                    <input type="datetime-local"
                           class="form-control"
                           wire:model.live="start_time"
                           min="{{ now()->format('Y-m-d\\TH:i') }}"
                           placeholder="e.g., 2025-05-15T09:00">
                    @error('start_time') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label required">End time</label>
                    <input type="datetime-local"
                           class="form-control"
                           wire:model.live="end_time"
                           min="{{ now()->format('Y-m-d\\TH:i') }}"
                           placeholder="e.g., 2025-05-15T12:00">
                    @error('end_time') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-baseline">
                        <label class="form-label">Event Categories</label>
                        <button type="button" class="btn btn-link btn-sm p-0 event-form-link-btn" wire:click="clearCategories" @disabled(empty($category_ids))>
                            Clear selection
                        </button>
                    </div>

                    <div class="card border shadow-sm">
                        <div class="card-body">
                            <div class="row g-2 align-items-center mb-3">
                                <div class="col-md-8 col-lg-9">
                                    <div class="input-group">
                                        <input
                                            type="search"
                                            class="form-control"
                                            placeholder="Search categories (e.g., Workshop, Fundraiser)"
                                            wire:model.live.debounce.300ms="categorySearchInput"
                                            data-allow-enter-submit="true"
                                        >
                                        <span
                                            class="input-group-text bg-transparent border-0"
                                            wire:loading.remove
                                            wire:target="categorySearchInput"
                                        >
                                            <i class="bi bi-search text-muted"></i>
                                        </span>
                                    </div>
                                    <small class="text-muted">Results update automatically as you type.</small>
                                </div>
                                <div class="col-md-4 col-lg-3 text-md-end">
                                    <small class="text-muted">
                                        {{ count($category_ids) }} selected
                                    </small>
                                </div>
                            </div>

                            @if (!empty($selectedCategoryLabels))
                                <div class="mb-3">
                                    <small class="text-muted d-block mb-1">Selected</small>
                                    @foreach ($selectedCategoryLabels as $id => $label)
                                        <span class="badge rounded-pill text-bg-light border me-1 mb-1">
                                            {{ $label }}
                                            <button
                                                type="button"
                                                class="btn btn-link btn-sm text-decoration-none ps-1"
                                                wire:click="removeCategory({{ (int) $id }})"
                                                aria-label="Remove {{ $label }}"
                                            >&times;</button>
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            <div class="row row-cols-1 row-cols-md-2 g-2">
                                @forelse ($filteredCategories as $cat)
                                    <div class="col">
                                        <label class="border rounded p-3 h-100 d-flex gap-3 align-items-start shadow-sm">
                                            <input
                                                type="checkbox"
                                                class="form-check-input mt-1"
                                                value="{{ $cat['id'] }}"
                                                wire:model.live="category_ids"
                                            >
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
                    @error('category_ids') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                </div>

                <label class="form-label">General Requirements</label>
                {{-- Check box for food handling --}}
                <div class="form-check">
                    <input
                        id="handles_food"
                        type="checkbox"
                        class="form-check-input"
                        wire:model.live="handles_food"
                        aria-describedby="handles_food_help"
                    >
                    <label class="form-check-label" for="handles_food">This event is to sell food</label>
                    <div id="handles_food_help" class="form-text">Select if you'll serve or sell food so we can
                        request the proper permits.</div>
                </div>

                {{-- Check box for institutional funds --}}
                <div class="form-check">
                    <input
                        id="has_funds"
                        type="checkbox"
                        class="form-check-input"
                        wire:model.live="use_institutional_funds"
                        aria-describedby="hasFunds"
                    >
                    <label class="form-check-label" for="has_funds">This event uses institutional funds</label>
                    <div id="hasFunds" class="form-text">Choose this if institutional or university funds will cover
                        the request.</div>
                </div>

                {{-- Check box for external guest --}}
                <div class="form-check">
                    <input
                        id="external_guest"
                        type="checkbox"
                        class="form-check-input"
                        wire:model.live="external_guest"
                        aria-describedby="externalGuest"
                    >
                    <label class="form-check-label" for="external_guest">This event has an external guest</label>
                    <div id="externalGuest" class="form-text">Let us know if you'll host speakers or guests from
                        outside the institution.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label required">Organization</label>
                    <input type="text" class="form-control" value="{{ $organization_name }}"
                           disabled placeholder="e.g., Engineering Student Council">
                    @error('organization_name') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label required">Advisor name</label>
                    <input type="text" value="{{ $organization_advisor_name}}" class="form-control" {{--wire:model="organization_advisor_name"--}}
                    disabled placeholder="e.g., Prof. Maria Lopez">
                    @error('organization_advisor_name') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label required">Advisor email</label>
                    <input type="email" value="{{ $organization_advisor_email}}" class="form-control" disabled
                    {{--                    disabled wire:model.defer="organization_advisor_email"--}}
                           placeholder="e.g., advisor@upr.edu"
                    >
                    @error('organization_advisor_email') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label required">Advisor phone</label>
                    <input type="text" class="form-control" wire:model.defer="organization_advisor_phone" placeholder="e.g., 787-555-1234">
                    @error('organization_advisor_phone') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    Next
                    <i class="bi bi-arrow-right"></i>
                </button>
                
            </div>
        </form>
    @endif
    {{-- STEP 2 --}}

    @if ($step === 2)

        <h2 id="eventRequestStep2Heading" class="visually-hidden">Venue selection</h2>
        <form wire:submit.prevent="next" data-prevent-enter-submit aria-labelledby="eventRequestStep2Heading">
            <div class="mb-3">
                <div class="form-text">
                    Showing venues available between
                    <strong>{{ $this->formattedStartTime }}</strong>
                    and
                    <strong>{{ $this->formattedEndTime }}</strong>.
                </div>
            </div>


            @if ($loadingVenues)
                <div class="alert alert-info">Checking availability…</div>
            @endif


            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5 col-lg-4">
                            <label for="venueSearch" class="form-label">Search by name or code</label>
                            <input id="venueSearch"
                                   type="text"
                                   class="form-control"
                                   placeholder="e.g., SALON DE CLASES or AE-102"
                                   wire:model.live.debounce.500ms="venueSearch">
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <label for="venueCapacityFilter" class="form-label">Minimum capacity</label>
                            <input id="venueCapacityFilter"
                                   type="number"
                                   min="0"
                                   class="form-control"
                                   placeholder="e.g., 50"
                                   wire:model.live.debounce.500ms="venueCapacityFilter">
                        </div>
                        <div class="col-md-4 col-lg-3">
                            <label for="venueDepartmentFilter" class="form-label">Department</label>
                            <select id="venueDepartmentFilter"
                                    class="form-select"
                                    wire:model.live="venueDepartmentFilter">
                                <option value="">All departments</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department['id'] }}">{{ $department['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-lg-3 d-flex gap-2">
                            <button
                                type="button"
                                class="btn btn-primary flex-fill"
                                wire:click="runVenueSearch"
                                wire:loading.attr="disabled"
                                wire:target="runVenueSearch,venueSearch,venueCapacityFilter,venueDepartmentFilter">
                                @if ($loadingVenues)
                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                @endif
                                Refresh
                            </button>
                            <button
                                type="button"
                                class="btn btn-secondary flex-fill"
                                wire:click="resetVenueFilters"
                                wire:loading.attr="disabled"
                                wire:target="resetVenueFilters">
                                Reset
                            </button>
                        </div>
                    </div>
                    <div class="form-text mt-3">
                        Filters update automatically as you type. Use Refresh if you need to manually rerun the availability query.
                    </div>
                </div>
            </div>

            <div class="table-responsive mb-2">
                <table class="table table-hover align-middle shadow-sm">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" style="width:56px">Select</th>
                            <th scope="col">Code</th>
                            <th scope="col">Name</th>
                            <th scope="col" class="text-end">Capacity</th>
                            <th scope="col" class="text-center" style="width:120px">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->paginatedVenues as $v)
                            <tr wire:key="venue-row-{{ $v['id'] }}"
                                class="{{ (int) $venue_id === (int) ($v['id'] ?? 0) ? 'table-primary' : '' }}"
                                style="cursor:pointer" wire:click="selectVenue({{ $v['id'] }})">
                                <td>
                                    <input type="radio" class="form-check-input" wire:model.live="venue_id"
                                        value="{{ $v['id'] }}"
                                        aria-label="Select {{ $v['code'] ?? 'Venue #' . $v['id'] }}" />
                                </td>
                                <td><span class="fw-semibold">{{ $v['code'] ?? '—' }}</span></td>
                                <td>{{ $v['name'] ?? '—' }}</td>
                                <td class="text-end">{{ $v['capacity'] ?? '—' }}</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-primary"
                                        wire:click.stop="showVenueDescription({{ $v['id'] }})">
                                        View
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No venues match the current
                                    filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
                <div class="text-muted small">
                    @if ($this->venuePagination['total'] > 0)
                        Showing {{ $this->venuePagination['from'] }}–{{ $this->venuePagination['to'] }}
                        of {{ $this->venuePagination['total'] }} venues
                    @else
                        No venues to display.
                    @endif
                </div>
                @if ($this->venuePagination['last'] > 1)
                    <nav aria-label="Venues pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item {{ $this->venuePagination['current'] === 1 ? 'disabled' : '' }}">
                                <button
                                    type="button"
                                    class="page-link"
                                    wire:click="previousVenuePage"
                                    @disabled($this->venuePagination['current'] === 1)
                                >&laquo;</button>
                            </li>
                            @foreach (range(1, $this->venuePagination['last']) as $page)
                                <li class="page-item {{ $page === $this->venuePagination['current'] ? 'active' : '' }}">
                                    <button
                                        type="button"
                                        class="page-link"
                                        wire:click="goToVenuePage({{ $page }})"
                                        @disabled($page === $this->venuePagination['current'])
                                    >{{ $page }}</button>
                                </li>
                            @endforeach
                            <li class="page-item {{ $this->venuePagination['current'] === $this->venuePagination['last'] ? 'disabled' : '' }}">
                                <button
                                    type="button"
                                    class="page-link"
                                    wire:click="nextVenuePage"
                                    @disabled($this->venuePagination['current'] === $this->venuePagination['last'])
                                >&raquo;</button>
                            </li>
                        </ul>
                    </nav>
                @endif
            </div>
            @error('venue_id')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror



            <div class="d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-secondary" wire:click="back">
                    <i class="bi bi-arrow-left"></i>
                    Back
                </button>
                <button
                    type="submit"
                    class="btn btn-primary"
                    wire:loading.attr="disabled"
                    wire:target="next"
                    @disabled(!$venue_id)
                >Next
            <i class="bi bi-arrow-right"></i></button>
            </div>
        </form>
    @endif
    @if ($showVenueDescriptionModal && $selectedVenueDetails)
        <div
            class="modal fade show d-block"
            tabindex="-1"
            aria-modal="true"
            role="dialog"
            style="background: rgba(0,0,0,0.35);"
        >
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            {{ $selectedVenueDetails['name'] ?? 'Venue details' }}
                            <small class="text-muted ms-2">{{ $selectedVenueDetails['code'] ?? '' }}</small>
                        </h5>
                        <button type="button" class="btn-close" aria-label="Close"
                            wire:click="closeVenueDescription"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-3">
                            {{ $selectedVenueDetails['description'] ?? 'No description available.' }}
                        </p>

                        <h6>Availability</h6>
                        @php($slots = $selectedVenueDetails['availabilities'] ?? [])
                        @if (!empty($slots))
                            <ul class="list-group">
                                @foreach ($slots as $slot)
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span class="fw-semibold">{{ $slot['day'] }}</span>
                                        <span>
                                            {{ \Carbon\Carbon::parse($slot['opens_at'])->format('g:i A') }}
                                            –
                                            {{ \Carbon\Carbon::parse($slot['closes_at'])->format('g:i A') }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted mb-0">No availability configured.</p>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeVenueDescription">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    {{-- STEP 3 --}}
    @if ($step === 3)
        <h2 id="eventRequestStep3Heading" class="visually-hidden">Document uploads and submission</h2>
        <form wire:submit.prevent="submit" data-prevent-enter-submit aria-labelledby="eventRequestStep3Heading">

            <div class="mb-3">
                <div class="form-text">Upload the documents required due to the nature of the event.</div>
            </div>

            {{-- If 'This event is to sell food' is checked, show a link --}}
            @if ($handles_food)
                <div class="mb-3">
                    <ul>
                        <li>
                            <a href="https://www.uprm.edu/p/actividades-sociales/certificado_de_manejador_de_alimentos" class="event-form-link-btn" 
                            target="_blank"
                            rel="noopener">Food Handling Details</a>
                        </li>
                    </ul>
                </div>
            @endif

            {{-- If 'This event uses institutional funds' is checked, show a link --}}
            @if ($use_institutional_funds)
                <div class="mb-3">
                    <ul>
                        <li>
                            {{-- wire:click.prevent="showInstitutionalFundsDetails"  --}}
                            <a href="{{ asset('assets/documents/ejemplo_carta_del_rector.pdf') }}" class="event-form-link-btn" 
                            target="_blank"
                            rel="noopener">Institutional Funds Details</a>
                        </li>
                    </ul>
                </div>
            @endif

            {{-- If 'This event has an external guest' is checked, show a link --}}
            @if ($external_guest)
                <div class="mb-3">
                    <ul>
                        <li>
                            {{-- wire:click.prevent="showInstitutionalFundsDetails" --}}
                        <a href="{{ asset('assets/documents/ejemplo_carta_del_rector.pdf') }}" class="event-form-link-btn"  
                            target="_blank"
                            rel="noopener">External Guest Name
                        </a>
                        </li>
                    </ul>
                </div>
            @endif

            @if(!$handles_food && !$use_institutional_funds && !$external_guest)
            <div class="alert alert-secondary">No documents required based 
                on the nature of the event.</div>
            @endif

            <div class="mb-3">
                    <div class="form-text">Upload the documents required for this venue.</div>
            </div>

            @if (empty($requiredDocuments))
                <div class="alert alert-secondary">No documents required for this venue.</div>
            @else


            <div class="mb-3">

                <ul>
                    @foreach ($requiredDocuments as $doc)
                        <li wire:key="doc-{{ $doc['id'] }}">
                                <a href="{{$doc['hyperlink']}}" target="_blank" class="event-form-link-btn">
                                    {{ $doc['name'] . ": "}}
                                </a>
                                {{$doc['description']}}
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if ($this->shouldShowRequirementUploads)
                <div class="mb-3">
                    <label for="requirementFiles" class="form-label fw-semibold">
                        Upload Required Documents
                        <span class="text-muted">
                            (PDF, max 10MB each. Maximum of 10 files allowed.)
                        </span>
                    </label>

                    <div class="position-relative">
                        {{-- Visible dropzone UI --}}
                        <div class="w-100 d-flex flex-column align-items-center justify-content-center rounded-3 p-4 text-center bg-light border border-2"
                            style="border-style: dashed;">
                            {{-- If you have Bootstrap Icons, this will show a nice cloud icon --}}
                            <i class="bi bi-cloud-arrow-up fs-1 mb-2"></i>

                            <span class="fw-semibold">Click to choose files</span>
                            <span class="text-muted small">or drag and drop them here</span>
                        </div>

                        {{-- Actual file input overlay (handles click + drag/drop) --}}
                        <input id="newRequirementFiles" type="file" wire:model="newRequirementFiles" multiple
                            accept=".pdf" class="position-absolute top-0 start-0 w-100 h-100 opacity-0"
                            style="cursor: pointer;">
                    </div>

                    <div class="form-text mt-2">
                        @if ($this->requirementUploadsAreMandatory)
                            Upload all required documents here. Accepted formats: PDF. Maximum size 10MB per document.
                        @else
                            No documents are required for this event, but you can attach supporting PDFs if needed.
                        @endif
                    </div>

                    {{-- Top-level error: no documents at all (if you add that rule later) --}}
                    @error('requirementFiles')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror
                    @error('newRequirementFiles')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror

                    {{-- Deduped per-file errors for newRequirementFiles.* --}}
                    @foreach (collect($errors->get('newRequirementFiles.*', []))->flatten()->unique() as $message)
                        <div class="text-danger small mt-2">{{ $message }}</div>
                    @endforeach
                </div>

                {{-- Preview list of all selected files --}}
                @if ($requirementFiles)
                    <ul class="list-group mb-3">
                        @foreach ($requirementFiles as $index => $file)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold">
                                        {{ $file->getClientOriginalName() }}
                                    </div>
                                    <div class="text-muted small">
                                        ~ {{ number_format($file->getSize() / 1024 / 1024, 2) }} MB
                                    </div>
                                </div>

                                <button type="button" class="btn btn-danger btn-sm"
                                    wire:click="removeRequirementFile({{ $index }})">
                                    Remove
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif

                {{-- Small “uploading” hint when Livewire is handling a new batch --}}
                <div wire:loading wire:target="newRequirementFiles" class="mt-2 small text-muted">
                    Uploading… please wait.
                </div>
            @endif


            <div class="d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-secondary" wire:click="back">
                    <i class="bi bi-arrow-left"></i>
                    Back
                </button>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#confirmSubmitModal">
                    <i class="bi bi-send"></i>
                    Submit Event
                </button>
            </div>
        </form>
    @endif

    {{-- Submit confirmation modal --}}
    <div wire:ignore.self class="modal fade" id="confirmSubmitModal" tabindex="-1" aria-labelledby="confirmSubmitModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmSubmitModalLabel">Submit Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to submit this event for approval? <br><br>
                    After submission, you will not be able to make further changes. Missing required documents may incur in the dismissal of your request.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" wire:click="submit" wire:loading.attr="disabled"
                            data-bs-dismiss="modal">
                        Yes, Submit
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function initializeLeaveWarning() {
        if (window.eventFormLeaveGuardInitialized) {
            return;
        }
        window.eventFormLeaveGuardInitialized = true;

        const confirmationMessage = "You have unsaved changes. Are you sure you want to leave?";
        let shouldWarn = true;

        const handleBeforeUnload = function (event) {
            if (!shouldWarn) {
                return;
            }

            event.preventDefault();
            event.returnValue = confirmationMessage;
            return confirmationMessage;
        };

        window.addEventListener('beforeunload', handleBeforeUnload);

        const registerLivewireHandlers = () => {
            if (!window.Livewire || typeof window.Livewire.on !== 'function') {
                return;
            }

            window.Livewire.on('event-form-submitted', () => {
                shouldWarn = false;
                window.removeEventListener('beforeunload', handleBeforeUnload);
            });
        };

        if (window.Livewire) {
            registerLivewireHandlers();
        } else {
            document.addEventListener('livewire:init', registerLivewireHandlers);
        }
    })();

    (function preventEnterFormSubmissions() {
        if (window.eventFormEnterGuardInitialized) {
            return;
        }
        window.eventFormEnterGuardInitialized = true;

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') {
                return;
            }

            const target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }

            if (target.tagName === 'TEXTAREA') {
                return;
            }

            if (target.closest('[data-allow-enter-submit]')) {
                return;
            }

            const form = target.closest('form[data-prevent-enter-submit]');
            if (!form) {
                return;
            }

            const selector = 'input:not([type=\"button\"]):not([type=\"submit\"]):not([type=\"reset\"]), select, [contenteditable=\"true\"]';
            if (!target.matches(selector)) {
                return;
            }

            event.preventDefault();
        });
    })();
</script>


{{--// Flag to prevent 'beforeunload' on form submit--}}
{{--let isSubmitting = false;--}}

{{--// Add event listener for the form submit action--}}
{{--document.querySelectorAll('form').forEach(function(form) {--}}
{{--form.addEventListener('submit', function() {--}}
{{--isSubmitting = true;--}}
{{--});--}}
{{--});--}}

{{--// Trigger beforeunload when the user tries to leave the page (except on form submit)--}}
{{--window.addEventListener('beforeunload', function(event) {--}}
{{--if (!isSubmitting) { // Only show the warning if it's not a form submission--}}
{{--const confirmationMessage = "You have unsaved changes. Are you sure you want to leave?";--}}
{{--event.returnValue = confirmationMessage; // For most modern browsers--}}
{{--return confirmationMessage; // For older browsers--}}
{{--}--}}
{{--});--}}
