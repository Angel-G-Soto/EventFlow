<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable notifying an approver that their action is required for an event.
 *
 * This class renders the `mail.approval-required-email` Blade view and is safe
 * to be queued. Provide the event metadata via the constructor so the template
 * can display relevant fields (e.g., title, times, location, requester).
 *
 * Typical usage:
 *  Mail::to($approver)->send(new ApprovalRequiredEmail($eventData));
 *  // or queue(): Mail::to($approver)->queue(new ApprovalRequiredEmail($eventzzData));
 *
 * @package App\Mail
 */
class ApprovalRequiredEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Event metadata consumed by the email view (e.g., id, title, starts_at, ends_at).
     *
     * @var array<string,mixed>
     */
    public array $event;

    /**
     * Create a new message instance.
     *
     * @param array<string,mixed> $event      Associative array with event information
     *                                        used by the Blade view. Common keys include
     *                                        `id`, `title`, `starts_at`, `ends_at`, `location`,
     *                                        and `requester_name`.
     */
    public function __construct(array $event)
    {
        $this->event = $event;
    }

    /**
     * Get the message envelope (subject, from, etc.).
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope(): Envelope
    {
        $subjectTitle = $this->event['title'] ?? 'Event';

        return new Envelope(
            subject: "Action Required: Approve {$subjectTitle}",
        );
    }

    /**
     * Get the message content definition (view and data binding).
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.approval-required-email',
            with: [
                'event' => $this->event,
                'route' => route('approver.pending.request', ['event' => $this->event['id'] ?? 0]),
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
