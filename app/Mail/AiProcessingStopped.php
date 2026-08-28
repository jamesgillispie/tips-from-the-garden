<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AiProcessingStopped extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'AI processing is now off for your garden memos',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.ai-processing-stopped',
            with: ['settingsUrl' => route('account')],
        );
    }
}
