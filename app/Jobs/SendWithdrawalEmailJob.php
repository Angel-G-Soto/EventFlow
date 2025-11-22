<?php

namespace App\Jobs;

use App\Services\EventHistoryService;
use App\Mail\WithdrawalEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Queued job that sends withdrawal notifications for an event to the creator and approvers.
 *
 * This job composes and sends the {@see WithdrawalEmail} mailable to the event
 * creator and each approver using Laravel's queue system. Typical usage is via
 * a service class that dispatches this job after a withdrawal action is
 * confirmed.
 *
 * @package App\Jobs
 * @see \App\Mail\WithdrawalEmail
 */
class SendWithdrawalEmailJob implements ShouldQueue
{
    use Queueable, SerializesModels, Dispatchable, InteractsWithQueue;

    protected EventHistoryService $eventHistoryService;
    public string $creatorEmail;

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

    /**
     * Create a new job instance.
     *
     * @param array<string,mixed> $eventData     Event metadata (id, title, dates, etc.).
     * @param string              $justification Reason for the withdrawal.
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
     * Sends the {@see WithdrawalEmail} to the creator and each approver tied to
     * the event history.
     *
     * @return void
     */
    public function handle(): void
    {
        Mail::to($this->creatorEmail)
            ->send(
                new WithdrawalEmail(
                    $this->eventData,
                    $this->justification,
                    route('user.request', ['event' => $this->eventData['id']])
                )
            );

        $eventHistories = $this->eventHistoryService->getEventHistoriesByEventId($this->eventData['id']);

        foreach ($eventHistories as $eventHistory) {
            $recipientEmail = $eventHistory->approver->email;
            Mail::to($recipientEmail)->send(
                new WithdrawalEmail(
                    $this->eventData,
                    $this->justification,
                    route('approver.history.request', ['eventHistory' => $eventHistory->id])
                )
            );
        }
    }
}
