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
        return ($user->roleAssignment->role->r_name == 'manager'
            || $user->roleAssignment->role->r_name == 'admin');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Venue $venue): bool
    {
        return ($user->department->id == $venue->department->id
            && $user->roleAssignment->role->r_name == 'manager')
            || $user->roleAssignment->role->r_name == 'admin';
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->roleAssignment->role->r_name == 'admin';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Venue $venue): bool
    {
        return ($user->department->id == $venue->department->id
            && $user->roleAssignment->role->r_name == 'manager')
            || $user->roleAssignment->role->r_name == 'admin';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Venue $venue): bool
    {
        return $user->department->id = $venue->department->id && $user->roleAssignment->role->r_name == 'manager';;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Venue $venue): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Venue $venue): bool
    {
        return $user->department->id = $venue->department->id && $user->roleAssignment->role->r_name == 'manager';
    }
}
