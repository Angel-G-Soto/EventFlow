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
     * @param string $creatorEmail
     * @param array $eventData
     * @param string $justification
     * @return void
     */
    public function dispatchRejectionNotification(string $creatorEmail,
                                                  array $eventData, string $justification): void
    {
        SendRejectionEmailJob::dispatch($creatorEmail, $eventData, $justification);
    }

    /**
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
