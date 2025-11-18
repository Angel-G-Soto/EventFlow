<?php

namespace App\Jobs;

use App\Mail\WithdrawalEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Queued job that sends withdrawal notifications for an event to a list of recipients.
 *
 * This job composes and sends the {@see WithdrawalEmail} mailable to each recipient
 * using Laravel's queue system. Typical usage is via a service class that
 * dispatches this job after a withdrawal action is confirmed.
 *
 * @package App\Jobs
 * @see \App\Mail\WithdrawalEmail
 */
class SendWithdrawalEmailJob implements ShouldQueue
{
    use Queueable, SerializesModels, Dispatchable, InteractsWithQueue;

    public string $creatorEmail;

    /**
     * Email addresses of recipients that should be notified.
     *
     * @var array<int,string>
     */
    public array $recipientEmails;

    /**
     * Event metadata used within the email (e.g., id, title, starts_at, ends_at).
     *
     * @var array<string,mixed>
     */
    public array $eventData;

    /**
     * Human‑readable reason for the withdrawal included in the email body.
     *
     * @var string
     */
    public string $justification;
    public string $creatorRoute;
    public string $approverRoute;

    /**
     * Create a new job instance.
     *
     * @param array<int,string>   $recipientEmails List of email addresses to notify.
     * @param array<string,mixed> $eventData       Event metadata (id, title, dates, etc.).
     * @param string              $justification   Reason for the withdrawal.
     */
    public function __construct(
        string $creatorEmail,
        array $recipientEmails,
        array $eventData,
        string $justification,
        string $creatorRoute,
        string $approverRoute
    ) {
        $this->creatorEmail = $creatorEmail;
        $this->recipientEmails = $recipientEmails;
        $this->eventData = $eventData;
        $this->justification = $justification;
        $this->creatorRoute = $creatorRoute;
        $this->approverRoute = $approverRoute;
    }

    /**
     * Execute the job.
     *
     * Iterates through the provided recipients and sends the {@see WithdrawalEmail}
     * mailable to each. If you expect a large number of recipients, consider
     * chunking or fan‑out strategies to keep individual job runtimes short.
     *
     * @return void
     */
    public function handle(): void
    {
        Mail::to($this->creatorEmail)
            ->send(new WithdrawalEmail($this->eventData, 
            $this->justification
            , $this->creatorRoute));
        
        foreach ($this->recipientEmails as $recipientEmail) {
           Mail::to($recipientEmail)->send(
               new WithdrawalEmail($this->eventData, 
               $this->justification
               , $this->approverRoute)
           );
       }

    }
}
