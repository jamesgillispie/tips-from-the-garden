<?php

namespace Tests\Feature;

use App\Mail\ArticleReady;
use App\Models\Article;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Imagick;
use Tests\TestCase;

class PhotoDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('pipeline.photos.disk'));
    }

    public function test_the_entry_page_shows_its_photos(): void
    {
        $article = $this->makeArticleWithPhoto();
        $photo = $article->submission->photos->first();

        $response = $this->get(route('articles.show', ['token' => $article->download_token]));

        $response->assertOk();
        $response->assertSee(route('articles.photo', [
            'token' => $article->download_token,
            'photo' => $photo,
            'size' => 'thumb',
        ]), escape: false);
        $response->assertSee(route('articles.photo', [
            'token' => $article->download_token,
            'photo' => $photo,
        ]), escape: false);
    }

    public function test_an_entry_without_photos_shows_no_gallery(): void
    {
        $article = $this->makeArticleWithPhoto(withPhoto: false);

        $this->get(route('articles.show', ['token' => $article->download_token]))
            ->assertOk()
            ->assertDontSee('From the garden');
    }

    public function test_the_ready_email_bakes_in_photo_urls(): void
    {
        $article = $this->makeArticleWithPhoto();
        $photo = $article->submission->photos->first();

        $mailable = new ArticleReady($article);

        // These URLs live in inboxes forever — the email embeds the thumb and
        // links through to the full photo (ADR 0002).
        $mailable->assertSeeInHtml(route('articles.photo', [
            'token' => $article->download_token,
            'photo' => $photo,
            'size' => 'thumb',
        ]));
        $mailable->assertSeeInHtml(route('articles.photo', [
            'token' => $article->download_token,
            'photo' => $photo,
        ]));
    }

    public function test_the_pdf_download_still_renders_with_photos(): void
    {
        $article = $this->makeArticleWithPhoto();

        $response = $this->get(route('articles.download', [
            'token' => $article->download_token,
            'format' => 'pdf',
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    private function makeArticleWithPhoto(bool $withPhoto = true): Article
    {
        $user = User::fromEmail('gardener@example.test');

        $submission = Submission::create([
            'user_id' => $user->id,
            'source' => Submission::SOURCE_RECORD,
            'audio_path' => 'audio/fake.webm',
            'status' => Submission::STATUS_READY,
        ]);

        $article = $submission->article()->create([
            'title' => 'The June Beds',
            'body_md' => 'The tomatoes finally set fruit.',
            'user_id' => $user->id,
            'published' => true,
        ]);

        if ($withPhoto) {
            $image = new Imagick;
            $image->newImage(64, 48, 'green');
            $image->setImageFormat('jpeg');
            $bytes = $image->getImageBlob();

            $disk = Storage::disk(config('pipeline.photos.disk'));
            $disk->put('photos/one.jpg', $bytes);
            $disk->put('photos/one_thumb.jpg', $bytes);

            $submission->photos()->create([
                'path' => 'photos/one.jpg',
                'thumb_path' => 'photos/one_thumb.jpg',
            ]);
        }

        return $article->fresh();
    }
}
