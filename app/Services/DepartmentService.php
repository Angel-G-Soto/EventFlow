<?php
namespace App\Services;
use App\Models\Department;
use App\Models\User;
use App\Models\Venue;
use \Illuminate\Database\Eloquent\Collection;

class DepartmentService {

    public static function updateDepartmentAssignment(Department $department, Venue $venue): void
    {
        $venue->department_id = $department->id;
        $venue->save();
    }

    public static function updateUserDepartment(Department $department, User $manager): void
    {
        $manager->department_id = $department->id;
        $manager->save();
    }

    public static function getDepartmentVenues(Department $department): Collection
    {
        return Department::with('venues')->where('id', $department->id)->get();
    }
}
