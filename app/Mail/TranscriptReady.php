<?php

namespace App\Mail;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TranscriptReady extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Submission $submission,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your garden notes are ready',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.transcript-ready',
            with: [
                'statusUrl' => route('submissions.status', ['submission' => $this->submission->uuid]),
            ],
        );
    }
}
