<div>
    {{-- Be like water. --}}
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

        <form wire:submit.prevent="next">

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Student phone</label>
                    <input type="text" class="form-control" wire:model.defer="student_phone">
                    @error('student_phone') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Student ID / Number</label>
                    <input type="text" class="form-control" wire:model.defer="student_number">
                    @error('student_number') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Event Title</label>
                    <input type="text" class="form-control" wire:model.defer="title">
                    @error('title') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Event description</label>
                    <textarea class="form-control" rows="4" wire:model.defer="description"></textarea>
                    @error('description') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Start time</label>
                    <input type="datetime-local" class="form-control" wire:model.live="start_at">
                    @error('start_at') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">End time</label>
                    <input type="datetime-local" class="form-control" wire:model.live="end_at">
                    @error('end_at') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Event Category (multiple)</label>
                    <select class="form-select" wire:model.defer="category_ids" multiple size="5">
                        @foreach ($allCategories as $cat)
                            <option value="{{ $cat['id'] }}">{{ $cat['name'] }}</option>
                        @endforeach
                    </select>
                    @error('category_ids') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Organization</label>
                    <input type="text" class="form-control" value="{{ $organization_name }}" disabled>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Advisor name</label>
                    <input type="text" class="form-control" wire:model.defer="advisor_name">
                    @error('advisor_name') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Advisor phone</label>
                    <input type="text" class="form-control" wire:model.defer="advisor_phone">
                    @error('advisor_phone') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Advisor email</label>
                    <input type="email" class="form-control" wire:model.defer="advisor_email">
                    @error('advisor_email') <div class="text-danger small">{{ $message }}</div> @enderror
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
                <div class="form-text">Showing venues available between <strong>{{ $start_at ?: '—' }}</strong> and <strong>{{ $end_at ?: '—' }}</strong>.</div>
            </div>


            @if ($loadingVenues)
                <div class="alert alert-info">Checking availability…</div>
            @endif


            <div class="mb-3">
                <label class="form-label">Venue</label>
                <select class="form-select" wire:model="venue_id">
                    <option value="">— Select venue —</option>
                    @foreach ($availableVenues as $v)
                        <option value="{{ $v['id'] }}">{{ $v['name'] }}</option>
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
                <div class="row g-3">
                    @foreach ($requiredDocuments as $doc)
                        <div class="col-md-6" wire:key="doc-{{ $doc['key'] }}">
                            <label class="form-label">
                                {{ $doc['label'] }}
                                @if ($doc['required'])
                                    <span class="badge bg-danger ms-1">Required</span>
                                @else
                                    <span class="badge bg-secondary ms-1">Optional</span>
                                @endif
                            </label>
                            <input type="file" class="form-control"
                                   wire:model="uploads.{{ $doc['key'] }}"
                                   accept="{{ empty($doc['mimes']) ? '' : implode(',', array_map(fn($m) => '.' . ($m === 'jpg' ? 'jpg' : $m), $doc['mimes'])) }}">
                            @error('uploads.' . $doc['key']) <div class="text-danger small">{{ $message }}</div> @enderror
                            {{-- Livewire upload progress --}}
                            <div wire:loading wire:target="uploads.{{ $doc['key'] }}" class="form-text">Uploading…</div>
                        </div>
                    @endforeach
                </div>
            @endif


            <div class="d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-outline-secondary" wire:click="back">Back</button>
                <button type="submit" class="btn btn-success">Submit Event</button>
            </div>
        </form>
    @endif

</div>
