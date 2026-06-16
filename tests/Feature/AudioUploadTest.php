<?php

namespace Tests\Feature;

use App\Jobs\DeliverArticle;
use App\Jobs\TranscribeAudio;
use App\Jobs\WriteArticle;
use App\Livewire\UploadForm;
use App\Mail\MagicLinkMail;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AudioUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_in_browser_recording_is_accepted_and_chains_the_full_pipeline(): void
    {
        Bus::fake();
        Mail::fake();
        Storage::fake(config('pipeline.audio.disk'));

        $user = User::fromEmail('gardener@example.test');

        Livewire::actingAs($user)
            ->test(UploadForm::class)
            ->set('mode', 'record')
            ->set('audio', $this->fakeWav())
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', ['tab' => 'recordings']));

        $this->assertDatabaseHas('submissions', [
            'user_id' => $user->id,
            'source' => Submission::SOURCE_RECORD,
        ]);

        // Recording lives behind the login wall now — no sign-in link is sent.
        Mail::assertNotSent(MagicLinkMail::class);

        Bus::assertChained([
            TranscribeAudio::class,
            WriteArticle::class,
            DeliverArticle::class,
        ]);
    }

    public function test_an_uploaded_file_keeps_the_upload_source(): void
    {
        Bus::fake();
        Storage::fake(config('pipeline.audio.disk'));

        $user = User::fromEmail('gardener@example.test');

        Livewire::actingAs($user)
            ->test(UploadForm::class)
            ->set('mode', 'audio')
            ->set('audio', $this->fakeWav())
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', ['tab' => 'recordings']));

        $this->assertDatabaseHas('submissions', [
            'user_id' => $user->id,
            'source' => Submission::SOURCE_UPLOAD,
        ]);
    }

    public function test_submitting_record_mode_without_a_recording_is_rejected(): void
    {
        $user = User::fromEmail('gardener@example.test');

        Livewire::actingAs($user)
            ->test(UploadForm::class)
            ->set('mode', 'record')
            ->call('submit')
            ->assertHasErrors(['audio' => 'required']);

        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_browser_recording_formats_are_allowed(): void
    {
        $this->assertContains('webm', config('pipeline.audio.mimes'));
        $this->assertContains('weba', config('pipeline.audio.mimes'));
    }

    public function test_the_record_page_advertises_the_configured_inbound_address(): void
    {
        config(['pipeline.inbound.address' => 'memos@manorhousegardens.org']);

        $user = User::fromEmail('gardener@example.test');

        Livewire::actingAs($user)
            ->test(UploadForm::class)
            ->assertSee('memos@manorhousegardens.org');
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
