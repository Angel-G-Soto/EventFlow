<?php

namespace App\Jobs;

use App\Mail\CancellationEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Queued job that sends an "event cancelled" notification to a set of recipients.
 *
 * This job composes and sends the {@see CancellationEmail} mailable to each
 * recipient using Laravel's queue system. Typical usage is to dispatch this job
 * from an application service after an event is cancelled with a justification.
 *
 * @package App\Jobs
 * @see \App\Mail\CancellationEmail
 */
class SendCancellationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Email addresses that should receive the cancellation notice.
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
     * Human‑readable reason for the cancellation included in the email body.
     *
     * @var string
     */
    public string $justification;

    public string $creatorEmail;

    /**
     * Create a new job instance.
     *
     * @param array<int,string>   $recipientEmails List of email addresses to notify.
     * @param array<string,mixed> $eventData       Event metadata (id, title, dates, etc.).
     * @param string              $justification   Reason for the cancellation.
     */
    public function __construct(
        string $creatorEmail,
        array $recipientEmails,
        array $eventData,
        string $justification
    ) {
        $this->creatorEmail = $creatorEmail;
        $this->recipientEmails = $recipientEmails;
        $this->eventData = $eventData;
        $this->justification = $justification;
    }

    /**
     * Execute the job.
     *
     * Iterates through the provided recipients and sends the {@see CancellationEmail}
     * mailable to each. If you expect a large number of recipients, consider
     * chunking or fan‑out strategies to keep individual job runtimes short.
     *
     * @return void
     */
    public function handle(): void
    {
        Mail::to($this->creatorEmail)->send(
            new CancellationEmail($this->eventData, $this->justification)
        );
        foreach ($this->recipientEmails as $recipientEmail) {
            Mail::to($recipientEmail)->send(
                new CancellationEmail($this->eventData, $this->justification)
            );
        }
    }
}
