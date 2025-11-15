
{{--    View: Venue Details--}}
{{--    Project: EventFlow (Laravel 12 + Livewire 3 + Bootstrap 5)--}}
{{--    Date: 2025-11-01--}}

{{--    Description:--}}
{{--    - Presents an individual venue with fields: Name, Department, Current Manager,--}}
{{--      Capacity, Opening Time, and Closing Time.--}}
{{--    - Receives a typed $venue model from the Livewire component.--}}

{{--    Variables:--}}
{{--    @var \App\Models\Venue $venue--}}

{{--    Accessibility notes:--}}
{{--    - Use <dl> or a table with <th scope> for label/value pairs.--}}
{{--    - Localize time values; ensure buttons/links have discernible text.--}}

<div>
    {{-- Only render when there are conflicts (uses paginator's total to avoid extra query) --}}
    @if ($conflicts->total() > 0)
        <div class="alert alert-warning d-flex align-items-start gap-2 mb-3" role="alert" aria-live="polite">
            <i class="bi bi-exclamation-triangle-fill fs-4" aria-hidden="true"></i>
            <div>
                <h2 class="h5 mb-1">Potential scheduling conflicts</h2>
                <p class="mb-0">
                    We found {{ $conflicts->total() }}
                    {{ \Illuminate\Support\Str::plural('overlapping event', $conflicts->total()) }}
                    for this time window{{ isset($event->venue_id) ? ' and venue' : '' }}.
                </p>
            </div>
        </div>

        <div class="card mb-4" aria-labelledby="conflict-list-title">
            <div class="card-header">
                <h3 id="conflict-list-title" class="h6 mb-0">Conflicting events</h3>
            </div>

            <ul class="list-group list-group-flush">
                @foreach ($conflicts as $c)
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="me-3">
                            <div class="fw-semibold">{{ $c->title }}</div>
                            <div class="small text-body-secondary">
                                {{ \Illuminate\Support\Carbon::parse($c->start_time)->format('M j, Y g:ia') }}
                                &ndash;
                                {{ \Illuminate\Support\Carbon::parse($c->end_time)->format('M j, Y g:ia') }}
                                @if (!empty($c->organization_name))
                                    &middot; {{ $c->organization_name }}
                                @endif
                            </div>
                        </div>
                        <span class="badge text-bg-danger align-self-center">Overlap</span>
                    </li>
                @endforeach
            </ul>

            {{-- Bootstrap 5 pagination (works if you’ve called Paginator::useBootstrapFive()) --}}
            <nav class="p-3" aria-label="Conflicting events pages">
                {{ $conflicts->onEachSide(1)->links() }}
            </nav>
        </div>
    @endif



    <h1>Event Details</h1>

    <div class="card container">
        <div class="card-body" style="text-align: justify">
            <h3>Event Name: {{$event->title}}</h3>
            <h5>Student Organization: {{$event->organization_name}}</h5>
            Description: {{$event->description}}
            <br>
            <br>
            Day Submitted: {{ optional($event->created_at)->format('D, M j, Y g:i A') }}
            <br>
            Event Start Time : {{$event->start_time}}
            <br>
            Event End Time : {{$event->end_time}}
            <br>
            Event handles food:
            @if ($event->handles_food === 0)
                No
            @else
                Yes
            @endif
            <br>
            Uses institutional funds:
            @if ($event->use_institutional_funds === 0)
                No
            @else
                Yes
            @endif
            <br>
            Invites external guests:
            @if ($event->external_guests === 0)
                No
            @else
                Yes
            @endif


        </div>
        <br>

        {{--Documents--}}
        <div class="container-fluid">
            <h5>Documents</h5>
            <livewire:documents.list-with-preview :docs="$docs" />
            <br>
        </div>

        {{--Buttons--}}
        <div class="d-flex gap-2 mb-3 container-fluid">
            <button type="button" wire:click="approve" class="btn btn-outline-success d-flex" wire:target="approve">
                Approve
            </button>

            <button type="button" class="btn btn-outline-danger d-flex" data-bs-toggle="modal" data-bs-target="#denyModal">
                Reject
            </button>

            <button type="button" wire:click="back" class="btn btn-outline-secondary ms-auto"
                    wire:target="back">
                Back
            </button>

        </div>


        <div class="modal fade"
             id="denyModal"
             tabindex="-1" aria-hidden="true"
             wire:ignore.self
             wire:key="deny-modal-{{ $event->id ?? 'single' }}"
             x-data="{ justification: @entangle('justification') }">

            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Write a message</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
        <textarea class="form-control"
                  x-model="justification"
                  rows="4" required minlength="10"
                  placeholder="Type at least 10 characters..."></textarea>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-outline-danger"
                                wire:click="save"
                                :disabled="justification.trim().length < 10"
                                wire:loading.attr="disabled" wire:target="save">
                            Reject Request
                        </button>
                    </div>
                </div>
            </div>
        </div>


       </div>
</div>
