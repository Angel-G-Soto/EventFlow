<div>
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h1 class="h4 mb-0">Categories</h1>
        <x-tooltip-button type="button" class="btn btn-primary" wire:click="startCreate" text="Create a new category">
            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>
            New Category
        </x-tooltip-button>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form class="row g-3 align-items-end" wire:submit.prevent="applySearch" aria-label="Filter categories">
                <div class="col-md-6">
                    <label for="categorySearch" class="form-label">Search</label>
                    <div class="input-group">
                        <input
                            type="search"
                            class="form-control"
                            id="categorySearch"
                            placeholder="Search by name"
                            wire:model.defer="search"
                        >
                        <x-tooltip-button class="btn btn-secondary" type="submit" aria-label="Search" text="Run search">
                            <i class="bi bi-search"></i>
                        </x-tooltip-button>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <label for="categoryRows" class="form-label">Rows</label>
                    <select id="categoryRows" class="form-select" wire:model.live="pageSize">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <x-tooltip-button type="button" class="btn btn-secondary w-100" wire:click="clearFilters" aria-label="Clear filters" text="Reset filters">
                        Clear
                    </x-tooltip-button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" aria-describedby="categoriesTableCaption">
                <caption id="categoriesTableCaption" class="visually-hidden">List of categories with creation dates and actions.</caption>
                <thead class="table-light">
                    <tr>
                        @php
                            $nameSort = $sortField === 'name' ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none';
                            $createdSort = $sortField === 'created_at' ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none';
                        @endphp
                        <th scope="col" aria-sort="{{ $nameSort }}">
                            <x-tooltip-button
                                class="btn btn-link p-0 text-decoration-none text-black fw-semibold"
                                type="button"
                                wire:click="sortBy('name')"
                                aria-label="Sort by category name"
                                text="Sort by category name"
                            >
                                Name
                                @if($sortField === 'name')
                                    <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-short" aria-hidden="true"></i>
                                @else
                                    <i class="bi bi-arrow-down-up text-muted" aria-hidden="true"></i>
                                @endif
                            </x-tooltip-button>
                        </th>
                        <th scope="col" style="width:160px;" aria-sort="{{ $createdSort }}">
                            <x-tooltip-button
                                class="btn btn-link p-0 text-decoration-none text-black fw-semibold"
                                type="button"
                                wire:click="sortBy('created_at')"
                                aria-label="Sort by creation date"
                                text="Sort by creation date"
                            >
                                Created
                                @if($sortField === 'created_at')
                                    <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-short" aria-hidden="true"></i>
                                @else
                                    <i class="bi bi-arrow-down-up text-muted" aria-hidden="true"></i>
                                @endif
                            </x-tooltip-button>
                        </th>
                        <th scope="col" class="text-end" style="width:200px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $category)
                        <tr wire:key="cat-{{ $category->id }}">
                            <th class="fw-medium" scope="row">{{ $category->name }}</th>
                            <td>{{ optional($category->created_at)->format('M d, Y') }}</td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm" role="group" aria-label="Row actions for {{ $category->name }}">
                                    <x-tooltip-button type="button"
                                            class="btn btn-secondary"
                                            wire:click="startEdit({{ $category->id }})"
                                            aria-label="Edit category {{ $category->name }}"
                                            text="Edit category {{ $category->name }}">
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                        <span class="visually-hidden">Edit</span>
                                    </x-tooltip-button>
                                    <x-tooltip-button type="button"
                                            class="btn btn-danger"
                                            wire:click="confirmDelete({{ $category->id }})"
                                            aria-label="Delete category {{ $category->name }}"
                                            text="Delete category {{ $category->name }}">
                                        <i class="bi bi-trash3" aria-hidden="true"></i>
                                        <span class="visually-hidden">Delete</span>
                                    </x-tooltip-button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-secondary py-4">
                                No categories found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer d-flex align-items-center justify-content-between">
            <small class="text-secondary">
                {{ method_exists($rows, 'total') ? $rows->total() : count($rows) }} results
            </small>
            {{ $rows->onEachSide(1)->links('partials.pagination') }}
        </div>
    </div>

    <div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true" aria-labelledby="categoryModalLabel" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content" wire:submit.prevent="saveCategory">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalLabel">
                        <i class="bi bi-tags-fill me-2"></i>
                        {{ $editingId ? 'Edit Category' : 'Add Category' }}
                    </h5>
                    <x-tooltip-button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" wire:click="cancelForm" text="Close dialog">
                        <span class="visually-hidden">Close</span>
                    </x-tooltip-button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="categoryName" class="form-label required">Name</label>
                        <input
                            type="text"
                            id="categoryName"
                            class="form-control @error('formName') is-invalid @enderror"
                            placeholder="e.g., Workshop"
                            wire:model.defer="formName"
                            required
                        >
                        @error('formName')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <x-tooltip-button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="cancelForm" text="Return without saving">
                        Back
                    </x-tooltip-button>
                    <x-tooltip-button type="submit" class="btn btn-primary" text="{{ $editingId ? 'Apply changes to this category' : 'Create the category' }}">
                        {{ $editingId ? 'Update Category' : 'Create Category' }}
                    </x-tooltip-button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="categoryJustify" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false" style="z-index: 1100;" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" wire:submit.prevent="deleteCategory">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-clipboard-check me-2"></i>Justification
                    </h5>
                    <x-tooltip-button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" text="Close dialog">
                        <span class="visually-hidden">Close</span>
                    </x-tooltip-button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Reason</label>
                        <textarea class="form-control" rows="4" required wire:model.live="deleteJustification"
                                  placeholder="Type at least 10 characters..."></textarea>
                        @error('deleteJustification')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <x-tooltip-button class="btn btn-secondary" type="button" data-bs-dismiss="modal" text="Return without deleting">Back</x-tooltip-button>
                    <x-tooltip-button class="btn btn-primary" type="submit" text="Confirm deletion of this category">
                        <i class="bi bi-check2 me-1"></i>Confirm
                    </x-tooltip-button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="categoryEditJustify" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false" style="z-index: 1105;" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" wire:submit.prevent="confirmEditSave">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-clipboard-check me-2"></i>Justification
                    </h5>
                    <x-tooltip-button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" text="Close dialog">
                        <span class="visually-hidden">Close</span>
                    </x-tooltip-button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Reason</label>
                        <textarea class="form-control" rows="4" required wire:model.live="editJustification"
                                  placeholder="Type at least 10 characters..."></textarea>
                        @error('editJustification')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <x-tooltip-button class="btn btn-secondary" type="button" data-bs-dismiss="modal" text="Return without saving">Back</x-tooltip-button>
                    <x-tooltip-button class="btn btn-primary" type="submit" text="Apply category changes">
                        <i class="bi bi-check2 me-1"></i>Confirm
                    </x-tooltip-button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const initTooltips = () => {
            if (!window.bootstrap || !window.bootstrap.Tooltip) {
                return;
            }
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
                window.bootstrap.Tooltip.getOrCreateInstance(el);
            });
        };

        document.addEventListener('livewire:init', () => {
            initTooltips();
            document.addEventListener('livewire:update', initTooltips);
        });
    })();
</script>
@endpush
