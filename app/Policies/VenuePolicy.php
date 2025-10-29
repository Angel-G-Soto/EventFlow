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
        return $user->getRoleNames()->contains('system-admin');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Venue $venue): bool
    {
        return (($user->getRoleNames()->contains('department-director') || $user->getRoleNames()->contains('venue-manager'))
                && $user->department_id == $venue->department_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->getRoleNames()->contains('system-admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Venue $venue): bool
    {
        return $user->getRoleNames()->contains('system-admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user): bool
    {
        return $user->getRoleNames()->contains('system-admin');
    }

    /**
     * Determine whether the user can assign the manager to the venue
     */
    public function assignManager(User $user, Venue $venue): bool
    {
        return $user->getRoleNames()->contains('department-director') && $user->department_id == $venue->department_id;
    }

    /**
     * Determine whether the user can update the model's requirements.
     */
    public function updateRequirements(User $user, Venue $venue): bool
    {
        return $user->getRoleNames()->contains('venue-manager') && $user->department_id == $venue->department_id;
    }

    /**
     * Determine whether the user can update the model's availability.
     */
    public function updateAvailability(User $user, Venue $venue): bool
    {
        return $user->getRoleNames()->contains('venue-manager') && $user->department_id == $venue->department_id;
    }
}
