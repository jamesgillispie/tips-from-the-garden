<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent when a voice memo arrives by email from an address we don't recognise.
 * Rather than silently spin up a ghost account (or risk a spoofed sender
 * landing a memo on someone else's desk), we point the sender at sign-up so
 * their memos attach to a real, claimed account from then on.
 */
class NoAccountFound extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Set up your '.config('app.name').' account to send memos',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.no-account-found',
            with: ['registerUrl' => route('register')],
        );
    }
}
