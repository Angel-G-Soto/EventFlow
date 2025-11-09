
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
                    <label class="form-label">Event Category (multiple)</label>
                    <select class="form-select" wire:model.defer="category_ids" multiple size="5">
                        @foreach ($allCategories as $cat)
                            <option value="{{ $cat['id'] }}">{{ $cat['name'] }}</option>
                        @endforeach
                    </select>
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
                </div>

                <div class="col-md-6">
                    <label class="form-label required">Advisor name</label>
                    <input type="text" class="form-control" wire:model.defer="organization_advisor_name" placeholder="Enter advisor's name">
                    @error('organization_advisor_name') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label required">Advisor email</label>
                    <input type="email" class="form-control" wire:model.defer="organization_advisor_email" placeholder="Enter advisor's email">
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
                <div class="form-text">Showing venues available between <strong>{{ $start_time ?: '—' }}</strong> and <strong>{{ $end_time ?: '—' }}</strong>.</div>
            </div>


            @if ($loadingVenues)
                <div class="alert alert-info">Checking availability…</div>
            @endif


            <div class="mb-3">
                <label class="form-label">Venue</label>
                <select class="form-select" wire:model="venue_id">
                    <option value="">— Select venue —</option>
                    @foreach ($availableVenues as $v)
                        <option value="{{ $v['id'] }}">{{ $v['code'] }}</option>
                    @endforeach
                </select>
                @error('venue_id') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>


            <div class="d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-outline-secondary" wire:click="back">Back</button>
                <button type="submit" class="btn btn-primary">Next</button>
            </div>
        </form>
    @endif

    {{-- STEP 3 --}}
    @if ($step === 3)
        <form wire:submit.prevent="submit">
            <div class="mb-3">
                <div class="form-text">Upload the documents required for the selected venue.</div>
            </div>


            @if (empty($requiredDocuments))
                <div class="alert alert-secondary">No documents required for this venue.</div>
            @else
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
                <div class="mb-3">
                    <label for="requirementFiles" class="form-label">Upload Requirement Documents</label>
                    <input type="file"
                           class="form-control"
                           id="requirementFiles"
                           wire:model="requirementFiles"
                           multiple
{{--                           accept=".pdf,.doc,.docx,.png,.jpg,.jpeg"/>--}}
                            accept=".pdf"/>
                    <div class="form-text">
{{--                        Upload all required documents here. Accepted formats: PDF, DOCX, JPG, PNG.--}}
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
    // Flag to prevent 'beforeunload' on form submit
    let isSubmitting = false;

    // Add event listener for the form submit action
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function() {
            isSubmitting = true;
        });
    });

    // Trigger beforeunload when the user tries to leave the page (except on form submit)
    window.addEventListener('beforeunload', function (event) {
        if (!isSubmitting) { // Only show the warning if it's not a form submission
            const confirmationMessage = "You have unsaved changes. Are you sure you want to leave?";
            event.returnValue = confirmationMessage;  // For most modern browsers
            return confirmationMessage;              // For older browsers
        }
    });
</script>




