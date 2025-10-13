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

    public function dispatchAdvisorNotification(string $advisorEmail, array $eventData): void
    {
        SendAdvisorEmailJob::dispatch($advisorEmail, $eventData);
        #SendEmailJob::dispatch($advisorEmail,$eventData, 'advisor');
    }

    public function dispatchApprovalRequiredNotification(string
                                                   $approverEmail, string $approverType, array $eventData): void
    {
        SendApprovalRequiredEmailJob::dispatch($approverEmail, $approverType, $eventData);

    }
    public function dispatchRejectionNotification(string $creatorEmail,
                                                  array $eventData, string $justification): void
    {
        SendRejectionEmailJob::dispatch($creatorEmail, $eventData, $justification);
    }

    public function dispatchSanctionedNotification(string
                                                    $creatorEmail, array $eventData): void
    {
        SendSanctionedEmailJob::dispatch($creatorEmail, $eventData);
    }

    public function dispatchCancellationNotifications(array
                                                      $recipientEmails, array $eventData, string $justification): void
    {

        SendCancelationEmailJob::dispatch($recipientEmails, $eventData, $justification);

    }


    public function dispatchWithdrawalNotifications(array $recipientEmails, array $eventData, string $justification): void
    {
        SendWithdrawalEmailJob::dispatch($recipientEmails, $eventData, $justification);
    }

}
