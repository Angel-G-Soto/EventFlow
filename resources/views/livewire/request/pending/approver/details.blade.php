<div>
    <h1>Event Details</h1>
    {{-- If you look to others for fulfillment, you will never truly be fulfilled. --}}
    <div class="card container">
        <div class="card-body" style="text-align: justify">
            <h3>Event Name: {{$event->title}}</h3>
            <h5>Student Organization: {{$event->organization_nexo_name}}</h5>
            Description: {{$event->description}}
            <br>
            Day Submitted: {{$event->created_at}}
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
        <div class="ms-auto d-flex gap-2 mb-3 container-fluid">
        <button type="button" class="btn btn-success d-flex" data-bs-toggle="modal" data-bs-target="#approveModal">
            Approve
        </button>

        <button type="button" class="btn btn-danger d-flex" data-bs-toggle="modal" data-bs-target="#denyModal">
            Reject
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
                        <button class="btn btn-primary"
                                wire:click="save"
                                :disabled="justification.trim().length < 10"
                                wire:loading.attr="disabled" wire:target="save">
                            Reject Approval
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
