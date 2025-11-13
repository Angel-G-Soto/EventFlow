<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable notifying recipients that an event has been cancelled.
 *
 * This class renders the `mail.cancellation-email` Blade view and is safe to
 * be queued. Provide the event metadata and a human-readable justification via
 * the constructor so the template can display relevant context (e.g., title,
 * time, location, and the reason for the cancellation).
 *
 * Typical usage:
 *  Mail::to($recipients)->send(new CancellationEmail($eventData, $justification));
 *  // or queue(): Mail::to($recipients)->queue(new CancellationEmail($eventData, $justification));
 *
 * @package App\Mail
 */
class CancellationEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Event metadata consumed by the email view (e.g., id, title, starts_at, ends_at, location).
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
     * Create a new message instance.
     *
     * @param array<string,mixed> $eventData     Associative array with event information
     *                                          used by the Blade view. Common keys include
     *                                          `id`, `title`, `starts_at`, `ends_at`, `location`,
     *                                          and `requester_name`.
     * @param string              $justification Reason the event was cancelled.
     */
    public function __construct(array $eventData, string $justification)
    {
        $this->eventData = $eventData;
        $this->justification = $justification;
    }

    /**
     * Get the message envelope (subject, from, etc.).
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope(): Envelope
    {
        $subjectTitle = $this->eventData['title'] ?? 'Event';

        return new Envelope(
            subject: "Event Cancelled: {$subjectTitle}",
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
            view: 'mail.cancellation-email',
            with: [
                'event' => $this->eventData,
                'justification' => $this->justification,
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
