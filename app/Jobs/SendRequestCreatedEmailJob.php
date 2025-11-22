<?php

namespace App\Jobs;

use App\Mail\RequestCreatedEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Queued job that emails the creator when a new event request is submitted.
 *
 * This job builds and sends the {@see RequestCreatedEmail} mailable using the
 * queue system so the form submission path stays responsive.
 */
class SendRequestCreatedEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Email address of the request creator.
     *
     * @var string
     */
    public string $creatorEmail;

    /**
     * Event metadata used within the email (e.g., id, title, starts_at, ends_at).
     *
     * @var array<string,mixed>
     */
    public array $eventDetails;

    /**
     * Create a new job instance.
     *
     * @param string              $creatorEmail Email address of the request creator.
     * @param array<string,mixed> $eventDetails Event metadata (id, title, dates, etc.).
     */
    public function __construct(string $creatorEmail, array $eventDetails)
    {
        $this->creatorEmail = $creatorEmail;
        $this->eventDetails = $eventDetails;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->creatorEmail)->send(
            new RequestCreatedEmail($this->eventDetails)
        );
    }
}
