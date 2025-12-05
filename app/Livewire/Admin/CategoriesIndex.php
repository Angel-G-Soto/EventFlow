<?php

namespace App\Livewire\Admin;

use App\Services\CategoryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Livewire\Traits\HasJustification;

/**
 * Admin category management view.
 *
 * Provides search/sort/pagination plus create, update (with justification),
 * and delete (with justification) flows, delegating all persistence to
 * CategoryService so validation, audit logging, and side effects stay
 * centralized outside the Livewire layer.
 */
#[Layout('layouts.app')]
class CategoriesIndex extends Component
{
    use AuthorizesRequests, HasJustification;

    /**
     * Search term for filtering categories.
     *
     * @var string
     */
    public string $search = '';

    /** @var int Current paginator page. */
    public int $page = 1;

    /** @var int Number of rows to display per page. */
    public int $pageSize = 10;

    /** @var string Active sort field for the listing. */
    public string $sortField = 'name';

    /** @var string Sort direction for the listing. */
    public string $sortDirection = 'asc';

    /** @var int|null Category id currently being edited. */
    public ?int $editingId = null;

    /** @var string Category name input for create/edit. */
    public string $formName = '';

    /** @var string Justification for edits. */
    public string $editJustification = '';

    /** @var string Justification for creates. */
    public string $createJustification = '';

    /** @var int|null Category id pending deletion. */
    public ?int $deleteId = null;

    /** @var string Display name of the category being deleted. */
    public string $deleteName = '';

    /** @var string Justification provided before deletion. */
    public string $deleteJustification = '';

    /** @var bool Whether the create/edit modal is visible. */
    public bool $showModal = false;

    /**
     * Ensure the current user can manage categories before interacting.
     *
     * @return void
     */
    public function mount(): void
    {
        $this->authorizeManage();
    }

    /**
     * Render the paginated categories table for admins.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        $this->authorizeManage();

        $paginator = $this->categoriesPaginator();

        return view('livewire.admin.categories-index', [
            'rows' => $paginator,
        ]);
    }

    /**
     * Move to a specific page number within bounds.
     *
     * @param int $target Desired page number (1-indexed).
     *
     * @return void
     */
    public function goToPage(int $target): void
    {
        $this->authorizeManage();
        $this->page = max(1, $target);
    }

    /**
     * Apply search terms and reset pagination.
     *
     * @return void
     */
    public function applySearch(): void
    {
        $this->authorizeManage();
        $this->page = 1;
    }

    /**
     * Clear filters and reset pagination to defaults.
     *
     * @return void
     */
    public function clearFilters(): void
    {
        $this->authorizeManage();
        $this->search = '';
        $this->page = 1;
    }

    /**
     * Toggle or set the active sort column/direction.
     *
     * @param string $field Column name to sort by.
     *
     * @return void
     */
    public function sortBy(string $field): void
    {
        $this->authorizeManage();

        if ($field === $this->sortField) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->page = 1;
    }

    /**
     * Reset pagination when page size changes.
     *
     * @return void
     */
    public function updatedPageSize(): void
    {
        $this->authorizeManage();
        $this->page = 1;
    }

    /**
     * Open create modal with a clean form.
     *
     * @return void
     */
    public function startCreate(): void
    {
        $this->authorizeManage();
        $this->resetForm();
        $this->showModal = true;
        $this->dispatch('bs:open', id: 'categoryModal');
    }

    /**
     * Load an existing category into the edit modal.
     *
     * @param int $categoryId Target category identifier.
     *
     * @return void
     */
    public function startEdit(int $categoryId): void
    {
        $this->authorizeManage();

        try {
            $category = app(CategoryService::class)->getCategoryByID($categoryId);
            $this->editingId = $category->id;
            $this->formName = (string)$category->name;
            $this->showModal = true;
            $this->dispatch('bs:open', id: 'categoryModal');
        } catch (\Throwable $e) {
            $this->dispatch('toast', message: 'Category not found.');
        }
    }

    /**
     * Validate and persist a category; edits require justification.
     *
     * @return void
     */
    public function saveCategory(): void
    {
        $this->authorizeManage();

        $this->validate([
            'formName' => ['required', 'string', 'min:2', 'max:150'],
        ]);

        $this->resetErrorBag(['editJustification']);
        $this->resetValidation(['editJustification']);
        $this->resetErrorBag(['createJustification']);
        $this->resetValidation(['createJustification']);

        if ($this->editingId) {
            $this->editJustification = '';
            $this->dispatch('bs:open', id: 'categoryEditJustify');
            return;
        }

        $this->createJustification = '';
        $this->dispatch('bs:open', id: 'categoryCreateJustify');
    }

    /**
     * Close the form modal and reset state.
     *
     * @return void
     */
    public function cancelForm(): void
    {
        $this->authorizeManage();
        $this->resetForm();
        $this->showModal = false;
        $this->dispatch('bs:close', id: 'categoryModal');
    }

    /**
     * Launch the delete confirmation/justification flow for a category.
     *
     * @param int $categoryId Target category identifier.
     *
     * @return void
     */
    public function confirmDelete(int $categoryId): void
    {
        $this->authorizeManage();
        $this->deleteId = $categoryId;
        $this->deleteName = $this->resolveCategoryName($categoryId);
        $this->deleteJustification = '';
        $this->resetErrorBag();
        $this->resetValidation();
        $this->dispatch('bs:open', id: 'categoryJustify');
    }

    /**
     * Cancel a pending delete action and close the modal.
     *
     * @return void
     */
    public function cancelDelete(): void
    {
        $this->deleteId = null;
        $this->deleteName = '';
        $this->deleteJustification = '';
        $this->dispatch('bs:close', id: 'categoryJustify');
    }

    /**
     * Delete a category after justification, delegating to the service layer.
     *
     * @return void
     */
    public function deleteCategory(): void
    {
        $this->authorizeManage();

        if (!$this->deleteId) {
            return;
        }

        $this->validate([
            'deleteJustification' => $this->justificationRules(true),
        ], [], [
            'deleteJustification' => 'justification',
        ]);

        $service = app(CategoryService::class);

        try {
            $service->deleteCategory($this->deleteId, $this->deleteJustification);
            $this->dispatch('toast', message: 'Category deleted');

            if ($this->editingId === $this->deleteId) {
                $this->resetForm();
                $this->showModal = false;
                $this->dispatch('bs:close', id: 'categoryModal');
            }
        } catch (\Throwable $e) {
            $this->dispatch('toast', message: 'Unable to delete category.');
            return;
        } finally {
            $this->deleteId = null;
            $this->deleteJustification = '';
            $this->dispatch('bs:close', id: 'categoryJustify');
        }
    }

    /**
     * Reset form-related state to defaults.
     *
     * @return void
     */
    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->formName = '';
        $this->editJustification = '';
        $this->createJustification = '';
    }

    /**
     * Build a paginator for categories using the service layer.
     *
     * Keeps pagination state in sync when filters/page size change.
     *
     * @return LengthAwarePaginator
     */
    protected function categoriesPaginator(): LengthAwarePaginator
    {
        $service = app(CategoryService::class);
        $sort = [
            'field' => $this->sortField,
            'direction' => $this->sortDirection,
        ];

        $paginator = $service->paginateCategories(
            ['search' => $this->search],
            $this->pageSize,
            $this->page,
            $sort
        );

        $last = max(1, (int)$paginator->lastPage());
        if ($this->page > $last) {
            $this->page = $last;
            $paginator = $service->paginateCategories(
                ['search' => $this->search],
                $this->pageSize,
                $this->page,
                $sort
            );
        }

        return $paginator;
    }

    /**
     * Assert the current user can manage categories.
     *
     * @return void
     */
    protected function authorizeManage(): void
    {
        $this->authorize('manage-categories');
    }

    /**
     * Validate justification and persist a new category.
     *
     * @return void
     */
    public function confirmCreateSave(): void
    {
        $this->authorizeManage();

        if ($this->editingId) {
            return;
        }

        $this->validate([
            'createJustification' => $this->justificationRules(true),
        ], [], [
            'createJustification' => 'justification',
        ]);

        if ($this->persistCategory($this->createJustification)) {
            $this->dispatch('bs:close', id: 'categoryCreateJustify');
            $this->createJustification = '';
        }
    }

    /**
     * Validate justification and persist an existing category.
     *
     * @return void
     */
    public function confirmEditSave(): void
    {
        $this->authorizeManage();

        if (!$this->editingId) {
            return;
        }

        $this->validate([
            'editJustification' => $this->justificationRules(true),
        ], [], [
            'editJustification' => 'justification',
        ]);

        if ($this->persistCategory($this->editJustification)) {
            $this->dispatch('bs:close', id: 'categoryEditJustify');
            $this->editJustification = '';
        }
    }

    /**
     * Create or update a category through the service layer.
     *
     * Justification is required for both creates and edits (passed to the service for audit logging).
     *
     * @param string|null $justification Business justification for the change.
     *
     * @return bool True when the service call succeeds.
     */
    protected function persistCategory(?string $justification = null): bool
    {
        $service = app(CategoryService::class);

        try {
            if ($this->editingId) {
                $service->updateCategory($this->editingId, $this->formName, (string) $justification);
                $message = 'Category updated';
            } else {
                $service->createCategory($this->formName, (string) $justification);
                $message = 'Category created';
            }

            $this->dispatch('toast', message: $message);
            $this->resetForm();
            $this->showModal = false;
            $this->dispatch('bs:close', id: 'categoryModal');
            $this->page = 1;
            return true;
        } catch (\InvalidArgumentException $ex) {
            $this->addError('formName', $ex->getMessage());
        } catch (\Throwable $e) {
            $this->addError('formName', 'Unable to save the category. Please try again.');
        }

        return false;
    }

    /**
     * Resolve a category's display name from its identifier.
     *
     * @param int $categoryId Target category identifier.
     *
     * @return string Category name or empty string when missing.
     */
    protected function resolveCategoryName(int $categoryId): string
    {
        try {
            $category = app(CategoryService::class)->getCategoryByID($categoryId);
            return (string) ($category->name ?? '');
        } catch (\Throwable) {
            return '';
        }
    }
}
