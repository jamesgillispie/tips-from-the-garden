<?php

namespace App\Mail;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubmissionReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Submission $submission,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Got your voice memo — your article is on its way',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.submission-received',
            with: [
                'statusUrl' => route('submissions.status', ['submission' => $this->submission->uuid]),
            ],
        );
    }
}
