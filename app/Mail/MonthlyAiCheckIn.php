<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class MonthlyAiCheckIn extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'A quick check-in about AI and your garden memos',
        );
    }

    public function content(): Content
    {
        $expiresAt = now()->addDays((int) config('pipeline.ai.signed_link_days', 14));

        return new Content(
            markdown: 'emails.monthly-ai-check-in',
            with: [
                'preferences' => $this->user->aiPreferences(),
                'confirmUrl' => URL::temporarySignedRoute(
                    'ai-check-in.show',
                    $expiresAt,
                    ['user' => $this->user->id, 'action' => 'confirm'],
                ),
                'disableUrl' => URL::temporarySignedRoute(
                    'ai-check-in.show',
                    $expiresAt,
                    ['user' => $this->user->id, 'action' => 'disable'],
                ),
                'settingsUrl' => route('account'),
            ],
        );
    }
}
