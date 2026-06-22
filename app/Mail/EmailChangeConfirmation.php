<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the *new* address a gardener wants to move to. Opening the link is
 * what actually switches their login email.
 */
class EmailChangeConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $url,
        public int $minutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirm your new email for '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.email-change-confirmation',
        );
    }
}
