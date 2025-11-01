
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


<div class="row container-fluid">
    <div class="accordion my-2 w-25" id="eventFilters">
        {{-- Categories --}}
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingCat">
                <button class="accordion-button collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseCat"
                        aria-expanded="false" aria-controls="collapseCat">
                    Categories <span class="badge text-bg-secondary ms-2">{{ count($selectedCategories) }}</span>
                </button>
            </h2>
            <div id="collapseCat" class="accordion-collapse collapse">
                <div class="accordion-body" style="max-height:20rem;overflow:auto;">
                    <div class="d-flex justify-content-end mb-2 gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="selectAll('categories')">All</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="clear('categories')">Clear</button>
                    </div>
                    @foreach ($categories as $cat)
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="cat-{{ $cat['id'] }}"
                                   value="{{ $cat['id'] }}"
                                   wire:model.defer="selectedCategories"
                                   wire:click.stop>
                            <label class="form-check-label" for="cat-{{ $cat['id'] }}">{{ $cat['name'] }}</label>
                        </div>
                    @endforeach
                    <button type="button" class="btn btn-primary mt-3" wire:click="apply">Apply</button>
                </div>
            </div>
        </div>
    </div>

    <div class="accordion my-2 w-25" id="eventFilters">

        {{-- Venues (copy of Categories, bound to selectedVenues) --}}
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingVen">
                <button class="accordion-button collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseVen"
                        aria-expanded="false" aria-controls="collapseVen">
                    Venues <span class="badge text-bg-secondary ms-2">{{ count($selectedVenues) }}</span>
                </button>
            </h2>
            <div id="collapseVen" class="accordion-collapse collapse">
                <div class="accordion-body" style="max-height:20rem;overflow:auto;">
                    <div class="d-flex justify-content-end mb-2 gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="selectAll('venues')">All</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="clear('venues')">Clear</button>
                    </div>
                    @foreach ($venues as $v)
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="ven-{{ $v['id'] }}"
                                   value="{{ $v['id'] }}"
                                   wire:model.defer="selectedVenues"
                                   wire:click.stop>
                            <label class="form-check-label" for="ven-{{ $v['id'] }}">{{ $v['name'] }}</label>
                        </div>
                    @endforeach
                    <button type="button" class="btn btn-primary mt-3" wire:click="apply">Apply</button>
                </div>
            </div>
        </div>
    </div>

    <div class="accordion my-2 w-25" id="orgFilters">
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingOrg">
                <button class="accordion-button collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseOrg"
                        aria-expanded="false" aria-controls="collapseOrg">
                    Organization <span class="badge text-bg-secondary ms-2">{{ count($selectedOrgs) }}</span>
                </button>
            </h2>
            <div id="collapseOrg" class="accordion-collapse collapse">
                <div class="accordion-body" style="max-height:20rem;overflow:auto;">
                    <div class="d-flex justify-content-end mb-2 gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="selectAll('orgs')">All</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="clear('orgs')">Clear</button>
                    </div>
                    @foreach ($orgs as $v)
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="ven-{{ $v['organization_nexo_id'] }}"
                                   value="{{ $v['organization_nexo_id'] }}"
                                   wire:model.defer="selectedOrgs"
                                   wire:click.stop>
                            <label class="form-check-label" for="ven-{{ $v['organization_nexo_id'] }}">{{ $v['organization_nexo_name'] }}</label>
                        </div>
                    @endforeach
                    <button type="button" class="btn btn-primary mt-3" wire:click="apply">Apply</button>
                </div>
            </div>
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
