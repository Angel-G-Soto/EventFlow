<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable confirming a newly submitted event request to its creator.
 */
class RequestCreatedEmail extends Mailable implements ShouldQueue
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
     * @param array<string,mixed> $event Associative array with event metadata.
     */
    public function __construct(array $event)
    {
        $this->event = $event;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subjectTitle = $this->event['title'] ?? 'Event Request';

        return new Envelope(
            subject: "Request Submitted: {$subjectTitle}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.request-created-email',
            with: [
                'event' => $this->event,
                'route' => route('user.request', ['event' => $this->event['id'] ?? 0]),
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
