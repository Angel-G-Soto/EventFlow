<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable notifying the event creator that their event has been sanctioned.
 *
 * This class renders the `mail.sanctioned-email` Blade view and is safe to be
 * queued. Provide the event metadata via the constructor so the template can
 * display relevant fields (e.g., title, times, location, and any sanction notes).
 *
 * Typical usage:
 *  Mail::to($creatorEmail)->send(new SanctionEmail($eventData));
 *  // or queue(): Mail::to($creatorEmail)->queue(new SanctionEmail($eventData));
 *
 * @package App\Mail
 */
class SanctionEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Event metadata consumed by the email view (e.g., id, title, starts_at, ends_at, location).
     *
     * @var array<string,mixed>
     */
    public array $eventData;

    public string $route;

    /**
     * Create a new message instance.
     *
     * @param array<string,mixed> $eventData  Associative array with event information
     *                                        used by the Blade view. Common keys include
     *                                        `id`, `title`, `starts_at`, `ends_at`, `location`,
     *                                        and `requester_name`.
     */
    public function __construct(array $eventData, string $route)
    {
        $this->eventData = $eventData;
        $this->route = $route;
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
            subject: "Event Sanctioned: {$subjectTitle}",
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
            view: 'mail.sanction-email',
            with: [
                'event' => $this->eventData,
                'route' => $this->route,
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
