{{--resources/views/livewire/venue/director/venues.blade.php--}}
<div class="container py-4">
    <h1 class="h4 mb-3" id="venueManagerHeading">
        Venue Managers
    </h1>

    {{-- Success status (polite) --}}
    <div aria-live="polite" aria-atomic="true">
        @session('message')
        <div class="alert alert-success" role="status">{{ session('message') }}</div>
        @endsession
    </div>

    {{-- Error summary (assertive) --}}
    @if ($errors->any())
        <div class="alert alert-danger" role="alert" aria-live="assertive">
            <p class="m-0 fw-bold">Please fix the following:</p>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

{{--    <div class="d-flex align-items-center gap-2 mb-3">--}}
{{--        <label for="per-page" class="form-label m-0">Rows per page</label>--}}
{{--        <select--}}
{{--            id="per-page"--}}
{{--            class="form-select form-select-sm"--}}
{{--            style="max-width: 8rem"--}}
{{--            wire:model.live="perPage"--}}
{{--            aria-describedby="venueManagerHeading"--}}
{{--        >--}}
{{--            <option value="5">5</option>--}}
{{--            <option value="10">10</option>--}}
{{--            <option value="15">15</option>--}}
{{--            <option value="25">25</option>--}}
{{--        </select>--}}
{{--    </div>--}}
    <div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" aria-describedby="venueManagerHeading">
            <thead class="table-light">
            <tr>
                <th scope="col">Venue</th>
                <th scope="col">Current Manager</th>
                <th scope="col">Change Manager</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($venues as $venue)
                @php
                    $field = "selectedManagers.{$venue->id}";
                    $hasError = $errors->has($field);
                    $errorId = "error-{$venue->id}";
                @endphp

                {{-- Wrap per-row controls in a form for better semantics --}}
                <tr>
                    <th scope="row">{{ $venue->name }}</th>
                    <td>{{ $venue->manager?->first_name . ' ' . $venue->manager?->last_name ?? '— No Manager Assigned —' }}</td>
                    <td>
                        <form wire:submit.prevent="updateManager({{ $venue->id }})" aria-labelledby="venueManagerHeading">
                            <label for="manager-select-{{ $venue->id }}" class="visually-hidden">
                                Select new manager for {{ $venue->name }}
                            </label>

                            <select
                                id="manager-select-{{ $venue->id }}"
                                class="form-select form-select-sm @if($hasError) is-invalid @endif"
                                wire:model="selectedManagers.{{ $venue->id }}"
                                aria-label="Change manager for {{ $venue->name }}"
                                @if($hasError) aria-invalid="true" aria-describedby="{{ $errorId }}" @endif
                            >
                                <option value="">Select Manager</option>
                                @foreach ($managers as $manager)
                                    <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                                @endforeach
                            </select>

                            @error($field)
                            <div id="{{ $errorId }}" class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror

                            <button
                                type="submit"
                                class="btn btn-outline-secondary btn-sm mt-2"
                                aria-label="Save new manager for {{ $venue->name }}"
                            >
                                Save
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">
                        <div class="text-center py-4" role="status">No venues found for your department.</div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    </div>

    <nav class="d-flex justify-content-center mt-3" aria-label="Venues pagination">
        {{ $venues->links('pagination::bootstrap-5') }}
    </nav>
</div>

