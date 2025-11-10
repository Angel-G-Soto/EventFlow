<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\EventHistory;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Auth;

class EventHistoryPolicy {
    /**
     *
     * Verifies if the user has the appropriate roles to access the Approval History Page
     *
     * @param User $user
     * @return bool
     */
    public function viewMyApprovalHistory(): bool
    {
        $user = Auth::user();
        // Ensure the roles relationship is loaded
        $user->load('roles');

        // Check if the user's roles contain any of the roles: 'advisor', 'venue-manager', or 'event-approver'
        return $user->roles->pluck('name')->intersect(['advisor', 'venue-manager', 'event-approver'])->isNotEmpty();
    }

    /**
     *
     * Verifies if the user has the appropriate roles to access the Approval History Details Page
     * for an event that was approved/rejected by this user. Also determines if the user can cancel
     * the action made.
     *
     * @param User $user
     * @param EventHistory $eventHistory
     * @return bool
     */
    public function manageMyApprovalHistory(User $user, EventHistory $eventHistory): bool
    {
        $user = Auth::user();
        // Ensure the roles relationship is loaded
        $user->load('roles');

        // Check if the user's roles contain any of the roles: 'advisor', 'venue-manager', or 'event-approver'
        return $user->roles->pluck('name')->intersect(['advisor', 'venue-manager', 'event-approver'])->isNotEmpty()
            && $user->id == $eventHistory->approver_id;
    }
}
