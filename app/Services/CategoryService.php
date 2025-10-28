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
     * Return the category with the corresponding id
     *
     * @param int $id
     * @return Category
     * @throws Exception
     */
    public function getCategoryByID(int $id): Category
    {
        try {
            if ($id < 0) {throw new InvalidArgumentException('Category ID must be a positive integer.');}

            return Category::findOrFail($id);
        }
        catch (InvalidArgumentException|ModelNotFoundException $exception) {throw $exception;} catch (Throwable $exception) {throw new Exception('Unable to find a category with that ID.');}
    }

    /**
     * Retrieves all the available categories
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
     * Updates the name of the category with the specified id
     *
     * @param int $id
     * @param string $name
     * @return Category
     * @throws Exception
     */
    public function updateCategory(int $id, string $name): Category
    {
        try {
            if ($id < 0) {throw new InvalidArgumentException('Category ID must be a positive integer.');}

            // Find value based on the name. Update its fields
            $category = Category::findOrFail($id);

            $category->name = $name;
            $category->save();

            // Add audit trail

            // Return collection of updated values
            return $category;
        }
        catch (InvalidArgumentException|ModelNotFoundException $exception) {throw $exception;} catch (Throwable $exception) {throw new Exception('Unable to synchronize category data.');}
    }

    /**
     * Deletes the category that contains the specified id
     *
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public function deleteCategory(int $id): bool
    {
        try {
            if ($id < 0) throw new InvalidArgumentException('Category ID must be a positive integer.');

            return Category::findOrFail($id)->delete();
        }
        catch (InvalidArgumentException|ModelNotFoundException $exception) {throw $exception;} catch (Throwable $exception) {throw new Exception('Unable to delete the specified category.');}
    }


    /////////////////////////////////////////////// SPECIALIZED FUNCTIONS //////////////////////////////////////////////
}
