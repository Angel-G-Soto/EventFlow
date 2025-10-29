<?php

namespace App\Services;
use App\Models\Category;
use Exception;
use \Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
    public function updateCategory(int $category_id, string $name): Category
    {
        try {
            if ($category_id < 0) {throw new InvalidArgumentException('Category ID must be a positive integer.');}

            // Find value based on the name. Update its fields
            $category = Category::findOrFail($category_id);

            $category->name = $name;
            $category->save();

            // Add audit trail

            // Return collection of updated values
            return $category;
        }
        catch (InvalidArgumentException|ModelNotFoundException $exception) {throw $exception;} catch (Throwable $exception) {throw new Exception('Unable to synchronize category data.');}
    }

    /**
     * Delete a category by its ID.
     *
     * Performs a soft or hard delete of the category depending on the model configuration.
     * The ID must be a positive integer. If the category does not exist, a `ModelNotFoundException` is thrown.
     *
     * @param int $category_id
     * @return bool
     * @throws Exception
     */
    public function deleteCategory(int $category_id): bool
    {
        try {
            if ($category_id < 0) throw new InvalidArgumentException('Category ID must be a positive integer.');

            return Category::findOrFail($category_id)->delete();
        }
        catch (InvalidArgumentException|ModelNotFoundException $exception) {throw $exception;} catch (Throwable $exception) {throw new Exception('Unable to delete the specified category.');}
    }


    /////////////////////////////////////////////// SPECIALIZED FUNCTIONS //////////////////////////////////////////////
}
