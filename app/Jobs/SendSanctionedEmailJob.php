<?php

namespace App\Jobs;

use App\Mail\SanctionEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Queued job that sends an "event sanctioned" notification to the event creator.
 *
 * This job composes and sends the {@see SanctionedEmail} mailable to the event
 * creator using Laravel's queue system. Typical usage is to dispatch this job
 * from an application service after an event is marked as sanctioned.
 *
 * @package App\Jobs
 * @see \App\Mail\SanctionedEmail
 */
class SendSanctionedEmailJob implements ShouldQueue
{
    use Queueable, SerializesModels, Dispatchable, InteractsWithQueue;

    /**
     * Email address of the event creator who will receive the sanction notice.
     *
     * @var string
     */
    public string $creatorEmail;


    public array $recipientEmails;

    public string $creatorRoute;
    public string $approverRoute;


    /**
     * Event metadata used within the email (e.g., id, title, starts_at, ends_at).
     *
     * @var array<string,mixed>
     */
    public array $eventData;

    /**
     * Create a new job instance.
     *
     * @param string              $creatorEmail Email address of the event creator.
     * @param array<string,mixed> $eventData    Event metadata (id, title, dates, etc.).
     */
    public function __construct(string $creatorEmail,
    array $recipientEmails,
    array $eventData,
    string $creatorRoute,
    string $approverRoute)
    {
        $this->creatorEmail = $creatorEmail;
        $this->eventData = $eventData;
        $this->recipientEmails = $recipientEmails;
        $this->creatorRoute = $creatorRoute;
        $this->approverRoute = $approverRoute;
    }

    /**
     * Execute the job.
     *
     * Sends the {@see SanctionedEmail} mailable to the creator. If additional
     * recipients should be included (e.g., CC an advisor), consider extending
     * the constructor signature or leveraging the mailable's envelope.
     *
     * @return void
     */
    public function handle(): void
    {
        Mail::to($this->creatorEmail)           
            ->send(new SanctionEmail($this->eventData, $this->creatorRoute));
        
        foreach ($this->recipientEmails as $recipient) {
                Mail::to($recipient)
                    ->send(new SanctionEmail($this->eventData, $this->approverRoute));
            }
    }
}
