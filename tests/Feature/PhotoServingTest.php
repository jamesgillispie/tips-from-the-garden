<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Photo;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoServingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('pipeline.photos.disk'));
    }

    public function test_a_photo_is_served_with_the_entry_token_and_long_cache_headers(): void
    {
        [$article, $photo] = $this->makeDeliveredPhoto();

        $response = $this->get(route('articles.photo', [
            'token' => $article->download_token,
            'photo' => $photo,
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/jpeg');
        $this->assertStringContainsString('immutable', (string) $response->headers->get('Cache-Control'));
        $this->assertSame('display-bytes', $response->streamedContent());
    }

    public function test_the_thumb_variant_serves_the_thumbnail(): void
    {
        [$article, $photo] = $this->makeDeliveredPhoto();

        $response = $this->get(route('articles.photo', [
            'token' => $article->download_token,
            'photo' => $photo,
            'size' => 'thumb',
        ]));

        $response->assertOk();
        $this->assertSame('thumb-bytes', $response->streamedContent());
    }

    public function test_a_wrong_token_cannot_reach_the_photo(): void
    {
        [, $photo] = $this->makeDeliveredPhoto();

        $this->get(route('articles.photo', [
            'token' => str_repeat('x', 40),
            'photo' => $photo,
        ]))->assertNotFound();
    }

    public function test_a_valid_token_cannot_reach_another_recordings_photo(): void
    {
        [, $photo] = $this->makeDeliveredPhoto('first@example.test');
        [$otherArticle] = $this->makeDeliveredPhoto('second@example.test');

        $this->get(route('articles.photo', [
            'token' => $otherArticle->download_token,
            'photo' => $photo,
        ]))->assertNotFound();
    }

    /** @return array{0: Article, 1: Photo} */
    private function makeDeliveredPhoto(string $email = 'gardener@example.test'): array
    {
        $user = User::fromEmail($email);

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

        $disk = Storage::disk(config('pipeline.photos.disk'));
        $disk->put('photos/one.jpg', 'display-bytes');
        $disk->put('photos/one_thumb.jpg', 'thumb-bytes');

        $photo = $submission->photos()->create([
            'path' => 'photos/one.jpg',
            'thumb_path' => 'photos/one_thumb.jpg',
        ]);

        return [$article, $photo];
    }
}
