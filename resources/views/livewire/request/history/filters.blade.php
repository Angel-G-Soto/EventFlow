
{{--    View: Filters (Livewire partial)--}}
{{--    Project: EventFlow (Laravel 12 + Livewire 3 + Bootstrap 5)--}}
{{--    Date: 2025-11-01--}}

{{--    Description:--}}
{{--    - Reusable multi-select filters to refine lists (e.g., Events index).--}}
{{--    - Typical facets: Organization, Category/Type, Venue; supports search and reset.--}}
{{--    - Emits events or uses bound properties to inform parent components.--}}

{{--    Expected variables/props (may vary by implementation):--}}
{{--    - array|Collection $organizations  List of organizations for pickers--}}
{{--    - array|Collection $categories     List of categories/types for pickers--}}
{{--    - array|Collection $venues         List of venues for pickers--}}

{{--    Accessibility notes:--}}
{{--    - Associate <label for="..."> with inputs; ensure each control has an accessible name.--}}
{{--    - Group checkbox lists inside <fieldset> with a <legend> to convey context.--}}
{{--    - Provide focus styles and keyboard operability for dropdowns/multiselects.--}}


{{--    View: Filters (Livewire partial)--}}
{{--    Project: EventFlow (Laravel 12 + Livewire 3 + Bootstrap 5)--}}
{{--    Date: 2025-11-01--}}

{{--    Description:--}}
{{--    - Reusable multi-select filters to refine lists (e.g., Events index).--}}
{{--    - Typical facets: Organization, Category/Type, Venue; supports search and reset.--}}
{{--    - Emits events or uses bound properties to inform parent components.--}}

{{--    Expected variables/props (may vary by implementation):--}}
{{--    - array|Collection $organizations  List of organizations for pickers--}}
{{--    - array|Collection $categories     List of categories/types for pickers--}}
{{--    - array|Collection $venues         List of venues for pickers--}}

{{--    Accessibility notes:--}}
{{--    - Associate <label for="..."> with inputs; ensure each control has an accessible name.--}}
{{--    - Group checkbox lists inside <fieldset> with a <legend> to convey context.--}}
{{--    - Provide focus styles and keyboard operability for dropdowns/multiselects.--}}

<x-slot:pageActions>
    <ul class="navbar-nav mx-auto">
        <li class="nav-item">
            <a class="fw-bold nav-link ? 'active' : '' " href="{{ route('public.calendar') }}">Home</a>
        </li>
        <li class="nav-item">
            <a class="fw-bold nav-link ? 'active' : '' " href="{{ route('approver.pending.index') }}">Pending Request</a>
        </li>
        <li class="nav-item">
            <a class="fw-bold nav-link ? 'active' : '' " href="{{ route('approver.history.index') }}">Request History</a>
        </li>
    </ul>
</x-slot:pageActions>

<div class="card-body py-2" style="font-size: 1.05rem;">
    <div class="row align-items-end g-2">

        {{-- Search by Title --}}
        <div class="col-md-4 col-sm-12">
            <label for="searchTitle" class="form-label mb-0 small text-muted" style="font-size: 1.05rem;">Search</label>
            <div class="input-group input-group-sm">
                <input id="searchTitle" type="text" class="form-control"
                       placeholder="Search by title or organization..."
                       wire:model.defer="searchTitle"
                       wire:keydown.enter="apply"
                       style="font-size: 1.05rem;">
                <button class="btn btn-secondary" wire:click="apply" style="font-size: 1.05rem;">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </div>

        {{-- Role / Status --}}
        <div class="col-md-3 col-sm-6">
            <label for="status" class="form-label mb-0 small text-muted" style="font-size: 1.05rem;">Role / Status</label>
            <select id="status" class="form-select form-select-sm" wire:model.defer="selectedRole" style="font-size: 1.05rem;">
                <option value="">All Roles</option>
                @foreach($roles as $role)
                    @php
                        $roleName = strtolower($role['name']);
                        if (str_contains($roleName, 'advisor')) {
                            $display = 'Advisor'; $value = 'advisor';
                        } elseif (str_contains($roleName, 'venue-manager')) {
                            $display = 'Venue Manager'; $value = 'venue-manager';
                        } elseif (str_contains($roleName, 'event-approver') || str_contains($roleName, 'dsca')) {
                            $display = 'Event Approver'; $value = 'event-approver';
                        } else {
                            $display = $roleName; $value = $roleName;
                        }
                    @endphp
                    <option value="{{ $value }}">{{ $display }}</option>
                @endforeach
            </select>
        </div>

        {{-- Sort by Date --}}
        <div class="col-md-3 col-sm-6">
            <label for="sort" class="form-label mb-0 small text-muted" style="font-size: 1.05rem;">Sort by Date</label>
            <select id="sort" class="form-select form-select-sm" wire:model.defer="sortDirection" style="font-size: 1.05rem;">
                <option value="desc">Newest First</option>
                <option value="asc">Oldest First</option>
            </select>
        </div>

        {{-- Clear Button --}}
        <div class="col-6 col-md-2 col-lg-1">
            <label class="form-label mb-0 small text-muted d-block">&nbsp;</label>
            <button type="button"
                    class="btn btn-secondary btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-1 text-nowrap"
                    wire:click="resetFilters" style="font-size: 1.05rem;">
                <i class="bi bi-x-circle" aria-hidden="true"></i>
                <span>Clear</span>
            </button>
        </div>

        {{-- Apply Button --}}
        <div class="col-6 col-md-2 col-lg-1">
            <label class="form-label mb-0 small text-muted d-block">&nbsp;</label>
            <button type="button"
                    class="btn btn-primary btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-1 text-nowrap"
                    wire:click="apply" style="font-size: 1.05rem;">
                <i class="bi bi-arrow-right-circle" aria-hidden="true"></i>
                <span>Apply</span>
            </button>
        </div>

    </div>
</div>

@script
<script>
    // Close open panels after Apply (scoped to this component)
    $wire.on('filters-applied', () => {
        ['collapseCat','collapseVen'].forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            const c = bootstrap.Collapse.getOrCreateInstance(el);
            c.hide();
        });
    });
</script>
@endscript
