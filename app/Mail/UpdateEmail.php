<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UpdateEmail extends Mailable
{
    use Queueable, SerializesModels;

    public array $eventDetails;
    public string $approverName;
    public string $role;

    /**
     * Create a new message instance.
     */
    public function __construct(
        array $eventDetails,
        string $approverName,
        string $role
    )
    {
        //
        $this->eventDetails = $eventDetails;
        $this->approverName = $approverName;
        $this->role = $role;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Update: ' . $this->eventDetails['title'],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.update-email',
            with: [
                'event'=> $this->eventDetails,
                'approverName' => $this->approverName,
                'role' => $this->role,
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
