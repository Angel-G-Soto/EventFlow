<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\EventHistory;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RequestPolicy
{
    /**
     * Determine whether the user can view and edit the model.
     */
    public function viewAndEditMyRequest(User $user, Event $event): bool
    {
        return $user->id = $event->creator_id;
    }

    /**
     * Check if the user has permission to view their pending requests.
     *
     * This method checks if the user has any of the roles:
     * 'advisor', 'venue-manager', or 'event-approver'. If the user has
     * any of these roles, they are allowed to view their pending requests.
     *
     * @param User $user The user requesting the access.
     * @return bool Returns true if the user has any of the allowed roles, false otherwise.
     */
    public function viewMyPendingRequests(User $user): bool
    {
        // Check if the user's roles contain any of the roles: 'advisor', 'venue-manager', or 'event-approver'
        return contains($user->roles->get('name')->toArray(), ['advisor', 'venue-manager', 'event-approver']);
    }

    /**
     * Check if the user has permission to manage their pending requests.
     *
     * This method checks if the user has the necessary role to manage an event based on its status.
     * The allowed roles and conditions are:
     * - 'advisor': Can manage events with status 'pending - advisor approval' if their email matches the event's advisor email.
     * - 'venue-manager': Can manage events with status 'pending - venue manager approval' if they are associated with the venue.
     * - 'event-approver': Can manage events with status 'pending - dsca approval'.
     * The method returns false if none of the conditions match.
     *
     * @param User $user The user requesting the management permission.
     * @param Event $event The event to check.
     * @return bool Returns true if the user has the appropriate role and meets the conditions, false otherwise.
     */
    public function manageMyPendingRequests(User $user, Event $event): bool
    {
        // Extract the status of the event
        $eventStatus = $event->status;

        // Check the user's roles based on the event status
        return match ($eventStatus) {
            // 'advisor' role can manage events pending advisor approval if the user's email matches the event's advisor email
            'pending - advisor approval' => $user->roles->contains('name', 'advisor')
                && $event->organization_advisor_email === $user->email,

            // 'venue-manager' role can manage events pending venue manager approval if they are associated with the venue
            'pending - venue manager approval' => $user->roles->contains('name', 'venue-manager')
                && in_array($event->venue_id, $user->department->venues()->pluck('id')->toArray()),

            // 'event-approver' role can manage events pending DSCA approval
            'pending - dsca approval' => $user->roles->contains('name', 'event-approver'),

            // Default case: returns false if the event status does not match any of the above conditions
            default => false,
        };
    }


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
