<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;

class AiConsentService
{
    /**
     * Apply the broad AI choice from the privacy banner. Turning it on enables
     * all three stages; gardeners can then tune them individually in Account
     * Settings. Turning it off is an immediate stop for every model-backed job.
     */
    public function applyBroadChoice(User $user, bool $enabled, bool $analyticsEnabled): User
    {
        $preferences = $user->aiPreferences();
        $preferences['transcription'] = $enabled;
        $preferences['article_writing'] = $enabled;
        $preferences['voice_learning'] = $enabled;
        $preferences['check_in_channel'] = $this->channel($analyticsEnabled);

        $attributes = [
            'ai_opt_in' => $enabled,
            'last_ai_check_in_at' => now(),
            'ai_preferences' => $preferences,
        ];

        if ($enabled && $user->ai_opted_in_at === null) {
            $attributes['ai_opted_in_at'] = now();
        }

        $user->forceFill($attributes)->save();

        return $user->refresh();
    }

    /**
     * Save the granular account controls. The master switch gates every stage,
     * while the stage values remain available if the gardener turns it back on.
     *
     * @param  array<string, mixed>  $preferences
     */
    public function updatePreferences(User $user, bool $optIn, array $preferences): User
    {
        $normalized = $user->aiPreferences();
        $normalized['transcription'] = (bool) ($preferences['transcription'] ?? false);
        $normalized['article_writing'] = (bool) ($preferences['article_writing'] ?? false);
        $normalized['voice_learning'] = (bool) ($preferences['voice_learning'] ?? false);
        $normalized['voice_learning_threshold'] = min(
            20,
            max(1, (int) ($preferences['voice_learning_threshold'] ?? 3)),
        );
        $normalized['included_samples'] = array_values(array_intersect(
            is_array($preferences['included_samples'] ?? null) ? $preferences['included_samples'] : [],
            ['transcript', 'paste'],
        ));

        // A master opt-in with every stage disabled is not meaningful and
        // should never keep the account in the monthly reminder cohort.
        $optIn = $optIn && (
            $normalized['transcription']
            || $normalized['article_writing']
            || $normalized['voice_learning']
        );

        $attributes = [
            'ai_opt_in' => $optIn,
            'last_ai_check_in_at' => now(),
            'ai_preferences' => $normalized,
        ];

        if ($optIn && $user->ai_opted_in_at === null) {
            $attributes['ai_opted_in_at'] = now();
        }

        $user->forceFill($attributes)->save();

        return $user->refresh();
    }

    public function reaffirm(User $user, bool $analyticsEnabled): User
    {
        $preferences = $user->aiPreferences();
        $preferences['check_in_channel'] = $this->channel($analyticsEnabled);

        $user->forceFill([
            'last_ai_check_in_at' => now(),
            'ai_preferences' => $preferences,
        ])->save();

        return $user->refresh();
    }

    public function updateCheckInChannel(User $user, bool $analyticsEnabled): User
    {
        $preferences = $user->aiPreferences();
        $channel = $this->channel($analyticsEnabled);

        if ($preferences['check_in_channel'] === $channel) {
            return $user;
        }

        $preferences['check_in_channel'] = $channel;
        $user->forceFill(['ai_preferences' => $preferences])->save();

        return $user->refresh();
    }

    /**
     * Sync one explicit browser interaction. An analytics-only change merely
     * selects the future check-in channel; it never broadens AI permissions.
     */
    public function syncBrowserChoice(
        User $user,
        bool $aiEnabled,
        bool $analyticsEnabled,
        bool $aiChanged,
        bool $reaffirmed,
    ): User {
        if ($aiChanged) {
            return $this->applyBroadChoice($user, $aiEnabled, $analyticsEnabled);
        }

        if ($reaffirmed) {
            return $this->reaffirm($user, $analyticsEnabled);
        }

        return $this->updateCheckInChannel($user, $analyticsEnabled);
    }

    /**
     * Read vanilla-cookieconsent's first-party `cc_cookie`. It is explicitly
     * exempted from Laravel cookie encryption because JavaScript creates it.
     *
     * @return array{recorded: bool, ai: bool, analytics: bool}
     */
    public function choiceFromCookie(Request $request): array
    {
        $raw = $request->cookie('cc_cookie');

        if (! is_string($raw) || $raw === '') {
            return ['recorded' => false, 'ai' => false, 'analytics' => false];
        }

        $data = json_decode($raw, true);

        if (! is_array($data)) {
            $data = json_decode(rawurldecode($raw), true);
        }

        $categories = is_array($data['categories'] ?? null) ? $data['categories'] : [];

        return [
            'recorded' => is_array($data) && isset($data['consentId']),
            'ai' => in_array('ai', $categories, true),
            'analytics' => in_array('analytics', $categories, true),
        ];
    }

    public function channel(bool $analyticsEnabled): string
    {
        return $analyticsEnabled
            ? User::AI_CHECK_IN_BROWSER
            : User::AI_CHECK_IN_EMAIL;
    }
}
