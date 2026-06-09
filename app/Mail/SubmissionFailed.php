<?php

namespace App\Mail;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubmissionFailed extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Submission $submission,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'We hit a snag with your memo — please send it again',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.submission-failed',
            with: [
                'retryUrl' => route('home'),
            ],
        );
    }
}
