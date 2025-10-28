<div class="d-flex flex-wrap align-items-center gap-2">
    {{-- Dropdown: Organizations --}}
    <div class="dropdown">
        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside">
            Organizations
            @if(count($selectedOrganizations))
                <span class="badge bg-primary ms-1">{{ count($selectedOrganizations) }}</span>
            @endif
        </button>
        <div class="dropdown-menu p-3" style="width: 320px;">
            <label class="form-label small text-muted">Select one or more</label>
            <select class="form-select" multiple size="8" wire:model="selectedOrganizations">
                @foreach($organizations as $id => $label)
                    <option value="{{ $id }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Dropdown: Categories (Event Types) --}}
    <div class="dropdown">
        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside">
            Event Types
            @if(count($selectedCategories))
                <span class="badge bg-primary ms-1">{{ count($selectedCategories) }}</span>
            @endif
        </button>
        <div class="dropdown-menu p-3" style="width: 320px;">
            <label class="form-label small text-muted">Select one or more</label>
            <select class="form-select" multiple size="8" wire:model="selectedCategories">
                @foreach($categories as $id => $label)
                    <option value="{{ $id }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Dropdown: Venues --}}
    <div class="dropdown">
        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside">
            Venues
            @if(count($selectedVenues))
                <span class="badge bg-primary ms-1">{{ count($selectedVenues) }}</span>
            @endif
        </button>
        <div class="dropdown-menu p-3" style="width: 320px;">
            <label class="form-label small text-muted">Select one or more</label>
            <select class="form-select" multiple size="8" wire:model="selectedVenues">
                @foreach($venues as $id => $label)
                    <option value="{{ $id }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @unless($autoApply)
        <button class="btn btn-primary" wire:click="apply">Apply</button>
    @endunless
    <button class="btn btn-link text-decoration-none" wire:click="clear">Clear</button>


    {{-- Chips of current selections (nice UX & quick glance) --}}
    <div class="d-flex flex-wrap gap-1 ms-auto">
        @foreach($selectedOrganizations as $id)
            <span class="badge bg-light text-dark border">Org: {{ $organizations[$id] ?? $id }}</span>
        @endforeach
        @foreach($selectedCategories as $id)
            <span class="badge bg-light text-dark border">Type: {{ $categories[$id] ?? $id }}</span>
        @endforeach
        @foreach($selectedVenues as $id)
            <span class="badge bg-light text-dark border">Venue: {{ $venues[$id] ?? $id }}</span>
        @endforeach
    </div>
</div>
