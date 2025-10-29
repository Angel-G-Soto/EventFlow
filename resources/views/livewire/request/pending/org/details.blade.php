<div>
    {{-- If you look to others for fulfillment, you will never truly be fulfilled. --}}
    <div class="card">
        <div class="card-body" style="text-align: justify">
            Event name: {{$event->e_title}}
            <br>
            Student Organization: {{$event->organization_nexo_name}}
            <br>
            Description: {{$event->e_description}}
            <br>
            Day Submitted: {{$event->created_at}}


        </div>
        <div class="ms-auto d-flex gap-2 mb-3 container-fluid">
        <button type="button" class="btn btn-success d-flex" data-bs-toggle="modal" data-bs-target="#approveModal">
            Approve
        </button>

        <button type="button" class="btn btn-danger d-flex" data-bs-toggle="modal" data-bs-target="#denyModal">
            Withdraw
        </button>
        </div>

        <div class="modal fade"
             id="denyModal{{ $event->id ?? '' }}"
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
                            Withdraw Request
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
