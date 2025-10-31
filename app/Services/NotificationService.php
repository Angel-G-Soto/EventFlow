<?php

namespace App\Services;

use App\Jobs\SendAdvisorEmailJob;
use App\Jobs\SendApprovalRequiredEmailJob;
use App\Jobs\SendCancelationEmailJob;
use App\Jobs\SendRejectionEmailJob;
use App\Jobs\SendSanctionedEmailJob;
use App\Jobs\SendWithdrawalEmailJob;

class NotificationService
{


    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }


    /**
     * Sends notification email to the corresponding approver to notify them about the pending request
     * Approver types:
     *  -venue: Venue manager.Manages requested venue
     *  -dsca: Representative of the Department of Social and Cultural Activities
     *  -dean: Representative of the dean of administration
     *
     * @param string $approverEmail  - Approvers email address
     * @param string $approverType  -
     * @param array  $eventData      - Relevant event information
     * @return void
     */
    public function dispatchApprovalRequiredNotification(string
                                                   $approverEmail, array $eventData): void
    {
        SendApprovalRequiredEmailJob::dispatch($approverEmail, $eventData);

    }

    /**
     *
     * Sends an email notifying the request creator about the rejection
     * @param string $creatorEmail   Email of request creator
     * @param array $eventData       Relevant devent details
     * @param string $justification
     * @return void
     *
     * Sed
     */
    public function dispatchRejectionNotification(string $creatorEmail,
                                                  array $eventData, string $justification): void
    {
        SendRejectionEmailJob::dispatch($creatorEmail, $eventData, $justification);
    }

    /**
     * Sends an email notifying the request creator about the approval
     *
     * @param string $creatorEmail
     * @param array $eventData
     * @return void
     */
    public function dispatchSanctionedNotification(string
                                                    $creatorEmail, array $eventData): void
    {
        SendSanctionedEmailJob::dispatch($creatorEmail, $eventData);
    }

    /**
     * Sends an email notifying the relevant approvers about the event cancellation
     *
     * @param array $recipientEmails
     * @param array $eventData
     * @param string $justification
     * @return void
     */
    public function dispatchCancellationNotifications(array
                                                      $recipientEmails, array $eventData, string $justification): void
    {
        SendCancelationEmailJob::dispatch($recipientEmails, $eventData, $justification);

    }

    /**
     * Sends an email notifying the relevant approvers about the event withdrawal
     *
     * @param array $recipientEmails
     * @param array $eventData
     * @param string $justification
     * @return void
     */
    public function dispatchWithdrawalNotifications(array $recipientEmails, array $eventData, string $justification): void
    {
        SendWithdrawalEmailJob::dispatch($recipientEmails, $eventData, $justification);
    }

}
