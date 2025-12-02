<?php

namespace App\Livewire\Admin;

use App\Services\CategoryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Component;

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
    use AuthorizesRequests;

    public string $search = '';
    public int $page = 1;
    public int $pageSize = 10;
    public string $sortField = 'name';
    public string $sortDirection = 'asc';

    public ?int $editingId = null;
    public string $formName = '';
    public string $editJustification = '';
    public string $createJustification = '';

    public ?int $deleteId = null;
    public string $deleteName = '';
    public string $deleteJustification = '';
    public bool $showModal = false;

    /**
     * Ensure the current user can manage categories before interacting.
     */
    public function mount(): void
    {
        $this->authorizeManage();
    }

    /**
     * Render the paginated categories table for admins.
     */
    public function render()
    {
        $this->authorizeManage();

        $paginator = $this->categoriesPaginator();

        return view('livewire.admin.categories-index', [
            'rows' => $paginator,
        ]);
    }

    public function goToPage(int $target): void
    {
        $this->authorizeManage();
        $this->page = max(1, $target);
    }

    public function applySearch(): void
    {
        $this->authorizeManage();
        $this->page = 1;
    }

    public function clearFilters(): void
    {
        $this->authorizeManage();
        $this->search = '';
        $this->page = 1;
    }

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

    public function updatedPageSize(): void
    {
        $this->authorizeManage();
        $this->page = 1;
    }

    /**
     * Open create modal with a clean form.
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

    public function cancelForm(): void
    {
        $this->authorizeManage();
        $this->resetForm();
        $this->showModal = false;
        $this->dispatch('bs:close', id: 'categoryModal');
    }

    /**
     * Launch the delete confirmation/justification flow for a category.
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

    public function cancelDelete(): void
    {
        $this->deleteId = null;
        $this->deleteName = '';
        $this->deleteJustification = '';
        $this->dispatch('bs:close', id: 'categoryJustify');
    }

    /**
     * Delete a category after justification, delegating to the service layer.
     */
    public function deleteCategory(): void
    {
        $this->authorizeManage();

        if (!$this->deleteId) {
            return;
        }

        $this->validate([
            'deleteJustification' => ['required', 'string', 'min:10'],
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

    protected function authorizeManage(): void
    {
        $this->authorize('manage-categories');
    }

    /**
     * Validate justification and persist a new category.
     */
    public function confirmCreateSave(): void
    {
        $this->authorizeManage();

        if ($this->editingId) {
            return;
        }

        $this->validate([
            'createJustification' => ['required', 'string', 'min:10'],
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
     */
    public function confirmEditSave(): void
    {
        $this->authorizeManage();

        if (!$this->editingId) {
            return;
        }

        $this->validate([
            'editJustification' => ['required', 'string', 'min:10'],
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
