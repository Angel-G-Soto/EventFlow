<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use App\Models\Role;
use Carbon\Carbon;
use DateTime;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * EventService
 *
 * The central hub for all business logic and state management related to an event's lifecycle.
 */
class EventService
{
    protected UserService $userService;
    // protected NotificationService $notificationService;
    protected AuditService $auditService;

    public function __construct(
        UserService $userService,
      
        AuditService $auditService
    ) {
        $this->userService = $userService;
        // $this->notificationService = $notificationService;
        $this->auditService = $auditService;
    }

    /**
     * Creates a new event request and starts the approval workflow.
     */
    public function createEvent(User $creator, array $data): Event
    {
        // Find the advisor user record by the email provided from the Nexo session.
        $advisor = $this->userService->findOrCreateUser($data['advisor_email'], $data['advisor_name'] ?? 'Advisor');

        $event = Event::create([
            'creator_id' => $creator->user_id,
            'current_approver_id' => $advisor->user_id,
            'venue_id' => $data['venue_id'],
            'event_type_id' => $data['event_type_id'], 
            'e_title' => $data['e_title'],
            'e_description' => $data['e_description'],
            'e_status' => 'Under Review - Advisor',
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'organization_nexo_id' => $data['organization_nexo_id'],
            'organization_name' => $data['organization_name'],
            'advisor_email' => $data['advisor_email'],
            'sells_food' => $data['sells_food'] ?? false, 
            'uses_institutional_funds' => $data['uses_institutional_funds'] ?? false, 
            'has_external_guests' => $data['has_external_guests'] ?? false, 
        ]);

        $event->history()->create(['user_id' => $creator->user_id, 'eh_action' => 'Submitted']);
        // Notification logic will beate(['user_id' => $creator->user_id, 'eh_action' => 'Submitted']);
        // Notification logic will be added here
        // $this->notificationService->sendNewRequestNotification($advisor->u_email, ['title' => $event->er_title]);

        return $event;
    }

    /**
     * Advances an event request to the next stage in the approval workflow.
     */
    public function approveEvent(Event $event, User $approver): Event
    {
        $event->eventHistories()->create(['u_id' => $approver->user_id, 'eh_action' => "Approved: {$event->er_status}", 'eh_timestamp' => now()]);

        switch ($event->er_status) {
            case 'Under Review - Advisor':
                $event->er_status = 'Under Review - Venue Manager';
                $event->current_approver_id = $event->venue->manager_id;
                $nextApprover = $event->venue->manager;
                break;
            case 'Under Review - Venue Manager':
                $event->er_status = 'Under Review - DSCA';
                $event->current_approver_id = User::getUsersWithRole('dsca-staff')->firstOrFail()->user_id;
                $nextApprover = User::getUsersWithRole('dean-of-administration')->firstOrFail()->user_id;
                break;
            case 'Under Review - DSCA':
                $event->er_status = 'Under Review - DA';
                $event->current_approver_id = null;
                $event->assigned_to_role_id = Role::findByCode('dean-admin')->firstOrFail()->role_id;
                $nextApprover = null; // Notify the group
                break;
            case 'Under Review - DA':
                return $this->finalizeEventApproval($event, $approver);
        }
        
        $event->save();

        // Notification logic will be added here
        // if ($nextApprover) {
        //     $this->notificationService->sendApprovalRequiredNotification($nextApprover->u_email, 'Next Approver', ['title' => $event->er_title]);
        // } else {
        //     // Logic to notify all users in the assigned role
        // }

        return $event;
    }

    /**
     * Performs the final approval, officially sanctioning the event.
     */
    protected function finalizeEventApproval(Event $event, User $finalApprover): Event
    {
        $event->er_status = 'Approved';
        $event->current_approver_id = null;
        $event->assigned_to_role_id = null;
        $event->save();

        $event->eventHistories()->create(['user_id' => $finalApprover->user_id, 'eh_action' => 'Approved: Final', 'eh_timestamp' => now()]);
        // $this->notificationService->sendSanctionedNotification($event->creator->u_email, ['title' => $event->er_title]);

        return $event;
    }

    /**
     * Denies an event request, halting the workflow.
     */
    public function denyEvent(Event $event, User $approver, string $reason): Event
    {
        $event->er_status = 'Denied';
        $event->current_approver_id = null;
        $event->assigned_to_role_id = null;
        $event->save();

        $event->eventHistories()->create(['user_id' => $approver->user_id, 'eh_action' => 'Denied', 'eh_comment' => $reason, 'eh_timestamp' => now()]);
        // $this->notificationService->sendRejectionNotification($event->creator->u_email, ['title' => $event->er_title], $reason);
        
        return $event;
    }

    /**
     * Allows the creator to withdraw a pending request.
     */
    public function withdrawEvent(Event $event, User $student): Event
    {
        $event->er_status = 'Withdrawn';
        $event->current_approver_id = null;
        $event->assigned_to_role_id = null;
        $event->save();

        $event->eventHistories()->create(['user_id' => $student->user_id, 'eh_action' => 'Withdrawn', 'eh_timestamp' => now()]);
        // Notify relevant parties
        
        return $event;
    }

    /**
     * Cancels an event that has already been approved.
     */
    public function cancelEvent(Event $event, User $user, string $reason): Event
    {
        $event->er_status = 'Canceled';
        $event->save();

        $event->eventHistories()->create(['user_id' => $user->user_id, 'eh_action' => 'Canceled', 'eh_comment' => $reason, 'eh_timestamp' => now()]);
        // Notify all relevant parties
        
        return $event;
    }

    /**
     * Finds and marks past, approved events as "Completed".
     */
    public function markCompletedEvents(): int
    {
        return Event::where('er_status', 'Approved')
            ->where('end_time', '<', Carbon::now()->subDay())
            ->update(['er_status' => 'Completed']);
    }

    /**
     * Retrieves a paginated list of event requests for an approver's dashboard.
     */
    public function getEventsForApproverDashboard(User $approver): LengthAwarePaginator
    {
        return Event::where('current_approver_id', $approver->user_id)
            ->orWhere(function ($query) use ($approver) {
                $query->whereNull('current_approver_id')
                      ->whereIn('assigned_to_role_id', $approver->roles->pluck('role_id'));
            })
            ->latest('start_time')
            ->paginate(15);
    }
    
    /**
     * Finds all "Approved" events that overlap with a given time window.
     */
    public function getBookedVenueIdsAtTime(DateTime $startTime, DateTime $endTime): array
    {
        return Event::where('er_status', 'Approved')
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
            })
            ->pluck('venue_id')
            ->toArray();
    }
    
    /**
     * Re-routes pending approvals from an old manager to a new one for a specific venue.
     */
    public function reroutePendingVenueApprovals(int $venueId, int $oldManagerId, int $newManagerId): void
    {
        Event::where('venue_id', $venueId)
            ->where('current_approver_id', $oldManagerId)
            ->where('er_status', 'Under Review - Venue Manager')
            ->update(['current_approver_id' => $newManagerId]);
        
        // You would also dispatch a notification to the new manager here.
    }
    
    /**
     * Allows a System Administrator to forcibly change any aspect of an event request.
     */
    public function performManualOverride(Event $event, User $admin, array $changes, string $justification): Event
    {
        $event->fill($changes);
        $event->save();

        $this->auditService->logAdminAction($admin->user_id, $admin->u_name, 'ADMIN_OVERRIDE', $justification);
        $event->eventHistories()->create(['u_id' => $admin->user_id, 'eh_action' => 'Manual Override', 'eh_comment' => $justification, 'eh_timestamp' => now()]);

        return $event;
    }

    /**
     * Retrieves a paginated list of all event requests for a student's dashboard.
     */
    public function getEventsForStudentDashboard(string $userId): LengthAwarePaginator
    {
        return Event::where('creator_id', $userId)
            ->latest('start_time')
            ->paginate(15);
    }

    /**
     * Retrieves a paginated list of all terminal-state events with filtering.
     */
    public function getHistoricalEvents(array $filters = []): LengthAwarePaginator
    {
        $query = Event::query()->whereIn('er_status', ['Approved', 'Denied', 'Canceled', 'Withdrawn', 'Completed']);

        // Add filtering logic here based on the $filters array
        
        return $query->latest('start_time')->paginate(25);
    }

    /**
     * Retrieves a paginated list of all events for the admin oversight dashboard.
     */
    public function getAllEvents(array $filters = []): LengthAwarePaginator
    {
        $query = Event::query();

        // Add comprehensive filtering logic here for the admin
        if (!empty($filters['status'])) {
            $query->where('er_status', $filters['status']);
        }
        if (!empty($filters['venue_id'])) {
            $query->where('venue_id', $filters['venue_id']);
        }

        return $query->latest('start_time')->paginate(25);
    }
}

