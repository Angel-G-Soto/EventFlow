<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\Event;
use App\Models\EventHistory;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Auth;

class EventPolicy
{
    /**
     * Determine whether the user can view and edit the model.
     */
    public function viewMyRequest(User $user, Event $event): bool
    {
        return $user->id === $event->creator_id;
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
    public function viewMyPendingRequests(): bool
    {
        $user = Auth::user();
        // Ensure the roles relationship is loaded
        $user->load('roles');

        // Check if the user's roles contain any of the roles: 'advisor', 'venue-manager', or 'event-approver'
        return $user->roles->pluck('name')->intersect(['advisor', 'venue-manager', 'event-approver'])->isNotEmpty();
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
     * @return \Illuminate\Auth\Access\Response
     */
    public function manageMyPendingRequests(User $user, Event $event): Response
    {
        $user->loadMissing('roles');

        // Extract the status of the event
        $eventStatus = $event->status;

        // Check the user's roles based on the event status
        $allowed = match ($eventStatus) {
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

        return $allowed
            ? Response::allow()
            : Response::deny('This action is unauthorized or the request has already been processed.');
    }

    /**
     * Determine whether the user can download a PDF summary for the event.
     *
     * Only the original requester or an approver role can download once the event is approved.
     */
    public function downloadEventPdf(User $user, Event $event): bool
    {
        if ($event->status !== 'approved') {
            return false;
        }

        if ($user->id === $event->creator_id) {
            return true;
        }

        $user->loadMissing('roles');

        return $user->roles
            ->pluck('name')
            ->intersect(['advisor', 'venue-manager', 'event-approver'])
            ->isNotEmpty();
    }

    public function viewMyDocument(User $user, Event $event): bool
    {
        $isVenueManager = $user->roles->contains('name', 'venue-manager') &&
            in_array($event->venue_id, $user->department->venues()->pluck('id')->toArray());
        $isAdvisor = $user->roles->contains('name', 'advisor') &&
            $event->organization_advisor_email === $user->email;
        $isDscaApprover = $user->roles->contains('name', 'event-approver');
        $isSystemAdmin = $user->roles->contains('name', 'system-admin');

        $isCreator = $user->id === $event->creator_id;


        return $isVenueManager || $isAdvisor || $isDscaApprover || $isCreator || $isSystemAdmin;
    }
}
