<?php

namespace Tests\Feature;

use App\Jobs\TranscribeAudio;
use App\Jobs\WriteArticle;
use App\Models\Submission;
use App\Models\User;
use Database\Seeders\ArticleTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_fake_pipeline_produces_an_article(): void
    {
        $this->seed(ArticleTemplateSeeder::class);

        $user = User::fromEmail('gardener@example.test');

        $submission = Submission::create([
            'user_id' => $user->id,
            'source' => Submission::SOURCE_UPLOAD,
            'audio_path' => 'audio/fake.m4a',
            'original_filename' => 'fake.m4a',
        ]);

        TranscribeAudio::dispatchSync($submission);
        WriteArticle::dispatchSync($submission);

        $submission->refresh();

        $this->assertEquals(Submission::STATUS_READY, $submission->status);
        $this->assertNotNull($submission->transcript);
        $this->assertNotNull($submission->article);
        $this->assertNotEmpty($submission->article->title);
        $this->assertNotEmpty($submission->article->download_token);
    }

    public function test_retried_jobs_do_not_duplicate_transcripts_or_articles(): void
    {
        $this->seed(ArticleTemplateSeeder::class);

        $user = User::fromEmail('gardener@example.test');

        $submission = Submission::create([
            'user_id' => $user->id,
            'source' => Submission::SOURCE_UPLOAD,
            'audio_path' => 'audio/fake.m4a',
            'original_filename' => 'fake.m4a',
        ]);

        // Each job allows retries — a second attempt must not double up.
        TranscribeAudio::dispatchSync($submission);
        TranscribeAudio::dispatchSync($submission);
        WriteArticle::dispatchSync($submission);
        WriteArticle::dispatchSync($submission);

        $this->assertDatabaseCount('transcripts', 1);
        $this->assertDatabaseCount('articles', 1);
        $this->assertEquals(Submission::STATUS_READY, $submission->fresh()->status);
    }

    public function test_article_is_viewable_and_downloadable_by_token(): void
    {
        $user = User::fromEmail('gardener@example.test');

        $submission = Submission::create([
            'user_id' => $user->id,
            'source' => Submission::SOURCE_UPLOAD,
            'audio_path' => 'audio/fake.m4a',
        ]);

        $article = $submission->article()->create([
            'title' => 'Squash Bugs and Second Chances',
            'body_md' => "## The beds\n\nThe tomatoes finally set fruit.",
            'user_id' => $user->id,
            'published' => true,
        ]);

        $this->get(route('articles.show', ['token' => $article->download_token]))
            ->assertOk()
            ->assertSee('Squash Bugs and Second Chances');

        $this->get(route('articles.download', ['token' => $article->download_token, 'format' => 'md']))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
    }
}
