<?php

namespace App\Services;

use App\Jobs\SendApprovalRequiredEmailJob;
use App\Jobs\SendCancellationEmailJob;
use App\Jobs\SendRejectionEmailJob;
use App\Jobs\SendSanctionedEmailJob;
use App\Jobs\SendUpdateEmailJob;
use App\Jobs\SendWithdrawalEmailJob;
use App\Models\Event;
use App\Models\User;
use App\Models\Venue;
use App\Services\VenueService;

/**
 * Service responsible for dispatching notification email jobs for EventFlow.
 *
 * This class centralizes the logic to queue notification emails related to an
 * event's lifecycle (approval required, rejection, sanctioned, cancellation,
 * and withdrawal). Each public method simply dispatches the corresponding
 * queue job with the required payload.
 *
 * @package App\Services
 */
class NotificationService
{
    /**
     * Create a new NotificationService instance.
     */
    public function __construct()
    {
        // Intentionally empty; kept for future dependency injection needs.
    }

    /**
     * Dispatch an email notification to a specific approver indicating that
     * their action is required for the provided event.
     *
     * @param string               $approverEmail  Single email address for the approver that must review/act.
     * @param array<string,mixed>  $eventDetails   Associative array with event metadata (e.g., id, title, starts_at, ends_at).
     *
     * @return void
     *
     * @see SendApprovalRequiredEmailJob
     */
    public function dispatchApprovalRequiredNotification(
        string $approverEmail,
        array $eventDetails
    ): void {
        SendApprovalRequiredEmailJob::dispatch(
            $approverEmail,
            $eventDetails);
    }

    /**
     * Dispatch an email notifying the event creator that the event was rejected.
     *
     * @param string               $creatorEmail   Email address of the event creator.
     * @param array<string,mixed>  $eventDetails   Associative array with event metadata (e.g., id, title, starts_at, ends_at).
     * @param string               $justification  Reason the event was rejected.
     *
     * @return void
     *
     * @see SendRejectionEmailJob
     */
    public function dispatchRejectionNotification(
        string $creatorEmail,
        array $recipientEmails,
        array $eventDetails,
        string $justification,
        string $creatorRoute,
        string $approverRoute

    ): void {
        SendRejectionEmailJob::dispatch(
            creatorEmail: $creatorEmail,
            recipientEmails: $recipientEmails,
            eventData: $eventDetails,
            justification: $justification,
            creatorRoute: $creatorRoute,
            approverRoute: $approverRoute);
    }

    /**
     * Dispatch an email notifying the event creator that the event has been sanctioned.
     *
     * @param string               $creatorEmail  Email address of the event creator.
     * @param array<string,mixed>  $eventDetails  Associative array with event metadata (e.g., id, title, starts_at, ends_at).
     *
     * @return void
     *
     * @see SendSanctionedEmailJob
     */
    public function dispatchSanctionedNotification(
        string $creatorEmail,
        array $recipientEmails,
        array $eventDetails,
        string $creatorRoute,
        string $approverRoute
    ): void {
        SendSanctionedEmailJob::dispatch(
            creatorEmail: $creatorEmail,
            recipientEmails: $recipientEmails,
            eventData: $eventDetails,
            creatorRoute: $creatorRoute,
            approverRoute: $approverRoute);
    }

    /**
     * Dispatch cancellation emails to a set of recipients.
     *
     * @param array<int,string>    $recipientEmails  List of email addresses that should receive the cancellation notice.
     * @param array<string,mixed>  $eventDetails     Associative array with event metadata (e.g., id, title, starts_at, ends_at).
     * @param string               $justification    Reason for the cancellation that will be included in the email.
     *
     * @return void
     *
     * @see SendCancellationEmailJob
     */
    public function dispatchCancellationNotifications(
        string $creatorEmail,
        array $recipientEmails,
        array $eventDetails,
        string $justification,
        string $creatorRoute,
        string $approverRoute
    ): void {
        SendCancellationEmailJob::dispatch(
            creatorEmail: $creatorEmail,
            recipientEmails: $recipientEmails,
            eventData: $eventDetails,
            justification: $justification,
            creatorRoute: $creatorRoute,
            approverRoute: $approverRoute
        );
    }

    /**
     * Dispatch withdrawal emails to a set of recipients.
     *
     * @param array<int,string>    $recipientEmails  List of email addresses that should receive the withdrawal notice.
     * @param array<string,mixed>  $eventDetails     Associative array with event metadata (e.g., id, title, starts_at, ends_at).
     * @param string               $justification    Reason for the withdrawal that will be included in the email.
     *
     * @return void
     *
     * @see SendWithdrawalEmailJob
     */
    public function dispatchWithdrawalNotifications(
        string $creatorEmail,
        array $recipientEmails,
        array $eventDetails,
        string $justification,
        string $creatorRoute,
        string $approverRoute
    ): void {
        SendWithdrawalEmailJob::dispatch(
            creatorEmail: $creatorEmail,
            recipientEmails: $recipientEmails,
            eventData: $eventDetails,
            justification: $justification,
            creatorRoute: $creatorRoute,
            approverRoute: $approverRoute


        );
    }


    public function dispatchUpdateNotification(
        string $creatorEmail,
        array $eventDetails,
        string $approverName,
        string $role){

        SendUpdateEmailJob::dispatch(
            creatorEmail: $creatorEmail,
            approverName: $approverName,
            eventDetails: $eventDetails,
            role: $role);


    }
    public function getEventDetails(Event $event):array
    {
        $venue = app(VenueService::class)->getVenueById($event->venue_id);
        $user = app(UserService::class)->findUserById($event->creator_id);

        return [
            'title' => $event->title,
            'organization_name' => $event->organization_name,
            'creator_name' => $user->first_name . ' ' . $user->last_name,
            'organization_advisor_name' => $event->organization_advisor_name,
            'organization_advisor_email' => $event->organization_advisor_email,
            'creator_email' => $user->email,
            'start_time' => $event->start_time,
            'end_time'=> $event->end_time,
            'venue_name' => $venue->name,
            'id' => $event->id,
            ];
    }


}
