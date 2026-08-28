<?php

namespace Tests\Feature;

use App\Jobs\DeliverArticle;
use App\Jobs\TranscribeAudio;
use App\Jobs\UpdateVoiceProfile;
use App\Jobs\WriteArticle;
use App\Livewire\AccountSettings;
use App\Livewire\UploadForm;
use App\Mail\TranscriptReady;
use App\Models\Submission;
use App\Models\User;
use App\Services\AiConsentService;
use App\Services\SubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class AiConsentTest extends TestCase
{
    use RefreshDatabase;

    private const NOTES = 'The tomatoes finally set fruit after that cold snap, '
        .'and the basil next to them needs pinching back before it gets leggy.';

    public function test_new_and_existing_accounts_default_to_no_ai_processing(): void
    {
        $user = User::fromEmail('gardener@example.test');

        $this->assertFalse($user->ai_opt_in);
        $this->assertFalse($user->canTranscribe());
        $this->assertFalse($user->canWriteArticles());
        $this->assertFalse($user->canLearnVoice());
        $this->assertTrue($user->needsAiConsentDecision());
    }

    public function test_the_privacy_banner_choice_syncs_to_the_account(): void
    {
        $user = User::fromEmail('gardener@example.test');

        $this->actingAs($user)->postJson(route('ai-consent.sync'), [
            'ai_enabled' => true,
            'analytics_enabled' => true,
            'ai_changed' => true,
            'reaffirmed' => true,
        ])->assertOk()
            ->assertJsonPath('ai_enabled', true)
            ->assertJsonPath('check_in_channel', User::AI_CHECK_IN_BROWSER);

        $fresh = $user->fresh();
        $this->assertTrue($fresh->canTranscribe());
        $this->assertTrue($fresh->canWriteArticles());
        $this->assertTrue($fresh->canLearnVoice());
        $this->assertNotNull($fresh->ai_opted_in_at);
        $this->assertNotNull($fresh->last_ai_check_in_at);
    }

    public function test_an_analytics_only_change_cannot_expand_ai_permission(): void
    {
        $user = User::fromEmail('gardener@example.test');

        $this->actingAs($user)->postJson(route('ai-consent.sync'), [
            'ai_enabled' => true,
            'analytics_enabled' => true,
            'ai_changed' => false,
            'reaffirmed' => false,
        ])->assertOk();

        $this->assertFalse($user->fresh()->canUseAi());
        $this->assertSame(User::AI_CHECK_IN_BROWSER, $user->fresh()->aiCheckInChannel());
    }

    public function test_granular_account_settings_take_effect_immediately(): void
    {
        $user = User::fromEmail('gardener@example.test');

        Livewire::actingAs($user)
            ->test(AccountSettings::class)
            ->set('aiOptIn', true)
            ->set('aiTranscription', true)
            ->set('aiArticleWriting', false)
            ->set('aiVoiceLearning', false)
            ->call('updateAiSettings')
            ->assertHasNoErrors();

        $fresh = $user->fresh();
        $this->assertTrue($fresh->canTranscribe());
        $this->assertFalse($fresh->canWriteArticles());
        $this->assertFalse($fresh->canLearnVoice());
    }

    public function test_enabled_account_renders_the_granular_privacy_controls(): void
    {
        $user = User::fromEmail('gardener@example.test');
        app(AiConsentService::class)->applyBroadChoice($user, true, false);

        $this->actingAs($user)->get(route('account'))
            ->assertOk()
            ->assertSee('privacy settings')
            ->assertSee('Refresh voice after')
            ->assertSee('Samples AI may learn from');
    }

    public function test_recordings_are_rejected_before_upload_when_transcription_is_off(): void
    {
        $user = User::fromEmail('gardener@example.test');

        Livewire::actingAs($user)
            ->test(UploadForm::class)
            ->set('mode', 'record')
            ->call('submit')
            ->assertHasErrors('ai');

        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_pasted_notes_are_saved_and_delivered_without_ai(): void
    {
        Mail::fake();
        User::fromEmail('gardener@example.test');

        $submission = app(SubmissionService::class)
            ->fromTranscript(self::NOTES, 'gardener@example.test')
            ->fresh();

        $this->assertTrue($submission->isReady());
        $this->assertFalse($submission->ai_used);
        $this->assertNull($submission->article);
        $this->assertSame(self::NOTES, $submission->transcript->raw_text);

        DeliverArticle::dispatchSync($submission); // retry must not send twice
        Mail::assertSent(TranscriptReady::class, 1);
    }

    public function test_transcription_only_pipeline_skips_the_writer(): void
    {
        Bus::fake();
        $user = User::fromEmail('gardener@example.test');
        app(AiConsentService::class)->updatePreferences($user, true, [
            'transcription' => true,
            'article_writing' => false,
            'voice_learning' => false,
            'voice_learning_threshold' => 3,
            'included_samples' => ['transcript'],
        ]);

        $submission = Submission::create([
            'user_id' => $user->id,
            'source' => Submission::SOURCE_UPLOAD,
            'audio_path' => 'audio/fake.m4a',
        ]);

        app(SubmissionService::class)->dispatchPipeline($submission);

        Bus::assertChained([
            TranscribeAudio::class,
            DeliverArticle::class,
        ]);
        Bus::assertNotDispatched(WriteArticle::class);
    }

    public function test_voice_profile_job_rechecks_consent_before_banking_a_sample(): void
    {
        $user = User::fromEmail('gardener@example.test');
        $submission = Submission::create([
            'user_id' => $user->id,
            'source' => Submission::SOURCE_PASTE,
            'status' => Submission::STATUS_READY,
        ]);
        $submission->transcript()->create([
            'raw_text' => self::NOTES,
            'transcriber' => 'paste',
        ]);

        UpdateVoiceProfile::dispatchSync($submission);

        $this->assertDatabaseCount('writing_samples', 0);
        $this->assertFalse($submission->fresh()->ai_used);
    }
}
