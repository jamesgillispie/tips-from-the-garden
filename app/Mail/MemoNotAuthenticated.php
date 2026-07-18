<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to an account holder when a memo arrived claiming to be from their
 * address but failed sender authentication (ADR 0001). The memo itself is
 * discarded; this is the rate-limited "couldn't verify this was you" notice —
 * a heads-up for a legitimate gardener whose domain lacks SPF/DKIM, and a
 * spoofing alarm for everyone else.
 */
class MemoNotAuthenticated extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "We couldn't verify a memo sent from your address",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.memo-not-authenticated',
        );
    }
}
