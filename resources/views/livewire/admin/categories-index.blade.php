<div>
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h1 class="h4 mb-0">Categories</h1>
        <button type="button" class="btn btn-sm btn-primary" wire:click="startCreate" title="Create a new category">
            <i class="bi bi-plus me-1" aria-hidden="true"></i> Add Category
        </button>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form class="row g-3 align-items-end" wire:submit.prevent="applySearch" aria-label="Filter categories">
                <div class="col-md-6">
                    <label for="categorySearch" class="form-label">Search</label>
                    <div class="input-group">
                        <input type="search" class="form-control" id="categorySearch" placeholder="Search by name"
                            wire:model.defer="search">
                        <button class="btn btn-secondary" type="submit" aria-label="Search" title="Run search">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <button type="button"
                        class="btn btn-secondary w-100 d-inline-flex align-items-center justify-content-center gap-1 text-nowrap"
                        wire:click="clearFilters" aria-label="Clear filters" title="Reset filters">
                        <i class="bi bi-x-circle" aria-hidden="true"></i>
                        <span>Clear</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Page size --}}
    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-end mb-2">
        <div class="d-flex align-items-center gap-2">
            <label class="text-secondary small mb-0 text-black" for="categoryRows">Rows</label>
            <select id="categoryRows" class="form-select form-select-sm" style="width:auto" wire:model.live="pageSize">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" aria-describedby="categoriesTableCaption">
                <caption id="categoriesTableCaption" class="visually-hidden">List of categories with creation dates and
                    actions.</caption>
                <thead class="table-light">
                    <tr>
                        @php
                        $nameSort = $sortField === 'name' ? ($sortDirection === 'asc' ? 'ascending' : 'descending') :
                        'none';
                        $createdSort = $sortField === 'created_at' ? ($sortDirection === 'asc' ? 'ascending' :
                        'descending') : 'none';
                        @endphp
                        <th scope="col" aria-sort="{{ $nameSort }}">
                            <button class="btn btn-link p-0 text-decoration-none text-black fw-semibold"
                                type="button" wire:click="sortBy('name')" aria-label="Sort by category name" title="Sort by category name">
                                Name
                                @if($sortField === 'name')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-short"
                                    aria-hidden="true"></i>
                                @else
                                <i class="bi bi-arrow-down-up text-muted" aria-hidden="true"></i>
                                @endif
                            </button>
                        </th>
                        <th scope="col" style="width:160px;" aria-sort="{{ $createdSort }}">
                            <button class="btn btn-link p-0 text-decoration-none text-black fw-semibold"
                                type="button" wire:click="sortBy('created_at')" aria-label="Sort by creation date" title="Sort by creation date">
                                Created
                                @if($sortField === 'created_at')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-short"
                                    aria-hidden="true"></i>
                                @else
                                <i class="bi bi-arrow-down-up text-muted" aria-hidden="true"></i>
                                @endif
                            </button>
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
                            <div class="btn-group btn-group-sm" role="group"
                                aria-label="Row actions for {{ $category->name }}">
                                <button type="button" class="btn btn-secondary"
                                    wire:click="startEdit({{ $category->id }})"
                                    aria-label="Edit category {{ $category->name }}"
                                    title="Edit category {{ $category->name }}">
                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                    <span class="visually-hidden">Edit</span>
                                </button>
                                <button type="button" class="btn btn-danger"
                                    wire:click="confirmDelete({{ $category->id }})"
                                    aria-label="Delete category {{ $category->name }}"
                                    title="Delete category {{ $category->name }}">
                                    <i class="bi bi-trash3" aria-hidden="true"></i>
                                    <span class="visually-hidden">Delete</span>
                                </button>
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

    <div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true" aria-labelledby="categoryModalLabel"
        wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content" wire:submit.prevent="saveCategory">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalLabel">
                        <i class="bi bi-tags-fill me-2"></i>
                        {{ $editingId ? 'Edit Category' : 'Add Category' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="cancelForm" title="Close dialog">
                        <span class="visually-hidden">Close</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="categoryName" class="form-label required">Name</label>
                        <input type="text" id="categoryName"
                            class="form-control @error('formName') is-invalid @enderror" placeholder="e.g., Workshop"
                            wire:model.defer="formName" required>
                        @error('formName')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        wire:click="cancelForm" title="Return without saving">
                        Back
                    </button>
                    <button type="submit" class="btn btn-primary"
                        title="{{ $editingId ? 'Apply changes to this category' : 'Create the category' }}">
                        {{ $editingId ? 'Update Category' : 'Create Category' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="categoryJustify" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false" style="z-index: 1100;" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" wire:submit.prevent="deleteCategory">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-clipboard-check me-2"></i>Justification
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        title="Close dialog">
                        <span class="visually-hidden">Close</span>
                    </button>
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
                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal"
                        wire:click="cancelDelete" title="Return without deleting">Back</button>
                    <button class="btn btn-primary" type="submit" title="Confirm deletion of this category">
                        <i class="bi bi-check2 me-1"></i>Confirm
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="categoryConfirmDelete" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Confirm deletion
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="cancelDelete" title="Close dialog">
                        <span class="visually-hidden">Close</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Are you sure you want to delete <strong>{{ $deleteName ?: 'this category' }}</strong>?</p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal"
                        wire:click="cancelDelete" title="Return without deleting">
                        Back
                    </button>
                    <button class="btn btn-danger" type="button" wire:click="proceedDelete"
                        title="Continue to justification">
                        <i class="bi bi-arrow-right-square me-1"></i>Continue
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="categoryEditJustify" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false" style="z-index: 1105;" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" wire:submit.prevent="confirmEditSave">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-clipboard-check me-2"></i>Justification
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        title="Close dialog">
                        <span class="visually-hidden">Close</span>
                    </button>
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
                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal"
                        title="Return without saving">Back</button>
                    <button class="btn btn-primary" type="submit" title="Apply category changes">
                        <i class="bi bi-check2 me-1"></i>Confirm
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
