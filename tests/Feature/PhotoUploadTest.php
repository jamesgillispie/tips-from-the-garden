<?php

namespace Tests\Feature;

use App\Livewire\UploadForm;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Imagick;
use Livewire\Livewire;
use Tests\TestCase;

class PhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('pipeline.audio.disk'));
        Storage::fake(config('pipeline.photos.disk'));
    }

    public function test_photos_attached_to_a_recording_are_reencoded_and_banked(): void
    {
        Bus::fake();

        $user = User::fromEmail('gardener@example.test');

        Livewire::actingAs($user)
            ->test(UploadForm::class)
            ->set('mode', 'record')
            ->set('audio', $this->fakeWav())
            ->set('photos', [$this->fakeJpeg('bed-one.jpg'), $this->fakeJpeg('bed-two.jpg')])
            ->call('submit')
            ->assertHasNoErrors();

        $submission = Submission::firstOrFail();
        $this->assertSame(2, $submission->photos()->count());
        // 2 displays + 2 thumbs (audio shares the local disk, so scope to photos/)
        $this->assertCount(4, Storage::disk(config('pipeline.photos.disk'))->allFiles(config('pipeline.photos.path')));
        $this->assertSame('bed-one.jpg', $submission->photos()->first()->original_filename);
    }

    public function test_photos_also_attach_to_a_pasted_transcript(): void
    {
        Bus::fake();

        $user = User::fromEmail('gardener@example.test');

        Livewire::actingAs($user)
            ->test(UploadForm::class)
            ->set('mode', 'paste')
            ->set('transcript', str_repeat('The tomatoes finally set fruit after the cold snap. ', 3))
            ->set('photos', [$this->fakeJpeg()])
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(1, Submission::firstOrFail()->photos()->count());
    }

    public function test_a_file_that_is_not_a_photo_is_rejected(): void
    {
        $user = User::fromEmail('gardener@example.test');

        Livewire::actingAs($user)
            ->test(UploadForm::class)
            ->set('mode', 'audio')
            ->set('audio', $this->fakeWav())
            ->set('photos', [$this->fakeWav()])
            ->call('submit')
            ->assertHasErrors(['photos.0' => 'mimes']);

        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_more_photos_than_the_cap_are_rejected(): void
    {
        config(['pipeline.photos.max_per_submission' => 2]);

        $user = User::fromEmail('gardener@example.test');

        Livewire::actingAs($user)
            ->test(UploadForm::class)
            ->set('mode', 'audio')
            ->set('audio', $this->fakeWav())
            ->set('photos', [$this->fakeJpeg(), $this->fakeJpeg(), $this->fakeJpeg()])
            ->call('submit')
            ->assertHasErrors(['photos' => 'max']);
    }

    public function test_a_staged_photo_can_be_removed_before_submitting(): void
    {
        Bus::fake();

        $user = User::fromEmail('gardener@example.test');

        Livewire::actingAs($user)
            ->test(UploadForm::class)
            ->set('mode', 'audio')
            ->set('audio', $this->fakeWav())
            ->set('photos', [$this->fakeJpeg('keep.jpg'), $this->fakeJpeg('drop.jpg')])
            ->call('removePhoto', 1)
            ->call('submit')
            ->assertHasNoErrors();

        $submission = Submission::firstOrFail();
        $this->assertSame(['keep.jpg'], $submission->photos()->pluck('original_filename')->all());
    }

    private function fakeJpeg(string $name = 'photo.jpg'): UploadedFile
    {
        $image = new Imagick;
        $image->newImage(320, 240, 'green');
        $image->setImageFormat('jpeg');

        return UploadedFile::fake()->createWithContent($name, $image->getImageBlob());
    }

    /**
     * A minimal but genuinely valid 16kHz mono WAV, so the content-sniffing
     * `mimes` rule sees real audio.
     */
    private function fakeWav(): UploadedFile
    {
        $dataLen = 16000;

        $wav = 'RIFF'.pack('V', 36 + $dataLen).'WAVE'
            .'fmt '.pack('V', 16).pack('v', 1).pack('v', 1)
            .pack('V', 16000).pack('V', 32000).pack('v', 2).pack('v', 16)
            .'data'.pack('V', $dataLen).str_repeat("\x00", $dataLen);

        return UploadedFile::fake()->createWithContent('memo.wav', $wav);
    }
}
