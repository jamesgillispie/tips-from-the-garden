<?php

namespace Tests\Feature;

use App\Jobs\DeliverArticle;
use App\Jobs\TranscribeAudio;
use App\Jobs\WriteArticle;
use App\Mail\NoAccountFound;
use App\Mail\NoAudioFound;
use App\Models\User;
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

        // The email door only files memos for an address that already has an
        // account — so the gardener has to exist before they can email one in.
        User::fromEmail('gardener@example.test');

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

    public function test_inbound_email_from_an_unknown_address_creates_no_account(): void
    {
        Bus::fake();
        Mail::fake();
        Storage::fake(config('pipeline.audio.disk'));

        $payload = [
            'FromFull' => ['Email' => 'stranger@example.test', 'Name' => 'A Stranger'],
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
            ->assertJsonPath('status', 'no-account');

        // No ghost account, no memo, no pipeline — just a nudge to sign up.
        $this->assertDatabaseCount('submissions', 0);
        $this->assertDatabaseMissing('users', ['email' => 'stranger@example.test']);
        Bus::assertNothingDispatched();

        Mail::assertQueued(NoAccountFound::class, fn ($mail) => $mail->hasTo('stranger@example.test'));
    }

    public function test_unknown_automated_sender_with_audio_gets_no_reply(): void
    {
        Mail::fake();
        Storage::fake(config('pipeline.audio.disk'));

        $payload = [
            'FromFull' => ['Email' => 'mailer-daemon@example.test'],
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
            ->assertJsonPath('status', 'no-account');

        // Never reply to an automated sender, even to say "make an account".
        Mail::assertNotQueued(NoAccountFound::class);
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
