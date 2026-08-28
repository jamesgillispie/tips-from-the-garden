<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Services\AiConsentService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(
        protected AiConsentService $aiConsent,
    ) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        // Normalise first so the unique check matches how fromEmail() stores the
        // address (lowercased, trimmed) — otherwise "Rose@x" could slip past the
        // row for "rose@x" and overwrite an existing account's password.
        $input['email'] = strtolower(trim($input['email'] ?? ''));

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => $this->passwordRules(),
            'consent_recorded' => ['sometimes', 'boolean'],
            'ai_consent' => ['sometimes', 'boolean'],
            'analytics_consent' => ['sometimes', 'boolean'],
        ])->validate();

        // fromEmail() is the one place that guarantees a user has a voice
        // profile; registration just sets a password on top of it.
        $user = User::fromEmail($input['email'], $input['name']);

        $user->forceFill(['password' => Hash::make($input['password'])])->save();

        if (filter_var($input['consent_recorded'] ?? false, FILTER_VALIDATE_BOOL)) {
            $this->aiConsent->applyBroadChoice(
                user: $user,
                enabled: filter_var($input['ai_consent'] ?? false, FILTER_VALIDATE_BOOL),
                analyticsEnabled: filter_var($input['analytics_consent'] ?? false, FILTER_VALIDATE_BOOL),
            );
        }

        return $user;
    }
}
