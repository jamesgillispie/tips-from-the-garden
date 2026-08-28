<?php

namespace App\Jobs;

use App\Mail\ArticleReady;
use App\Mail\TranscriptReady;
use App\Models\Submission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class DeliverArticle implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300];

    public function __construct(
        public Submission $submission,
    ) {}

    public function handle(): void
    {
        $this->submission->loadMissing(['article', 'transcript', 'user']);

        $article = $this->submission->article;
        $deliveredNow = false;

        if ($article !== null) {
            // delivered_at doubles as the idempotency flag: a retried attempt
            // that already emailed the article must not email it twice.
            if ($article->delivered_at === null) {
                Mail::to($this->submission->user->email)->send(new ArticleReady($article));

                $deliveredAt = now();
                $article->update(['delivered_at' => $deliveredAt]);
                $this->submission->update(['delivered_at' => $deliveredAt]);
                $deliveredNow = true;
            }

            $this->submission->markAs(Submission::STATUS_READY);
        } else {
            if ($this->submission->transcript === null) {
                throw new RuntimeException('No article or transcript found for submission.');
            }

            if ($this->submission->delivered_at === null) {
                Mail::to($this->submission->user->email)->send(new TranscriptReady($this->submission));

                $this->submission->update(['delivered_at' => now()]);
                $deliveredNow = true;
            }

            $this->submission->markAs(Submission::STATUS_READY);
        }

        if ($deliveredNow && $this->submission->user->canLearnVoice()) {
            UpdateVoiceProfile::dispatch($this->submission);
        }
    }
}
