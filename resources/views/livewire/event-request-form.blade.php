<div>
    @if (session()->has('ok'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('ok') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form class="needs-validation" novalidate wire:submit.prevent="save">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="phone" class="form-label">Student Phone</label>
                <input id="phone" type="tel"
                       class="form-control @error('name') is-invalid @enderror"
                       wire:model.live.debounce.500ms="name" autocomplete="name" />
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label for="student_number" class="form-label">Student Number</label>
                <input id="student_number" type="text"
                       class="form-control @error('student_number') is-invalid @enderror"
                       wire:model.live="student_number" />
                @error('organization') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>


            {{--            <div class="col-md-6">--}}
{{--                <label for="email" class="form-label">Email</label>--}}
{{--                <input id="email" type="email"--}}
{{--                       class="form-control @error('email') is-invalid @enderror"--}}
{{--                       wire:model.live.debounce.500ms="email" autocomplete="email" />--}}
{{--                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror--}}
{{--            </div>--}}

            <div class="col-md-6">
                <label for="event_title" class="form-label">Event Title</label>
                <input id="event_title" type="text"
                       class="form-control @error('event_title') is-invalid @enderror"
                       wire:model.live="event_title" />
                @error('organization') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label for="category" class="form-label">Category</label>
                <select id="category"
                        class="form-select @error('category') is-invalid @enderror"
                        wire:model.live="category">
                    <option value="">Choose…</option>
                    <option value="meeting">Meeting</option>
                    <option value="workshop">Workshop</option>
                    <option value="competition">Competition</option>
                    <option value="performance">Performance</option>
                    <option value="other">Other</option>
                </select>
                @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label for="starts_at" class="form-label">Starts at</label>
                <input id="starts_at" type="datetime-local"
                       class="form-control @error('starts_at') is-invalid @enderror"
                       wire:model.live="starts_at" />
                @error('starts_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label for="ends_at" class="form-label">Ends at</label>
                <input id="ends_at" type="datetime-local"
                       class="form-control @error('ends_at') is-invalid @enderror"
                       wire:model.live="ends_at" />
                @error('ends_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
                <label for="location" class="form-label">Location</label>
                <input id="location" type="text"
                       class="form-control @error('location') is-invalid @enderror"
                       wire:model.live="location" placeholder="e.g., Centro de Estudiantes, Room 101" />
                @error('location') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- Toggle: student org --}}
            <div class="col-12">
                <div class="form-check form-switch">
                    <input id="is_student_org" class="form-check-input" type="checkbox"
                           wire:model.live="is_student_org">
                    <label class="form-check-label" for="is_student_org">
                        This is a Student Organization request
                    </label>
                </div>
            </div>

            @if($is_student_org)
                <div class="col-md-6">
                    <label for="advisor_email" class="form-label">Advisor Email</label>
                    <input id="advisor_email" type="email"
                           class="form-control @error('advisor_email') is-invalid @enderror"
                           wire:model.live.debounce.500ms="advisor_email" />
                    @error('advisor_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            @endif

            {{-- Toggle: approval doc --}}
            <div class="col-12">
                <div class="form-check">
                    <input id="needs_approval_doc" class="form-check-input" type="checkbox"
                           wire:model.live="needs_approval_doc">
                    <label class="form-check-label" for="needs_approval_doc">
                        Attach approval PDF (if required)
                    </label>
                </div>
            </div>

            @if($needs_approval_doc)
                <div class="col-md-8">
                    <label for="approval_pdf" class="form-label">Approval PDF</label>
                    <input id="approval_pdf" type="file" accept="application/pdf"
                           class="form-control @error('approval_pdf') is-invalid @enderror"
                           wire:model="approval_pdf" />
                    <div class="form-text">PDF, up to 4 MB.</div>

                    <div wire:loading wire:target="approval_pdf" class="form-text">
                        Uploading…
                    </div>

                    @error('approval_pdf') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            @endif
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Submit request</span> n
                <span wire:loading wire:target="save" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                <span wire:loading wire:target="save"> Saving…</span>
            </button>

            <button type="button" class="btn btn-outline-secondary" wire:click="$refresh">
                Reset validation
            </button>
        </div>
    </form>
</div>
