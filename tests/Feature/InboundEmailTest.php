<?php

namespace Tests\Feature;

use App\Jobs\DeliverArticle;
use App\Jobs\TranscribeAudio;
use App\Jobs\WriteArticle;
use App\Mail\MemoNotAuthenticated;
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

    /**
     * The headers Postmark adds when a message passed aligned DKIM — the
     * shape every legitimate consumer-mail sender (Gmail, iCloud, …) produces.
     */
    protected function authenticatedHeaders(): array
    {
        return [
            ['Name' => 'X-Spam-Tests', 'Value' => 'DKIM_SIGNED,DKIM_VALID,DKIM_VALID_AU,SPF_PASS'],
        ];
    }

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
            'Headers' => $this->authenticatedHeaders(),
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
            'Headers' => $this->authenticatedHeaders(),
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
            'Headers' => $this->authenticatedHeaders(),
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
            'Headers' => $this->authenticatedHeaders(),
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
            'Headers' => $this->authenticatedHeaders(),
            'Attachments' => [],
        ];

        $this->postJson(route('webhooks.postmark'), $payload)
            ->assertOk()
            ->assertJsonPath('status', 'no-audio');

        Mail::assertNotQueued(NoAudioFound::class);
    }

    public function test_an_unauthenticated_memo_for_a_real_account_is_dropped_and_the_account_holder_notified(): void
    {
        Bus::fake();
        Mail::fake();
        Storage::fake(config('pipeline.audio.disk'));

        User::fromEmail('victim@example.test');

        // A forged From with no authentication evidence at all — the memo must
        // never reach the victim's desk, but they get told someone tried.
        $payload = [
            'FromFull' => ['Email' => 'victim@example.test', 'Name' => 'Not Really Them'],
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
            ->assertJsonPath('status', 'unauthenticated');

        $this->assertDatabaseCount('submissions', 0);
        Bus::assertNothingDispatched();

        Mail::assertQueued(MemoNotAuthenticated::class, fn ($mail) => $mail->hasTo('victim@example.test'));
    }

    public function test_repeated_unauthenticated_memos_notify_the_account_holder_at_most_once_a_day(): void
    {
        Mail::fake();
        Storage::fake(config('pipeline.audio.disk'));

        User::fromEmail('victim@example.test');

        $payload = [
            'FromFull' => ['Email' => 'victim@example.test'],
            'Attachments' => [],
        ];

        $this->postJson(route('webhooks.postmark'), $payload)->assertOk();
        $this->postJson(route('webhooks.postmark'), $payload)->assertOk();
        $this->postJson(route('webhooks.postmark'), $payload)->assertOk();

        // An attacker hammering forged memos must not become a spam cannon
        // aimed at the very person being spoofed.
        Mail::assertQueuedCount(1);
        Mail::assertQueued(MemoNotAuthenticated::class, fn ($mail) => $mail->hasTo('victim@example.test'));
    }

    public function test_the_not_authenticated_notice_also_respects_the_automated_sender_guard(): void
    {
        Mail::fake();
        Storage::fake(config('pipeline.audio.disk'));

        // An account whose address looks automated must never be replied to,
        // even with the spoofing heads-up — loop protection beats courtesy.
        User::fromEmail('no-reply@example.test');

        $payload = [
            'FromFull' => ['Email' => 'no-reply@example.test'],
            'Attachments' => [],
        ];

        $this->postJson(route('webhooks.postmark'), $payload)
            ->assertOk()
            ->assertJsonPath('status', 'unauthenticated');

        Mail::assertNothingQueued();
    }

    public function test_an_unauthenticated_memo_from_an_unknown_address_is_dropped_silently(): void
    {
        Mail::fake();
        Storage::fake(config('pipeline.audio.disk'));

        // No account, no authentication: replying here would let anyone use
        // the app to bounce mail at arbitrary third parties.
        $payload = [
            'FromFull' => ['Email' => 'nobody@example.test'],
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
            ->assertJsonPath('status', 'unauthenticated');

        $this->assertDatabaseCount('submissions', 0);
        Mail::assertNothingQueued();
    }

    public function test_bad_token_is_rejected_when_configured(): void
    {
        config(['services.postmark.inbound_token' => 'secret-token']);

        $this->postJson(route('webhooks.postmark').'?token=wrong', [])
            ->assertForbidden();
    }
}
