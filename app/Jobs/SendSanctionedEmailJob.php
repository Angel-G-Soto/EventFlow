<?php

namespace App\Jobs;

use App\Mail\SanctionEmail;
use App\Services\EventHistoryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Queued job that sends an "event sanctioned" notification to the creator and approvers.
 *
 * This job composes and sends the {@see SanctionEmail} mailable to the event
 * creator and approvers using Laravel's queue system. Typical usage is to
 * dispatch this job from an application service after an event is marked as
 * sanctioned.
 *
 * @package App\Jobs
 * @see \App\Mail\SanctionedEmail
 */
class SendSanctionedEmailJob implements ShouldQueue
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
     * Create a new job instance.
     *
     * @param string              $creatorEmail Email address of the event creator.
     * @param array<string,mixed> $eventData    Event metadata (id, title, dates, etc.).
     */
    public function __construct(
        string $creatorEmail,
        array $eventData
    ) {
        $this->creatorEmail = $creatorEmail;
        $this->eventData = $eventData;
        $this->eventHistoryService = app(EventHistoryService::class);
    }

    /**
     * Execute the job.
     *
     * Sends the {@see SanctionEmail} mailable to the creator and each approver
     * tied to the event history.
     *
     * @return void
     */
    public function handle(): void
    {
        Mail::to($this->creatorEmail)           
            ->send(new SanctionEmail(
                $this->eventData,
                route('user.request', ['event' => $this->eventData['id']])
            ));
        
        $eventHistories = $this->eventHistoryService->getEventHistoriesByEventId($this->eventData['id']);

        foreach ($eventHistories as $eventHistory) {
            $recipient = $eventHistory->approver->email;
            Mail::to($recipient)
                ->send(new SanctionEmail(
                    $this->eventData,
                    route('approver.history.request', ['eventHistory' => $eventHistory->id])
                ));
        }
    }
}
