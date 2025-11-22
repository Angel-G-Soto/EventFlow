<?php

namespace App\Jobs;

use App\Mail\RejectionEmail;
use App\Models\Event;
use App\Services\EventHistoryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Queued job that sends an "event rejected" notification to the event creator.
 *
 * This job composes and sends the {@see RejectionEmail} mailable to the event
 * creator using Laravel's queue system. Typical usage is to dispatch this job
 * from an application service after an event is rejected with a justification.
 *
 * @package App\Jobs
 * @see \App\Mail\RejectionEmail
 */
class SendRejectionEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $eventHistoryService;

    /**
     * Email address of the event creator who will receive the rejection notice.
     *
     * @var string
     */
    public string $creatorEmail;

    /**
     * Event metadata used within the email (e.g., id, title, starts_at, ends_at).
     *
     * @var array<string,mixed>
     */
    public array $eventData;

    /**
     * Human‑readable reason for the rejection included in the email body.
     *
     * @var string
     */
    public string $justification;
  


    /**
     * Create a new job instance.
     *
     * @param string              $creatorEmail  Email address of the event creator.
     * @param array<string,mixed> $eventData     Event metadata (id, title, dates, etc.).
     * @param string              $justification Reason the event was rejected.
     */
    public function __construct(string $creatorEmail, 
    array $eventData, 
    string $justification,)
    {
        $this->creatorEmail = $creatorEmail;
        $this->eventData = $eventData;
        $this->justification = $justification;

        $this->eventHistoryService=app(EventHistoryService::class);
    }

    /**
     * Execute the job.
     *
     * Sends the {@see RejectionEmail} mailable to the creator. If additional
     * recipients should be included (e.g., CC an advisor), consider extending
     * the constructor signature or leveraging the mailable's envelope.
     *
     * @return void
     */
    public function handle(): void
    {
        Mail::to($this->creatorEmail)
            ->send(new RejectionEmail($this->eventData, $this->justification, route('user.request', ['event' => $this->eventData['id']]))
        );

        $eventHistories= $this->eventHistoryService->getEventHistoriesByEventId($this->eventData['id']);

        // $recipientEmails = $this->eventHistoryService->getEventApproverEmails(new Event($this->eventData));

        foreach ($eventHistories as $eventHistory) {
                $recipientEmail = $eventHistory->approver->email;
                Mail::to($recipientEmail)
                    ->send(new RejectionEmail($this->eventData, $this->justification, route('approver.history.request', ['eventHistory' => $eventHistory->id]))); 
        }
    }
}
