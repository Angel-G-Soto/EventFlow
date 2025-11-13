<?php

namespace App\Jobs;

use App\Mail\ApprovalRequiredEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Queued job that notifies a specific approver that their action is required for an event.
 *
 * This job composes and sends the {@see ApprovalRequiredEmail} mailable to the
 * approver using Laravel's queue system. Typical usage is to dispatch this job
 * when an event transitions into an "awaiting approval" state.
 *
 * @package App\Jobs
 * @see \App\Mail\ApprovalRequiredEmail
 */
class SendApprovalRequiredEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Email address of the approver who must review/approve the event.
     *
     * @var string
     */
    public string $approverEmail;

    /**
     * Event metadata used within the email (e.g., id, title, starts_at, ends_at).
     *
     * @var array<string,mixed>
     */
    public array $eventData;

    /**
     * Create a new job instance.
     *
     * @param string              $approverEmail Email address of the approver.
     * @param array<string,mixed> $eventData     Event metadata (id, title, dates, etc.).
     */
    public function __construct(string $approverEmail, array $eventData)
    {
        $this->approverEmail = $approverEmail;
        $this->eventData = $eventData;
    }

    /**
     * Execute the job.
     *
     * Sends the {@see ApprovalRequiredEmail} mailable to the approver. If you
     * plan to notify multiple approvers, consider transforming this job into a
     * fan‑out pattern (one job per email) or passing an array of recipients and
     * iterating inside `handle()`.
     *
     * @return void
     */
    public function handle(): void
    {
        Mail::to($this->approverEmail)->send(
            new ApprovalRequiredEmail($this->eventData)
        );
    }
}
