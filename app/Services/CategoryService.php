<?php

namespace App\Services;
use App\Models\Category;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Throwable;

class CategoryService {

    ///////////////////////////////////////////// CRUD Operations ////////////////////////////////////////////////

    public function __construct(){}

    /**
     * Retrieve the category with the specified ID.
     *
     * This method finds a category by its unique identifier. The ID must be a
     * positive integer. If the category does not exist, a `ModelNotFoundException` is thrown.
     *
     * @param int $category_id
     * @return Category
     * @throws Exception
     */
    public function getCategoryByID(int $category_id): Category
    {
        try {
            if ($category_id < 0) {throw new InvalidArgumentException('Category ID must be a positive integer.');}

            return Category::findOrFail($category_id);
        }
        catch (InvalidArgumentException|ModelNotFoundException $exception) {throw $exception;} catch (Throwable $exception) {throw new Exception('Unable to find a category with that ID.');}
    }

    /**
     * Retrieve all available categories.
     *
     * This method returns a collection of all categories stored in the database.
     *
     * @return Collection
     * @throws Exception
     */
    public function getAllCategories(): Collection
    {
        try {
            return Category::all();
        }
        catch (Throwable $exception) {throw new Exception('Unable to retrieve the available categories.');}
    }

    /**
     * Paginate filtered categories for admin management.
     *
     * @param array<string,mixed> $filters
     * @param int $perPage
     * @param int $page
     * @param array{field?:string|null,direction?:string|null}|null $sort
     */
    public function paginateCategories(array $filters = [], int $perPage = 10, int $page = 1, ?array $sort = null): LengthAwarePaginator
    {
        $query = Category::query()->whereNull('deleted_at');

        $search = trim((string)($filters['search'] ?? ''));
        if ($search !== '') {
            $like = '%' . mb_strtolower($search) . '%';
            $query->whereRaw('LOWER(name) LIKE ?', [$like]);
        }

        $direction = strtolower($sort['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $field = $sort['field'] ?? 'name';
        if ($field === 'created_at') {
            $query->orderBy('created_at', $direction);
        } else {
            $query->orderBy('name', $direction)->orderBy('id', 'asc');
        }

        return $query->paginate(max(1, $perPage), ['*'], 'page', max(1, $page));
    }

    /**
     * Create a new category.
     *
     * @throws Exception
     */
    public function createCategory(string $name): Category
    {
        try {
            $name = trim($name);
            if ($name === '') {
                throw new InvalidArgumentException('Category name is required.');
            }

            $exists = Category::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->exists();

            if ($exists) {
                throw new InvalidArgumentException('A category with this name already exists.');
            }

            $category = Category::create(['name' => $name]);

            $this->logAudit('CATEGORY_CREATED', $category->id, [
                'category_id' => (int)$category->id,
                'category_name' => (string)$category->name,
                'source' => 'category_create',
            ]);

            return $category;
        } catch (InvalidArgumentException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new Exception('Unable to create the category.');
        }
    }

    /**
     * Update the name of an existing category by its ID.
     *
     * Finds the category with the specified ID and updates its name.
     * The ID must be a positive integer, and the category must exist.
     *
     * @param int $category_id
     * @param string $name
     * @return Category
     * @throws Exception
     */
    public function updateCategory(int $category_id, string $name, string $justification): Category
    {
        try {
            if ($category_id < 0) {throw new InvalidArgumentException('Category ID must be a positive integer.');}
            $trimmedJustification = trim($justification);
            if (mb_strlen($trimmedJustification) < 10) {
                throw new InvalidArgumentException('Justification must be at least 10 characters.');
            }

            $name = trim($name);
            if ($name === '') {
                throw new InvalidArgumentException('Category name is required.');
            }

            $dup = Category::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->where('id', '!=', $category_id)
                ->exists();

            if ($dup) {
                throw new InvalidArgumentException('A category with this name already exists.');
            }

            // Find value based on the name. Update its fields
            $category = Category::findOrFail($category_id);

            $category->name = $name;
            $category->save();

            // Add audit trail

            // AUDIT: category renamed (best-effort)
            try {
                /** @var \App\Services\AuditService $audit */
                $audit = app(\App\Services\AuditService::class);

                $actor   = Auth::user();
                $actorId = $actor?->id ?? null;

                if ($actorId > 0) {
                    $actorLabel = $actor
                        ? (trim(($actor->first_name ?? '').' '.($actor->last_name ?? '')) ?: (string)($actor->email ?? ''))
                        : 'system';

                    $meta = [
                        'category_id'   => (int) ($category->id ?? $category_id),
                        'new_name'      => (string) $name,
                        'source'        => 'category_update',
                        'justification' => $trimmedJustification,
                    ];

                    $ctx = ['meta' => $meta];
                    if (function_exists('request') && request()) {
                        $ctx = $audit->buildContextFromRequest(request(), $meta);
                    }

                    $audit->logAdminAction(
                        $actorId,
                        'category',
                        'CATEGORY_UPDATED',
                        (string) ($category->id ?? $category_id),
                        $ctx
                    );
                }
            } catch (Throwable) { /* best-effort */ }


            // Return collection of updated values
            return $category;
        }
        catch (InvalidArgumentException|ModelNotFoundException $exception) {throw $exception;} catch (Throwable $exception) {throw new Exception('Unable to synchronize category data.');}
    }

    /**
     * Delete a category by its ID.
     *
     * Performs a soft or hard delete of the category depending on the model configuration.
     * The ID must be a positive integer and a justification of at least 10 characters is required.
     * If the category does not exist, a `ModelNotFoundException` is thrown.
     *
     * @param int $category_id
     * @param string $justification
     * @return bool
     * @throws Exception
     */

    public function deleteCategory(int $category_id, string $justification): bool
    {
        try {
            if ($category_id < 0) throw new InvalidArgumentException('Category ID must be a positive integer.');
            $trimmedJustification = trim($justification);
            if (mb_strlen($trimmedJustification) < 10) {
                throw new InvalidArgumentException('Justification must be at least 10 characters.');
            }

            $category = Category::findOrFail($category_id);
            $deleted  = (bool) $category->delete();

            // AUDIT: category deleted (best-effort)
            try {
                /** @var \App\Services\AuditService $audit */
                $audit = app(\App\Services\AuditService::class);

                $actor   = Auth::user();
                $actorId = $actor?->id ?? null;

                if ($actorId > 0 && $deleted) {
                    $actorLabel = $actor
                        ? (trim(($actor->first_name ?? '').' '.($actor->last_name ?? '')) ?: (string)($actor->email ?? ''))
                        : 'system';

                    $meta = [
                        'category_id'   => (int) ($category->id ?? $category_id),
                        'category_name' => (string) ($category->name ?? ''),
                        'source'        => 'category_delete',
                        'justification' => $trimmedJustification,
                    ];

                    $ctx = ['meta' => $meta];
                    if (function_exists('request') && request()) {
                        $ctx = $audit->buildContextFromRequest(request(), $meta);
                    }

                    $audit->logAdminAction(
                        (int) $actorId,
                        'category',
                        'CATEGORY_DELETED',
                        (string) ($category->id ?? $category_id),
                        $ctx
                    );
                }
            } catch (Throwable) { /* best-effort */ }

            return $deleted;
        }
        catch (InvalidArgumentException|ModelNotFoundException $exception) { throw $exception; }
        catch (Throwable $exception) { throw new Exception('Unable to delete the specified category.'); }
    }

    /**
     * Helper to log audit events for category changes.
     *
     * @param array<string,mixed> $meta
     */
    protected function logAudit(string $action, int $resourceId, array $meta = []): void
    {
        try {
            /** @var \App\Services\AuditService $audit */
            $audit = app(\App\Services\AuditService::class);
            $actor = Auth::user();
            $actorId = $actor?->id ?? null;

            if (!$actorId) {
                return;
            }

            $ctx = ['meta' => $meta];
            if (function_exists('request') && request()) {
                $ctx = $audit->buildContextFromRequest(request(), $meta);
            }

            $audit->logAdminAction(
                (int)$actorId,
                'category',
                $action,
                (string)$resourceId,
                $ctx
            );
        } catch (Throwable) {
            // best-effort
        }
    }



    // /**
    // public function deleteCategory(int $category_id): bool
    // {
    // try {
    // if ($category_id < 0) throw new InvalidArgumentException('Category ID must be a positive integer.');

    // return Category::findOrFail($category_id)->delete();
    // }
    // catch (InvalidArgumentException|ModelNotFoundException $exception) {throw $exception;} catch (Throwable $exception) {throw new Exception('Unable to delete the specified category.');}
    // }
    /////////////////////////////////////////////// SPECIALIZED FUNCTIONS //////////////////////////////////////////////
}
