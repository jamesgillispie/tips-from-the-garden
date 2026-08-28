<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AiTranscriptionDisabled extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your memo was not transcribed — AI is turned off',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.ai-transcription-disabled',
            with: ['settingsUrl' => route('account')],
        );
    }
}
