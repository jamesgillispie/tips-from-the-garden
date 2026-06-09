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

        $this->submission->markAs(Submission::STATUS_WRITING);

        $transcript = $this->submission->transcript;

        if ($transcript === null) {
            throw new RuntimeException('No transcript found for submission.');
        }

        $user = $this->submission->user;
        $template = ArticleTemplate::pick();

        $draft = $writer->write(new WriteRequest(
            transcript: $transcript->raw_text,
            template: $template,
            voiceProfile: $user->voiceProfile?->profile_text,
            authorName: $user->name,
        ));

        $this->submission->article()->create([
            'title' => $draft->title,
            'body_md' => $draft->bodyMarkdown,
            'user_id' => $user->id,
            'article_template_id' => $template?->id,
            'writer' => $writer->identifier(),
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
