<?php

namespace App\Jobs;

use App\Mail\ArticleReady;
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

    public function __construct(
        public Submission $submission,
    ) {}

    public function handle(): void
    {
        $article = $this->submission->article;

        if ($article === null) {
            throw new RuntimeException('No article found for submission.');
        }

        Mail::to($this->submission->user->email)->send(new ArticleReady($article));

        $article->update(['delivered_at' => now()]);

        UpdateVoiceProfile::dispatch($this->submission);
    }
}
