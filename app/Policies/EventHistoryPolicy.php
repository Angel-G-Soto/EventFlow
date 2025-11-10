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
    public function viewMyApprovalHistory(User $user): bool
    {
        return contains($user->roles->get('name')->toArray(), ['advisor', 'venue-manager', 'event-approver']);
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
        return contains($user->roles->get('name')->toArray(), ['advisor', 'venue-manager', 'event-approver'])
            && $user->id == $eventHistory->approver_id;
    }
}
