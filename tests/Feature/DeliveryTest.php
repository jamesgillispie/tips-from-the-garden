<?php

namespace Tests\Feature;

use App\Jobs\DeliverArticle;
use App\Mail\ArticleReady;
use App\Models\Article;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_ready_email_contains_the_full_article(): void
    {
        $article = $this->makeArticle();

        $mailable = new ArticleReady($article);

        $mailable->assertSeeInHtml('Squash Bugs and Second Chances');
        $mailable->assertSeeInHtml('The tomatoes finally set fruit');
        $mailable->assertSeeInText('The tomatoes finally set fruit');
    }

    public function test_a_retried_delivery_does_not_email_the_article_twice(): void
    {
        Mail::fake();

        $article = $this->makeArticle();
        $submission = $article->submission;

        DeliverArticle::dispatchSync($submission);
        DeliverArticle::dispatchSync($submission); // simulates a retry

        Mail::assertSent(ArticleReady::class, 1);
        $this->assertNotNull($article->fresh()->delivered_at);
    }

    private function makeArticle(): Article
    {
        $user = User::fromEmail('gardener@example.test');

        $submission = Submission::create([
            'user_id' => $user->id,
            'source' => Submission::SOURCE_UPLOAD,
            'audio_path' => 'audio/fake.m4a',
            'status' => Submission::STATUS_READY,
        ]);

        return $submission->article()->create([
            'title' => 'Squash Bugs and Second Chances',
            'body_md' => "## The beds\n\nThe tomatoes finally set fruit after the cold snap.",
            'user_id' => $user->id,
            'published' => true,
        ]);
    }
}
