<?php

namespace App\Services;

use App\Jobs\SendApprovalRequiredEmailJob;
use App\Jobs\SendCancellationEmailJob;
use App\Jobs\SendRejectionEmailJob;
use App\Jobs\SendRequestCreatedEmailJob;
use App\Jobs\SendSanctionedEmailJob;
use App\Jobs\SendUpdateEmailJob;
use App\Jobs\SendWithdrawalEmailJob;
use App\Models\Event;
use App\Models\User;
use App\Models\Venue;
use App\Services\VenueService;
use Carbon\Carbon;

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
     * Dispatch an email confirming a request submission to its creator.
     *
     * @param string               $creatorEmail  Email address of the request creator.
     * @param array<string,mixed>  $eventDetails  Associative array with event metadata.
     *
     * @return void
     *
     * @see SendRequestCreatedEmailJob
     */
    public function dispatchRequestCreatedNotification(
        string $creatorEmail,
        array $eventDetails
    ): void {
        SendRequestCreatedEmailJob::dispatch(
            $creatorEmail,
            $eventDetails
        );
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
        array $eventDetails,
        string $justification,

    ): void {
        SendRejectionEmailJob::dispatch(
            creatorEmail: $creatorEmail,
            eventData: $eventDetails,
            justification: $justification);
    }

    /**
     * Dispatch an email notifying the event creator and approvers that the event has been sanctioned.
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
        array $eventDetails
    ): void {
        SendSanctionedEmailJob::dispatch(
            creatorEmail: $creatorEmail,
            eventData: $eventDetails);
    }

    /**
     * Dispatch cancellation emails to a set of recipients.
     *
     * @param string               $creatorEmail   Email address of the event creator.
     * @param array<string,mixed>  $eventDetails   Associative array with event metadata (e.g., id, title, starts_at, ends_at).
     * @param string               $justification  Reason for the cancellation that will be included in the email.
     *
     * @return void
     *
     * @see SendCancellationEmailJob
     */
    public function dispatchCancellationNotifications(
        string $creatorEmail,
        array $eventDetails,
        string $justification,
    ): void {
        SendCancellationEmailJob::dispatch(
            creatorEmail: $creatorEmail,
            eventData: $eventDetails,
            justification: $justification,
        );
    }

    /**
     * Dispatch withdrawal emails to the creator and prior approvers.
     *
     * @param string               $creatorEmail   Email address of the event creator.
     * @param array<string,mixed>  $eventDetails   Associative array with event metadata (e.g., id, title, starts_at, ends_at).
     * @param string               $justification  Reason for the withdrawal that will be included in the email.
     *
     * @return void
     *
     * @see SendWithdrawalEmailJob
     */
    public function dispatchWithdrawalNotifications(
        string $creatorEmail,
        array $eventDetails,
        string $justification
    ): void {
        SendWithdrawalEmailJob::dispatch(
            creatorEmail: $creatorEmail,
            eventData: $eventDetails,
            justification: $justification,
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




}
