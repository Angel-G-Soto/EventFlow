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
                {{-- Search: full width on mobile, wide on desktop --}}
                <div class="col-12 col-md-8 col-lg-9">
                    <label for="categorySearch" class="form-label">Search</label>
                    <div class="input-group w-100">
                        <input type="search" class="form-control" id="categorySearch" placeholder="Search by name"
                            wire:model.defer="search">
                        <button class="btn btn-secondary" type="submit" title="Search">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <span class="ms-1">Search</span>
                        </button>
                    </div>
                </div>

                {{-- Clear button: stacked on mobile, aligned on right column on desktop --}}
                <div class="col-12 col-md-4 col-lg-3">
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

    {{-- (Row quantity selector was removed as requested) --}}

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" aria-describedby="categoriesTableCaption">
                <caption id="categoriesTableCaption" class="visually-hidden">
                    List of categories with actions.
                </caption>
                <thead class="table-light">
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col" class="text-end" style="width:220px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $category)
                        <tr wire:key="cat-{{ $category->id }}">
                            <th class="fw-medium" scope="row">{{ $category->name }}</th>
                            <td class="text-end">
                                <div class="d-flex flex-column flex-md-row justify-content-end align-items-end align-items-md-stretch gap-2"
                                     role="group"
                                     aria-label="Row actions for {{ $category->name }}">
                                    <button type="button"
                                            class="btn btn-secondary btn-sm d-flex align-items-center justify-content-center gap-1"
                                            wire:click="startEdit({{ $category->id }})"
                                            title="Edit category {{ $category->name }}">
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                        <span>Edit</span>
                                    </button>
                                    <button type="button"
                                            class="btn btn-danger btn-sm d-flex align-items-center justify-content-center gap-1"
                                            wire:click="confirmDelete({{ $category->id }})"
                                            title="Delete category {{ $category->name }}">
                                        <i class="bi bi-trash3" aria-hidden="true"></i>
                                        <span>Delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center text-secondary py-4">
                                No categories found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Footer: stacked on mobile, row on desktop --}}
        <div class="card-footer d-flex flex-column flex-md-row align-items-center justify-content-between gap-2">
            <small class="text-secondary">
                {{ method_exists($rows, 'total') ? $rows->total() : count($rows) }} results
            </small>
            {{ $rows->onEachSide(1)->links('partials.pagination') }}
        </div>
    </div>

    {{-- Create / Edit Category Modal --}}
    <div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true"
         aria-labelledby="categoryModalLabel" wire:ignore.self>
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
                               class="form-control @error('formName') is-invalid @enderror"
                               placeholder="e.g., Workshop"
                               wire:model.defer="formName" required>
                        @error('formName')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal" wire:click="cancelForm"
                            title="Return without saving">
                        Back
                    </button>
                    <button type="submit" class="btn btn-primary"
                            title="{{ $editingId ? 'Apply changes to this category' : 'Create the category' }}">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Justification modals --}}
    <x-justification id="categoryJustify" submit="deleteCategory" model="deleteJustification" />
    <x-justification id="categoryCreateJustify" submit="confirmCreateSave" model="createJustification" />
    <x-justification id="categoryEditJustify" submit="confirmEditSave" model="editJustification" />
</div>
