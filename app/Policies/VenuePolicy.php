<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Venue;
use Illuminate\Auth\Access\Response;
use function Pest\Laravel\isAuthenticated;

class VenuePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->roleAssignment->role->r_name == 'system-admin';
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Venue $venue): bool
    {
        return ($user->department->id == $venue->department->id
            && $user->roleAssignment->role->r_name == 'manager')
            || $user->roleAssignment->role->r_name == 'system-admin';
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->roleAssignment->role->r_name == 'system-admin';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Venue $venue): bool
    {
        return $user->hasRole('system-admin')
            || ($user->hasRole('department-director')
                && $user->department_id == $venue->department_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Venue $venue): bool
    {
        return $user->hasRole('system-admin')
            || ($user->hasRole('department-director')
                && $user->department_id == $venue->department_id);
    }

    /**
     * Determine whether the user can assign the manager to the venue
     */
    public function assignManager(User $user, Venue $venue): bool
    {
        return $user->hasRole('system-admin')
            || ($user->hasRole('department-director')
                && $user->department_id == $venue->department_id);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function updateRequirements(User $user, Venue $venue): bool
    {
        return $user->id == $venue->manager_id
            || $user->roleAssignment->role->r_name == 'system-admin';
    }
}
