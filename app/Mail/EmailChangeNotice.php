<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the gardener's *current* address whenever an email change is
 * requested — the safety net that tells the real owner if someone with their
 * password is trying to move the account.
 */
class EmailChangeNotice extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $newEmail,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'A change was requested on your '.config('app.name').' account',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.email-change-notice',
        );
    }
}
