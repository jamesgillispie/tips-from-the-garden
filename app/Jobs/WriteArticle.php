<?php

namespace App\Jobs;

use App\Models\ArticleTemplate;
use App\Models\Submission;
use App\Pipeline\Contracts\WriterContract;
use App\Pipeline\Data\WriteRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class WriteArticle implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $timeout = 1800;

    public int $tries = 2;

    public function __construct(
        public Submission $submission,
    ) {}

    public function handle(WriterContract $writer): void
    {
        // A retried attempt that already produced an article shouldn't write
        // (and bill for) a second one.
        if ($this->submission->article()->exists()) {
            $this->submission->markAs(Submission::STATUS_READY);

            return;
        }

        $user = $this->submission->user;

        // The setting is checked again when the queued job actually starts.
        // A gardener who opts out after dispatch must not trigger a model call.
        if (! $user->canWriteArticles()) {
            $this->submission->markAs(Submission::STATUS_TRANSCRIBED);

            return;
        }

        $this->submission->markAs(Submission::STATUS_WRITING);

        $transcript = $this->submission->transcript;

        if ($transcript === null) {
            throw new RuntimeException('No transcript found for submission.');
        }

        $template = ArticleTemplate::pick();

        $this->submission->update(['ai_used' => true]);

        $draft = $writer->write(new WriteRequest(
            transcript: $transcript->raw_text,
            template: $template,
            voiceProfile: $user->canLearnVoice() ? $user->voiceProfile?->profile_text : null,
            authorName: $user->name,
        ));

        $this->submission->article()->create([
            'title' => $draft->title,
            'body_md' => $draft->bodyMarkdown,
            'user_id' => $user->id,
            'article_template_id' => $template?->id,
            'writer' => $writer->identifier(),
            'is_ai_assisted' => true,
            'ai_model' => $writer->identifier(),
            'published' => true,
        ]);

        $this->submission->markAs(Submission::STATUS_READY);
    }

    public function failed(?\Throwable $exception): void
    {
        $this->submission->fresh()?->markFailed(
            $exception?->getMessage() ?? 'Article generation failed.'
        );
    }
}
