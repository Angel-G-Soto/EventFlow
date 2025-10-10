<?php
namespace App\Services;
use App\Models\Department;
use App\Models\User;
use App\Models\Venue;
use Psy\Util\Str;
use Ramsey\Collection\Collection;
use function Laravel\Prompts\error;

class DepartmentService {

    public function updateDepartmentAssignment(Department $department, Venue $venue)
    {
        $venue->department_id = $department->id;
        $venue->save();
    }

    public function updateUserDepartment(Department $department, User $user)
    {
        $user->department_id = $department->id;
        $user->save();
    }

    public function getDepartmentVenues(Department $department): Collection
    {
        return $department->venues;
    }
}
