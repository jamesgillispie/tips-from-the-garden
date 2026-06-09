<?php

namespace Tests\Feature;

use App\Jobs\DeliverArticle;
use App\Jobs\TranscribeAudio;
use App\Jobs\WriteArticle;
use App\Mail\NoAudioFound;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InboundEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbound_email_with_audio_creates_a_submission(): void
    {
        Bus::fake();
        Mail::fake();
        Storage::fake(config('pipeline.audio.disk'));

        $payload = [
            'FromFull' => ['Email' => 'gardener@example.test', 'Name' => 'Pat Gardener'],
            'Attachments' => [
                [
                    'Name' => 'memo.m4a',
                    'ContentType' => 'audio/mp4',
                    'Content' => base64_encode('not-really-audio'),
                ],
            ],
        ];

        $this->postJson(route('webhooks.postmark'), $payload)
            ->assertOk()
            ->assertJsonPath('status', 'queued');

        $this->assertDatabaseCount('submissions', 1);
        $this->assertDatabaseHas('users', ['email' => 'gardener@example.test']);

        Bus::assertChained([
            TranscribeAudio::class,
            WriteArticle::class,
            DeliverArticle::class,
        ]);
    }

    public function test_inbound_email_without_audio_gets_a_helpful_reply(): void
    {
        Mail::fake();

        $payload = [
            'FromFull' => ['Email' => 'gardener@example.test'],
            'Attachments' => [],
        ];

        $this->postJson(route('webhooks.postmark'), $payload)
            ->assertOk()
            ->assertJsonPath('status', 'no-audio');

        $this->assertDatabaseCount('submissions', 0);

        Mail::assertQueued(NoAudioFound::class, fn ($mail) => $mail->hasTo('gardener@example.test'));
    }

    public function test_automated_senders_without_audio_get_no_reply(): void
    {
        Mail::fake();

        $payload = [
            'FromFull' => ['Email' => 'no-reply@example.test'],
            'Attachments' => [],
        ];

        $this->postJson(route('webhooks.postmark'), $payload)
            ->assertOk()
            ->assertJsonPath('status', 'no-audio');

        Mail::assertNotQueued(NoAudioFound::class);
    }

    public function test_bad_token_is_rejected_when_configured(): void
    {
        config(['services.postmark.inbound_token' => 'secret-token']);

        $this->postJson(route('webhooks.postmark').'?token=wrong', [])
            ->assertForbidden();
    }
}
