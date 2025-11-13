
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
    <ul class="nav nav-pills mb-4">
        <li class="nav-item"><span class="nav-link {{ $step === 1 ? 'active' : '' }}">1. Event</span></li>
        <li class="nav-item"><span class="nav-link {{ $step === 2 ? 'active' : '' }}">2. Venue</span></li>
        <li class="nav-item"><span class="nav-link {{ $step === 3 ? 'active' : '' }}">3. Documents</span></li>
    </ul>
    {{-- STEP 1 --}}
    @if ($step === 1)

        <p class="text-muted small">
            <span class="text-danger" aria-hidden="true">*</span>
            <span class="visually-hidden">required</span>
            Fields marked with an asterisk are required.
        </p>
        <form wire:submit.prevent="next">

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label required">Student Phone</label>
                    <input type="text" class="form-control" wire:model.defer="creator_phone_number" placeholder="787-777-7777">
                    @error('creator_phone_number') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label required">Student ID / Number</label>
                    <input type="text" class="form-control" wire:model.defer="creator_institutional_number" placeholder="802201234">
                    @error('creator_institutional_number') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label class="form-label required">Event Title</label>
                    <input type="text" class="form-control" wire:model.defer="title" placeholder="Enter event title">
                    @error('title') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label class="form-label required">Event description</label>
                    <textarea class="form-control" rows="4" wire:model.defer="description" placeholder="Enter a brief description of the event"></textarea>
                    @error('description') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-12">
                    <label class="form-label required">Number of Guests</label>
                    <input type="text" class="form-control" wire:model.defer="guest_size" placeholder="20">
                    @error('guest_size') <div class="text-danger small">{{ $message }}</div> @enderror

                </div>

                <div class="col-md-6">
                    <label class="form-label required">Start time</label>
                    <input type="datetime-local" class="form-control" wire:model.live="start_time" placeholder="Select start time">
                    @error('start_time') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label required">End time</label>
                    <input type="datetime-local" class="form-control" wire:model.live="end_time" placeholder="Select end time">
                    @error('end_time') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-baseline">
                        <label class="form-label">Event Categories</label>
                        <button type="button" class="btn btn-link btn-sm p-0" wire:click="clearCategories" @disabled(empty($category_ids))>
                            Clear selection
                        </button>
                    </div>

                    <div class="card border shadow-sm">
                        <div class="card-body">
                            <div class="row g-2 align-items-center mb-3">
                                <div class="col-md-8">
                                    <input
                                        type="search"
                                        class="form-control"
                                        placeholder="Search categories (e.g., Workshop, Fundraiser)"
                                        wire:model.debounce.300ms="categorySearch"
                                    >
                                </div>
                                <div class="col-md-4 text-md-end">
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
                        aria-describedby="handles_food"
                    >
                    <label class="form-check-label" for="handles_food">This event is to sell food</label>
                    <div id="handles_food" class="form-text"></div>
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
                    <div id="hasFunds" class="form-text"></div>
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
                </div>

                <div class="col-md-6">
                    <label class="form-label required">Organization</label>
                    <input type="text" class="form-control" value="{{ $organization_name }}" disabled placeholder="Organization name">
                    @error('organization_name') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label required">Advisor name</label>
                    <input type="text" value="{{ $organization_advisor_name}}" class="form-control" {{--wire:model.defer="organization_advisor_name"--}} disabled placeholder="Enter advisor's name">
                    @error('organization_advisor_name') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label required">Advisor email</label>
                    <input type="email" value="{{ $organization_advisor_email}}" class="form-control" {{--wire:model.defer="organization_advisor_email"--}}  disabled placeholder="Enter advisor's email">
                    @error('organization_advisor_email') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="submit" class="btn btn-primary">Next</button>
            </div>
        </form>
    @endif
    {{-- STEP 2 --}}

    @if ($step === 2)

        <form wire:submit.prevent="next">
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
                <div class="card-body" wire:keydown.enter.prevent="runVenueSearch">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5 col-lg-4">
                            <label for="venueSearch" class="form-label">Search by name or code</label>
                            <input id="venueSearch"
                                   type="text"
                                   class="form-control"
                                   placeholder="e.g., SALON DE CLASES or AE-102"
                                   wire:model.defer="venueSearch">
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <label for="venueCapacityFilter" class="form-label">Minimum capacity</label>
                            <input id="venueCapacityFilter"
                                   type="number"
                                   min="0"
                                   class="form-control"
                                   placeholder="50"
                                   wire:model.defer="venueCapacityFilter">
                        </div>
                        <div class="col-md-4 col-lg-3">
                            <label for="venueDepartmentFilter" class="form-label">Department</label>
                            <select id="venueDepartmentFilter"
                                    class="form-select"
                                    wire:model.defer="venueDepartmentFilter">
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
                                wire:target="runVenueSearch">
                                @if ($loadingVenues)
                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                @endif
                                Search
                            </button>
                            <button
                                type="button"
                                class="btn btn-outline-secondary flex-fill"
                                wire:click="resetVenueFilters"
                                wire:loading.attr="disabled"
                                wire:target="resetVenueFilters">
                                Reset
                            </button>
                        </div>
                    </div>
                    <div class="form-text mt-3">Adjust one or more filters and press Search to rerun the availability query.</div>
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
                            class="{{ (int)$venue_id === (int)($v['id'] ?? 0) ? 'table-primary' : '' }}"
                            style="cursor:pointer"
                            wire:click="selectVenue({{ $v['id'] }})">
                            <td>
                                <input type="radio"
                                       class="form-check-input"
                                       wire:model.live="venue_id"
                                       value="{{ $v['id'] }}"
                                       aria-label="Select {{ $v['code'] ?? ('Venue #'.$v['id']) }}" />
                            </td>
                            <td><span class="fw-semibold">{{ $v['code'] ?? '—' }}</span></td>
                            <td>{{ $v['name'] ?? '—' }}</td>
                            <td class="text-end">{{ $v['capacity'] ?? '—' }}</td>
                            <td class="text-center">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-primary"
                                    wire:click.stop="showVenueDescription({{ $v['id'] }})"
                                >
                                    View
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No venues match the current filter.</td>
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
            @error('venue_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror



            <div class="d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-outline-secondary" wire:click="back">Back</button>
                <button
                    type="submit"
                    class="btn btn-primary"
                    wire:loading.attr="disabled"
                    wire:target="next"
                    @disabled(!$venue_id)
                >Next</button>
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
                        <button type="button" class="btn-close" aria-label="Close" wire:click="closeVenueDescription"></button>
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
        <form wire:submit.prevent="submit">

            <div class="mb-3">
                <div class="form-text">Upload the documents required due to the nature of the event.</div>
            </div>

            {{-- If 'This event is to sell food' is checked, show a link --}}
            @if ($handles_food)
                <div class="mb-3">
                    <ul>
                        <li>
                            <a href="#" class="text-primary" wire:click.prevent="showFoodHandlingDetails">Food Handling Details</a>
                        </li>
                    </ul>
                </div>
            @endif

            {{-- If 'This event uses institutional funds' is checked, show a link --}}
            @if ($use_institutional_funds)
                <div class="mb-3">
                    <ul>
                        <li>
                            <a href="#" class="text-primary" wire:click.prevent="showInstitutionalFundsDetails">Institutional Funds Details</a>
                        </li>
                    </ul>
                </div>
            @endif

            {{-- If 'This event has an external guest' is checked, show a link --}}
            @if ($external_guest)
                <div class="mb-3">
                    <ul>
                        <li>
                            <a href="#" class="text-primary" wire:click.prevent="showExternalGuestDetails">External Guest Name</a>
                        </li>
                    </ul>
                </div>
            @endif


            @if (empty($requiredDocuments))
                <div class="alert alert-secondary">No documents required for this venue.</div>
            @else

                <div class="mb-3">
                    <div class="form-text">Upload the documents required for this venue.</div>
                </div>

                <ul class="row">
                    @foreach ($requiredDocuments as $doc)
                        <li wire:key="doc-{{ $doc['id'] }}">
                                <a href="{{$doc['hyperlink']}}" target="_blank">
                                    {{ $doc['name'] . ": "}}
                                </a>
                                {{$doc['description']}}
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($this->shouldShowRequirementUploads)
                <div class="mb-3">
                    <label for="requirementFiles" class="form-label">Upload Requirement Documents</label>
                    <input type="file"
                           class="form-control"
                           id="requirementFiles"
                           wire:model="requirementFiles"
                           multiple
                           accept=".pdf"/>
                    <div class="form-text">
                        Upload all required documents here. Accepted formats: PDF.
                    </div>

                @error('requirementFiles.*')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
                </div>
            @endif


            <div class="d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-outline-secondary" wire:click="back">Back</button>
                <button type="submit" class="btn btn-success">Submit Event</button>
            </div>
        </form>
    @endif
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
</script>
