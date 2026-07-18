<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Deleting a recording is also how its photos are revoked (ADR 0002): the
 * stored objects must go when the recording does, from every deletion path.
 */
class PhotoDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('pipeline.photos.disk'));
    }

    public function test_deleting_a_recording_from_the_desk_deletes_its_photo_files(): void
    {
        [$user, $submission] = $this->makeMemoWithPhoto();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->call('deleteMemo', $submission->id);

        $this->assertDatabaseCount('photos', 0);
        Storage::disk(config('pipeline.photos.disk'))
            ->assertMissing(['photos/one.jpg', 'photos/one_thumb.jpg']);
    }

    public function test_force_deleting_a_recording_deletes_its_photo_files(): void
    {
        [, $submission] = $this->makeMemoWithPhoto();

        // The Twill admin's hard delete goes through the model, not the desk —
        // without a hook, the FK cascade would drop the rows and orphan the files.
        $submission->forceDelete();

        $this->assertDatabaseCount('photos', 0);
        Storage::disk(config('pipeline.photos.disk'))
            ->assertMissing(['photos/one.jpg', 'photos/one_thumb.jpg']);
    }

    /** @return array{0: User, 1: Submission} */
    private function makeMemoWithPhoto(): array
    {
        $user = User::fromEmail('gardener@example.test');

        $submission = Submission::create([
            'user_id' => $user->id,
            'source' => Submission::SOURCE_RECORD,
            'audio_path' => 'audio/fake.webm',
            'status' => Submission::STATUS_READY,
        ]);

        $disk = Storage::disk(config('pipeline.photos.disk'));
        $disk->put('photos/one.jpg', 'display-bytes');
        $disk->put('photos/one_thumb.jpg', 'thumb-bytes');

        $submission->photos()->create([
            'path' => 'photos/one.jpg',
            'thumb_path' => 'photos/one_thumb.jpg',
        ]);

        return [$user, $submission];
    }
}
