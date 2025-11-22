<?php

namespace App\Jobs;

use App\Mail\CancellationEmail;
use App\Services\EventHistoryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Queued job that sends an "event cancelled" notification to the creator and approvers.
 *
 * This job composes and sends the {@see CancellationEmail} mailable to the
 * creator and each approver using Laravel's queue system. Typical usage is to
 * dispatch this job from an application service after an event is cancelled
 * with a justification.
 *
 * @package App\Jobs
 * @see \App\Mail\CancellationEmail
 */
class SendCancellationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected EventHistoryService $eventHistoryService;
    public string $creatorEmail;

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

    /**
     * Create a new job instance.
     *
     * @param array<string,mixed> $eventData     Event metadata (id, title, dates, etc.).
     * @param string              $justification Reason for the cancellation.
     */
    public function __construct(
        string $creatorEmail,
        array $eventData,
        string $justification,
    ) {
        $this->creatorEmail = $creatorEmail;
        $this->eventData = $eventData;
        $this->justification = $justification;
        $this->eventHistoryService = app(EventHistoryService::class);
    }

    /**
     * Execute the job.
     *
     * Sends the {@see CancellationEmail} to the creator and each approver tied
     * to the event history.
     *
     * @return void
     */
    public function handle(): void
    {
        Mail::to($this->creatorEmail)
            ->send(
                new CancellationEmail(
                    $this->eventData,
                    $this->justification,
                    route('user.request', ['event' => $this->eventData['id']])
                )
            );

        $eventHistories = $this->eventHistoryService->getEventHistoriesByEventId($this->eventData['id']);

        foreach ($eventHistories as $eventHistory) {
            $recipientEmail = $eventHistory->approver->email;
            Mail::to($recipientEmail)->send(
                new CancellationEmail(
                    $this->eventData,
                    $this->justification,
                    route('approver.history.request', ['eventHistory' => $eventHistory->id])
                )
            );
        }
    }
}
