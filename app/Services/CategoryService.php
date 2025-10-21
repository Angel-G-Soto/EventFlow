<?php
namespace App\Services;
use App\Models\Category;
use App\Models\Department;
use App\Models\User;
use App\Models\Venue;
use Exception;
use \Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use Throwable;

class CategoryService {

    /**
     * Return the category with the corresponding id
     *
     * @param int $id
     * @return Category
     * @throws Exception
     */
    public static function getCategoryByID(int $id): Category
    {
        try {
            if ($id == 0 || $id == null) {throw new InvalidArgumentException();}

            return Category::find($id);
        }
        catch (InvalidArgumentException $exception) {throw $exception;}
        catch (Throwable $exception) {throw new Exception('We were not able to find a category with that ID.');}
    }

    /**
     * Retrieves all the available categories
     *
     * @return Collection
     * @throws Exception
     */
    public static function getAllCategories(): Collection
    {
        try {
            return Category::all();
        }
        catch (Throwable $exception) {throw new Exception('We were not able to retrieve the available categories.');}
    }

    /**
     * Updates the name of the category with the specified id
     *
     * @param int $id
     * @param string $name
     * @return Category
     * @throws Exception
     */
    public static function updateCategory(int $id, string $name): Category
    {
        try {
            if ($id == 0 || $id == null) {throw new InvalidArgumentException();}

            // Find value based on the name. Update its fields
            $category = Category::updateOrCreate(
                [
                    'id' => $id,
                ],
                [
                    'c_name' => $name,
                ]
            );

            // Add audit trail

            // Return collection of updated values
            return $category;
        }
        catch (InvalidArgumentException $exception) {throw $exception;}
        catch (Throwable $exception) {throw new Exception('Unable to synchronize category data.');}
    }

    /**
     * Deletes the category that contains the specified id
     *
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public static function deleteCategory(int $id): bool
    {
        try {
            if ($id < 0 || $id == null) throw new InvalidArgumentException();

            return Category::find($id)->delete();
        }
        catch (InvalidArgumentException $exception) {throw $exception;}
        catch (Throwable $exception) {throw new Exception('We were not able to delete the specified category.');}
    }

    /**
     * Returns the use requirements of the specified category
     *
     * @param int $id
     * @return Collection
     * @throws Exception
     */
    public static function getUseRequirement(int $id): Collection
    {
        {
            try {
                if ($id == 0 || $id == null) {throw new InvalidArgumentException();}
                $category = Category::find($id);
                if ($category == null) {throw new ModelNotFoundException();}
                return $category->requirements;
            }
            catch (InvalidArgumentException|ModelNotFoundException $exception) {throw $exception;}
            catch (Throwable $exception) {throw new Exception('We were not able to find the requirements for the category.');}
        }
    }
}
