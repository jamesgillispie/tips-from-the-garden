<?php

namespace Tests\Feature;

use App\Mail\AiProcessingStopped;
use App\Mail\MonthlyAiCheckIn;
use App\Models\User;
use App\Services\AiConsentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AiCheckInTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_emails_only_due_users_who_declined_optional_cookies(): void
    {
        Mail::fake();

        $dueByEmail = $this->aiUser('email@example.test', analyticsEnabled: false);
        $dueByEmail->forceFill(['last_ai_check_in_at' => now()->subDays(31)])->save();

        $dueInBrowser = $this->aiUser('browser@example.test', analyticsEnabled: true);
        $dueInBrowser->forceFill(['last_ai_check_in_at' => now()->subDays(31)])->save();

        $notDue = $this->aiUser('recent@example.test', analyticsEnabled: false);

        $this->artisan('ai:send-check-ins')
            ->expectsOutput('Queued 1 AI check-in email(s).')
            ->assertSuccessful();

        Mail::assertQueued(MonthlyAiCheckIn::class, fn ($mail) => $mail->hasTo($dueByEmail->email));
        Mail::assertNotQueued(MonthlyAiCheckIn::class, fn ($mail) => $mail->hasTo($dueInBrowser->email));
        Mail::assertNotQueued(MonthlyAiCheckIn::class, fn ($mail) => $mail->hasTo($notDue->email));
    }

    public function test_signed_check_in_requires_a_confirmation_post(): void
    {
        $user = $this->aiUser('gardener@example.test', analyticsEnabled: false);
        $user->forceFill(['last_ai_check_in_at' => now()->subDays(31)])->save();
        $before = $user->last_ai_check_in_at;

        $url = $this->signedUrl($user, 'confirm');

        $this->get($url)
            ->assertOk()
            ->assertSee('Keep your current AI choices?');

        $this->assertTrue($user->fresh()->last_ai_check_in_at->equalTo($before));

        $this->post($url)
            ->assertOk()
            ->assertSee('Thanks for checking in');

        $this->assertTrue($user->fresh()->last_ai_check_in_at->greaterThan($before));
        $this->assertTrue($user->fresh()->canUseAi());
    }

    public function test_unsigned_check_in_links_are_rejected(): void
    {
        $user = $this->aiUser('gardener@example.test', analyticsEnabled: false);

        $this->get(route('ai-check-in.show', [
            'user' => $user,
            'action' => 'disable',
        ]))->assertForbidden();
    }

    public function test_confirmed_stop_action_disables_every_ai_stage(): void
    {
        Mail::fake();
        $user = $this->aiUser('gardener@example.test', analyticsEnabled: false);

        $this->post($this->signedUrl($user, 'disable'))
            ->assertOk()
            ->assertSee('AI processing is off');

        $fresh = $user->fresh();
        $this->assertFalse($fresh->ai_opt_in);
        $this->assertFalse($fresh->canTranscribe());
        $this->assertFalse($fresh->canWriteArticles());
        $this->assertFalse($fresh->canLearnVoice());
        Mail::assertQueued(AiProcessingStopped::class, fn ($mail) => $mail->hasTo($user->email));
    }

    public function test_check_in_email_explains_current_settings_and_has_signed_actions(): void
    {
        $user = $this->aiUser('gardener@example.test', analyticsEnabled: false);
        $mail = new MonthlyAiCheckIn($user);

        $mail->assertSeeInHtml('Transcription:');
        $mail->assertSeeInHtml('Journal entry writing:');
        $mail->assertSeeInHtml('Stop all AI processing');
        $mail->assertSeeInText('email security scanner');
    }

    private function aiUser(string $email, bool $analyticsEnabled): User
    {
        $user = User::fromEmail($email);

        return app(AiConsentService::class)
            ->applyBroadChoice($user, enabled: true, analyticsEnabled: $analyticsEnabled);
    }

    private function signedUrl(User $user, string $action): string
    {
        return URL::temporarySignedRoute(
            'ai-check-in.show',
            now()->addHour(),
            ['user' => $user->id, 'action' => $action],
        );
    }
}
