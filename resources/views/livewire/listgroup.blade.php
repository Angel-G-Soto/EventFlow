<div class="container py-4">

    <h1 class="h4 mb-3">Pending Requests</h1>

    <ul class="list-group">
        @foreach ($events as $event)
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    {{-- Simple initial avatar --}}
                    <div>
                        <div class="fw-bold">
                            <span>{{ $event->e_title ?? '—' }}</span>
                        </div>
                        <div class="small text-muted">Organization: {{ $event->organization_nexo_name }}</div>
                    </div>
                </div>


                <div class="ms-auto d-flex gap-2">
                    <button type="button" class="btn btn-primary d-flex"  data-bs-toggle="modal" data-bs-target="#detailModal">
                        View Details
                    </button>
                    <button type="button" class="btn btn-success d-flex" data-bs-toggle="modal" data-bs-target="#approveModal">
                        Approve
                    </button>
                    <button type="button" class="btn btn-danger d-flex" data-bs-toggle="modal" data-bs-target="#denyModal">
                        Deny
                    </button>
                </div>
            </li>
            <div class="modal fade" id="detailModal">
                <div class="modal-dialog modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5>Event Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div>Event Title: {{$event->e_title}}</div>
                            <div>Description: {{$event->e_description}}</div>
                            <div>Day Submitted: {{$event->created_at}}</div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal" style="background-color: #6c757d">
                                Ok
                            </button>
                        </div>
                    </div>
                </div>
            </div>


            <div class="modal fade" id="approveModal">
                <div class="modal-dialog modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5>Approve Event</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            Do you want to approve this request?
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-success" data-bs-dismiss="modal" >
                                Approve
                            </button>
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal" style="background-color: #6c757d">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>


            <div class="modal fade" id="denyModal">
                <div class="modal-dialog modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5>Deny Event</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            Do you want to deny this request?
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                                Deny
                            </button>
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal" style="background-color: #6c757d">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        @endforeach
    </ul>




    {{--        <div class="modal-dialog modal-dialog-centered">Modal</div>--}}
    <div class="mt-3">
        {{ $events->withQueryString()->onEachSide(1)->links() }}
    </div>
</div>
