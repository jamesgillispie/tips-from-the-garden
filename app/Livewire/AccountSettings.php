<?php

namespace App\Livewire;

use App\Actions\Fortify\PasswordValidationRules;
use App\Mail\EmailChangeConfirmation;
use App\Mail\EmailChangeNotice;
use App\Models\Transcript;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Everything a gardener can do to their own account: tidy their details, move
 * to a new email (confirmed from the new address), change their password, and —
 * in the danger zone — wipe their content or delete the account outright.
 *
 * Sensitive actions re-check the current password so a borrowed, still-signed-in
 * browser can't quietly take the account over.
 */
class AccountSettings extends Component
{
    use PasswordValidationRules;

    /** How long an email-change confirmation link stays good, in minutes. */
    public const EMAIL_LINK_MINUTES = 60;

    // Your details.
    public string $name = '';

    public string $region = '';

    public ?string $birthYear = null;

    // Email change.
    public string $newEmail = '';

    public string $emailPassword = '';

    // Password change.
    public string $currentPassword = '';

    public string $password = '';

    public string $password_confirmation = '';

    // Danger zone.
    public string $wipeConfirmation = '';

    public string $deletePassword = '';

    public function mount(): void
    {
        $user = auth()->user();

        $this->name = $user->name ?? '';
        $this->region = $user->region ?? '';
        $this->birthYear = $user->birth_year ? (string) $user->birth_year : null;
    }

    /** USDA hardiness zones 1a–13b — the values the region select offers. */
    public function zones(): array
    {
        $zones = [];

        foreach (range(1, 13) as $n) {
            $zones[] = $n.'a';
            $zones[] = $n.'b';
        }

        return $zones;
    }

    public function updateProfile(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            // '' rides along so "no zone chosen" passes; it's stored as null below.
            'region' => ['nullable', Rule::in([...$this->zones(), ''])],
            'birthYear' => ['nullable', 'integer', 'min:1900', 'max:'.now()->year],
        ]);

        auth()->user()->update([
            'name' => $validated['name'],
            'region' => $validated['region'] ?: null,
            'birth_year' => $validated['birthYear'] ?: null,
        ]);

        Flux::toast(text: 'Your details are saved.', variant: 'success');
    }

    /**
     * Stash the requested address as `pending_email` and send a confirmation
     * link there — the live email only flips once it's clicked. We also nudge
     * the *current* address so the real owner hears about it.
     */
    public function requestEmailChange(): void
    {
        $this->newEmail = strtolower(trim($this->newEmail));
        $user = auth()->user();

        if ($this->newEmail !== '' && $this->newEmail === strtolower(trim($user->email))) {
            $this->addError('newEmail', 'That’s already your email address.');

            return;
        }

        $this->validate([
            'newEmail' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'emailPassword' => ['required', 'current_password'],
        ], [
            'emailPassword.current_password' => 'That password doesn’t match our records.',
        ]);

        $user->forceFill(['pending_email' => $this->newEmail])->save();

        $this->sendConfirmationLink($user);
        Mail::to($user->email)->send(new EmailChangeNotice($user->pending_email));

        $this->reset('emailPassword');

        Flux::toast(text: 'Check your new inbox — we sent a confirmation link to '.$this->newEmail.'.', variant: 'success');
    }

    public function resendEmailChange(): void
    {
        $user = auth()->user();

        if (! $user->pending_email) {
            return;
        }

        $this->sendConfirmationLink($user);

        Flux::toast(text: 'Confirmation link sent again to '.$user->pending_email.'.', variant: 'success');
    }

    public function cancelEmailChange(): void
    {
        auth()->user()->forceFill(['pending_email' => null])->save();

        $this->reset('newEmail');

        Flux::toast(text: 'Email change cancelled.');
    }

    public function updatePassword(): void
    {
        $this->validate([
            'currentPassword' => ['required', 'current_password'],
            'password' => $this->passwordRules(),
        ], [
            'currentPassword.current_password' => 'That password doesn’t match our records.',
        ]);

        auth()->user()->update(['password' => $this->password]);

        $this->reset('currentPassword', 'password', 'password_confirmation');

        Flux::toast(text: 'Your password is updated.', variant: 'success');
    }

    /**
     * Clear out everything the gardener has made — recordings, journal entries,
     * writing samples — but keep the account itself and reset the learned voice.
     */
    public function wipeData(): void
    {
        $this->validate(['wipeConfirmation' => ['required', 'string']]);

        if (strtolower(trim($this->wipeConfirmation)) !== 'wipe') {
            $this->addError('wipeConfirmation', 'Type the word “wipe” to confirm.');

            return;
        }

        $user = auth()->user();

        DB::transaction(function () use ($user) {
            $this->purgeContent($user);
            $user->voiceProfile?->update(['profile_text' => null, 'sample_count' => 0]);
        });

        $this->reset('wipeConfirmation');
        Flux::modal('wipe-data')->close();

        Flux::toast(text: 'Your recordings, journal entries and writing samples are gone.', variant: 'success');
    }

    /**
     * Delete the account and everything attached to it, then sign out. The
     * voice profile cascades on the user row; the rest we clear explicitly so
     * nothing is left behind even where foreign keys aren't enforced.
     */
    public function deleteAccount(): void
    {
        $this->validate([
            'deletePassword' => ['required', 'current_password'],
        ], [
            'deletePassword.current_password' => 'That password doesn’t match our records.',
        ]);

        $user = auth()->user();

        DB::transaction(function () use ($user) {
            $this->purgeContent($user);
            $user->delete();   // the voice profile cascades on the user row
        });

        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        session()->flash('status', 'Your account and everything in it has been deleted.');

        $this->redirect(route('login'));
    }

    /**
     * Hard-delete every piece of content a gardener owns. Articles and
     * submissions are Twill (soft-deleting) models, so we force them out —
     * a plain delete would only set deleted_at and leave the rows (and the
     * transcript text) behind. Transcripts hang off submissions but carry no
     * user_id, so we clear them by their (possibly already-trashed) parents
     * before the submissions go. Writing samples are plain Eloquent.
     */
    protected function purgeContent(User $user): void
    {
        $submissionIds = $user->submissions()->withTrashed()->pluck('id');

        Transcript::whereIn('submission_id', $submissionIds)->delete();
        $user->articles()->withTrashed()->forceDelete();
        $user->submissions()->withTrashed()->forceDelete();
        $user->writingSamples()->delete();
    }

    protected function sendConfirmationLink(User $user): void
    {
        $url = URL::temporarySignedRoute(
            'account.email.confirm',
            now()->addMinutes(self::EMAIL_LINK_MINUTES),
            ['user' => $user->id, 'hash' => sha1($user->pending_email)],
        );

        Mail::to($user->pending_email)->send(new EmailChangeConfirmation($url, self::EMAIL_LINK_MINUTES));
    }

    public function render()
    {
        return view('livewire.account-settings', [
            'pendingEmail' => auth()->user()->pending_email,
        ])->layout('components.layouts.app', [
            'title' => 'Account settings — '.config('app.name'),
        ]);
    }
}
