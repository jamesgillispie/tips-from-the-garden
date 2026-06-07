<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MagicLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $loginUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your sign-in link for Tips From The Garden',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.magic-link',
            with: [
                'loginUrl' => $this->loginUrl,
            ],
        );
    }
}
