<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const AI_CHECK_IN_BROWSER = 'browser';

    public const AI_CHECK_IN_EMAIL = 'email';

    /** @var array<string, mixed> */
    public const DEFAULT_AI_PREFERENCES = [
        'transcription' => false,
        'article_writing' => false,
        'voice_learning' => false,
        'voice_learning_threshold' => 3,
        'included_samples' => ['transcript', 'paste'],
        'check_in_channel' => self::AI_CHECK_IN_EMAIL,
    ];

    protected $attributes = [
        'ai_opt_in' => false,
    ];

    protected $fillable = [
        'name',
        'region',
        'birth_year',
        'email',
        'password',
        'google_id',
        'ai_opt_in',
        'ai_opted_in_at',
        'last_ai_check_in_at',
        'ai_preferences',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_year' => 'integer',
            'ai_opt_in' => 'boolean',
            'ai_opted_in_at' => 'datetime',
            'last_ai_check_in_at' => 'datetime',
            'ai_preferences' => 'array',
        ];
    }

    public function voiceProfile(): HasOne
    {
        return $this->hasOne(VoiceProfile::class);
    }

    public function writingSamples(): HasMany
    {
        return $this->hasMany(WritingSample::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /**
     * Return a normalized preference set even for users created before the AI
     * controls existed. The master opt-in gates every model-backed stage, so
     * default-false preferences can never accidentally turn processing on.
     *
     * @return array<string, mixed>
     */
    public function aiPreferences(): array
    {
        $stored = is_array($this->ai_preferences) ? $this->ai_preferences : [];
        $preferences = array_replace(self::DEFAULT_AI_PREFERENCES, $stored);

        $preferences['transcription'] = (bool) $preferences['transcription'];
        $preferences['article_writing'] = (bool) $preferences['article_writing'];
        $preferences['voice_learning'] = (bool) $preferences['voice_learning'];
        $preferences['voice_learning_threshold'] = min(20, max(1, (int) $preferences['voice_learning_threshold']));
        $preferences['included_samples'] = array_values(array_intersect(
            is_array($preferences['included_samples']) ? $preferences['included_samples'] : [],
            ['transcript', 'paste'],
        ));
        $preferences['check_in_channel'] = in_array(
            $preferences['check_in_channel'],
            [self::AI_CHECK_IN_BROWSER, self::AI_CHECK_IN_EMAIL],
            true,
        ) ? $preferences['check_in_channel'] : self::AI_CHECK_IN_EMAIL;

        return $preferences;
    }

    public function canUseAi(): bool
    {
        return $this->usesAnyAi();
    }

    public function usesAnyAi(): bool
    {
        return $this->canTranscribe() || $this->canWriteArticles() || $this->canLearnVoice();
    }

    public function canTranscribe(): bool
    {
        return $this->ai_opt_in && $this->aiPreferences()['transcription'];
    }

    public function canWriteArticles(): bool
    {
        return $this->ai_opt_in && $this->aiPreferences()['article_writing'];
    }

    public function canLearnVoice(): bool
    {
        return $this->ai_opt_in && $this->aiPreferences()['voice_learning'];
    }

    public function aiVoiceLearningThreshold(): int
    {
        return $this->aiPreferences()['voice_learning_threshold'];
    }

    /** @return array<int, string> */
    public function aiIncludedSampleSources(): array
    {
        return $this->aiPreferences()['included_samples'];
    }

    public function aiCheckInChannel(): string
    {
        return $this->aiPreferences()['check_in_channel'];
    }

    public function aiCheckInDue(): bool
    {
        if (! $this->usesAnyAi()) {
            return false;
        }

        if ($this->last_ai_check_in_at === null) {
            return true;
        }

        return $this->last_ai_check_in_at->lte(
            now()->subDays((int) config('pipeline.ai.check_in_days', 30)),
        );
    }

    /** Existing users receive one explicit choice after the migration. */
    public function needsAiConsentDecision(): bool
    {
        return $this->ai_opted_in_at === null && $this->last_ai_check_in_at === null;
    }

    /**
     * Find or create a user from an email address. Registration adds a password
     * on top; the email and webhook intake doors leave it null until the
     * gardener claims the account with a password-reset link.
     */
    public static function fromEmail(string $email, ?string $name = null): self
    {
        $user = static::firstOrCreate(
            ['email' => strtolower(trim($email))],
            ['name' => $name],
        );

        $user->voiceProfile()->firstOrCreate([]);

        return $user;
    }

    /**
     * Look up an existing account by email — never creates one. The inbound
     * email door uses this so a memo from an unknown (or spoofed) sender can't
     * conjure a ghost account; it has to match an address that already signed up.
     */
    public static function findByEmail(string $email): ?self
    {
        return static::where('email', strtolower(trim($email)))->first();
    }
}
