<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Venue;
use Illuminate\Auth\Access\Response;
use function Pest\Laravel\isAuthenticated;

class VenuePolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Venue $venue): bool
    {
        return (($user->getRoleNames()->contains('department-director') || $user->getRoleNames()->contains('venue-manager'))
                && $user->department_id == $venue->department_id);
    }
    public function viewIndex(User $user): bool
    {
        return  $user->getRoleNames()->contains('venue-manager');
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
