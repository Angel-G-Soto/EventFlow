<?php

namespace App\Services;

use App\Jobs\SendApprovalRequiredEmailJob;
use App\Jobs\SendCancellationEmailJob;
use App\Jobs\SendRejectionEmailJob;
use App\Jobs\SendSanctionedEmailJob;
use App\Jobs\SendWithdrawalEmailJob;

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
        SendApprovalRequiredEmailJob::dispatch($approverEmail, $eventDetails);
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
        string $justification
    ): void {
        SendRejectionEmailJob::dispatch($creatorEmail, $eventDetails, $justification);
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
        array $eventDetails
    ): void {
        SendSanctionedEmailJob::dispatch($creatorEmail, $eventDetails);
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
        array $recipientEmails,
        array $eventDetails,
        string $justification
    ): void {
        SendCancellationEmailJob::dispatch($recipientEmails, $eventDetails, $justification);
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
        array $recipientEmails,
        array $eventDetails,
        string $justification
    ): void {
        SendWithdrawalEmailJob::dispatch($recipientEmails, $eventDetails, $justification);
    }
}
