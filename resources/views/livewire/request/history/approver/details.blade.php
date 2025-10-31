<div>
    <h1>Event Details</h1>

    <div class="card container">
        <div class="card-body" style="text-align: justify">
            <h3>Event Name: {{$event->title}}</h3>
            <h5>Student Organization: {{$event->organization_nexo_name}}</h5>
            Description: {{$event->description}}
            <br>
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
                Cancel Request Approval
            </button>

            <button type="button" wire:click="approve" class="btn btn-outline-secondary ms-auto"
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
                            Cancel Request Approval
                        </button>
                    </div>
                </div>
            </div>
        </div>


    </div>
</div>
