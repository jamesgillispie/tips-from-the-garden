<?php

namespace Tests\Feature;

use App\Jobs\WriteArticle;
use App\Models\Article;
use App\Models\Submission;
use App\Models\User;
use App\Services\AiConsentService;
use Database\Seeders\ArticleTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiTransparencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_generated_articles_record_and_render_ai_provenance(): void
    {
        $article = $this->generatedArticle();

        $this->assertFileExists(public_path('icons/eu-ai-labels/ai-label-black.svg'));
        $this->assertFileExists(public_path('icons/eu-ai-labels/ai-label-black.png'));
        $this->assertTrue($article->is_ai_assisted);
        $this->assertSame('fake:writer', $article->ai_model);
        $this->assertTrue($article->submission->ai_used);

        $this->get($article->publicUrl())
            ->assertOk()
            ->assertSee('AI-assisted article')
            ->assertSee('name="generator:ai-assisted" content="true"', false)
            ->assertSee('name="generator:ai-model" content="fake:writer"', false)
            ->assertSee(asset('icons/eu-ai-labels/ai-label-black.svg'), false);
    }

    public function test_downloaded_ai_articles_keep_the_declaration(): void
    {
        $article = $this->generatedArticle();

        $this->get(route('articles.download', [
            'token' => $article->download_token,
            'format' => 'md',
        ]))->assertOk()
            ->assertSee('AI-assisted content; model: fake:writer')
            ->assertSee('AI-assisted article — written with help from artificial intelligence.');

        $this->get(route('articles.download', [
            'token' => $article->download_token,
            'format' => 'pdf',
        ]))->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_non_ai_articles_are_not_mislabeled(): void
    {
        $user = User::fromEmail('gardener@example.test');
        $submission = Submission::create([
            'user_id' => $user->id,
            'source' => Submission::SOURCE_PASTE,
            'status' => Submission::STATUS_READY,
        ]);
        $article = $submission->article()->create([
            'title' => 'Notes I wrote myself',
            'body_md' => 'No model touched these notes.',
            'user_id' => $user->id,
            'is_ai_assisted' => false,
            'published' => true,
        ]);

        $this->get($article->publicUrl())
            ->assertOk()
            ->assertDontSee('AI-assisted article')
            ->assertDontSee('generator:ai-assisted', false);
    }

    private function generatedArticle(): Article
    {
        $this->seed(ArticleTemplateSeeder::class);

        $user = User::fromEmail('gardener@example.test');
        app(AiConsentService::class)->applyBroadChoice($user, true, false);

        $submission = Submission::create([
            'user_id' => $user->id,
            'source' => Submission::SOURCE_PASTE,
            'status' => Submission::STATUS_TRANSCRIBED,
        ]);
        $submission->transcript()->create([
            'raw_text' => 'The tomatoes finally set fruit after the cold snap, and the basil needs pinching back.',
            'transcriber' => 'paste',
        ]);

        WriteArticle::dispatchSync($submission);

        return $submission->fresh()->article;
    }
}
