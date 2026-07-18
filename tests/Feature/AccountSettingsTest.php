<?php

namespace Tests\Feature;

use App\Livewire\AccountSettings;
use App\Mail\EmailChangeConfirmation;
use App\Mail\EmailChangeNotice;
use App\Models\Article;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class AccountSettingsTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'open-sesame-9';

    public function test_the_account_page_requires_a_signed_in_gardener(): void
    {
        $this->get(route('account'))->assertRedirect(route('login'));
    }

    // ─────────────────────────────  DETAILS  ─────────────────────────────

    public function test_a_gardener_can_save_their_region_and_birth_year(): void
    {
        $user = $this->gardener();

        Livewire::actingAs($user)
            ->test(AccountSettings::class)
            ->set('name', 'Rose Gardener')
            ->set('region', '8b')
            ->set('birthYear', '1968')
            ->call('updateProfile')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Rose Gardener',
            'region' => '8b',
            'birth_year' => 1968,
        ]);
    }

    public function test_no_region_chosen_is_stored_as_null(): void
    {
        $user = $this->gardener();
        $user->forceFill(['region' => '8b'])->save();

        Livewire::actingAs($user)
            ->test(AccountSettings::class)
            ->set('name', 'Rose Gardener')
            ->set('region', '')
            ->set('birthYear', null)
            ->call('updateProfile')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'region' => null, 'birth_year' => null]);
    }

    public function test_an_unknown_zone_or_out_of_range_year_is_rejected(): void
    {
        $user = $this->gardener();

        Livewire::actingAs($user)
            ->test(AccountSettings::class)
            ->set('region', '99z')
            ->set('birthYear', '1700')
            ->call('updateProfile')
            ->assertHasErrors(['region', 'birthYear']);
    }

    // ───────────────────────────  EMAIL CHANGE  ──────────────────────────

    public function test_requesting_an_email_change_stashes_it_and_sends_both_emails(): void
    {
        Mail::fake();
        $user = $this->gardener('rose@example.test');

        Livewire::actingAs($user)
            ->test(AccountSettings::class)
            ->set('newEmail', 'NewRose@Example.test')
            ->set('emailPassword', self::PASSWORD)
            ->call('requestEmailChange')
            ->assertHasNoErrors();

        $fresh = $user->fresh();
        // The live login email is untouched until the link is opened…
        $this->assertSame('rose@example.test', $fresh->email);
        // …and the requested address is normalised and parked.
        $this->assertSame('newrose@example.test', $fresh->pending_email);

        Mail::assertSent(EmailChangeConfirmation::class, fn ($m) => $m->hasTo('newrose@example.test'));
        Mail::assertSent(EmailChangeNotice::class, fn ($m) => $m->hasTo('rose@example.test'));
    }

    public function test_the_wrong_password_blocks_an_email_change(): void
    {
        Mail::fake();
        $user = $this->gardener();

        Livewire::actingAs($user)
            ->test(AccountSettings::class)
            ->set('newEmail', 'new@example.test')
            ->set('emailPassword', 'not-my-password')
            ->call('requestEmailChange')
            ->assertHasErrors('emailPassword');

        $this->assertNull($user->fresh()->pending_email);
        Mail::assertNothingSent();
    }

    public function test_an_email_already_in_use_is_rejected(): void
    {
        $this->gardener('taken@example.test');
        $user = $this->gardener('rose@example.test');

        Livewire::actingAs($user)
            ->test(AccountSettings::class)
            ->set('newEmail', 'taken@example.test')
            ->set('emailPassword', self::PASSWORD)
            ->call('requestEmailChange')
            ->assertHasErrors('newEmail');

        $this->assertNull($user->fresh()->pending_email);
    }

    public function test_changing_to_your_own_email_is_rejected(): void
    {
        $user = $this->gardener('rose@example.test');

        Livewire::actingAs($user)
            ->test(AccountSettings::class)
            ->set('newEmail', 'rose@example.test')
            ->set('emailPassword', self::PASSWORD)
            ->call('requestEmailChange')
            ->assertHasErrors('newEmail');
    }

    public function test_confirming_from_the_link_switches_the_email(): void
    {
        $user = $this->gardener('rose@example.test');
        $user->forceFill(['pending_email' => 'new@example.test'])->save();

        $url = $this->confirmUrl($user, 'new@example.test');

        // A guest (link opened on another device) lands on the sign-in page.
        $this->get($url)->assertRedirect(route('login'));

        $fresh = $user->fresh();
        $this->assertSame('new@example.test', $fresh->email);
        $this->assertNull($fresh->pending_email);
    }

    public function test_a_confirmation_link_is_dead_once_the_pending_change_is_gone(): void
    {
        $user = $this->gardener('rose@example.test');
        $user->forceFill(['pending_email' => 'new@example.test'])->save();

        // Build a properly signed link, then cancel the change before it's used.
        $url = $this->confirmUrl($user, 'new@example.test');
        $user->forceFill(['pending_email' => null])->save();

        $this->get($url)->assertRedirect(route('login'));

        // The email never moved.
        $this->assertSame('rose@example.test', $user->fresh()->email);
    }

    public function test_a_tampered_confirmation_link_is_refused(): void
    {
        $user = $this->gardener('rose@example.test');
        $user->forceFill(['pending_email' => 'new@example.test'])->save();

        $this->get(route('account.email.confirm', ['user' => $user->id, 'hash' => sha1('new@example.test')]))
            ->assertForbidden();

        $this->assertSame('rose@example.test', $user->fresh()->email);
    }

    // ──────────────────────────  PASSWORD CHANGE  ─────────────────────────

    public function test_a_gardener_can_change_their_password(): void
    {
        $user = $this->gardener();

        Livewire::actingAs($user)
            ->test(AccountSettings::class)
            ->set('currentPassword', self::PASSWORD)
            ->set('password', 'a-fresh-passphrase')
            ->set('password_confirmation', 'a-fresh-passphrase')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('a-fresh-passphrase', $user->fresh()->password));
    }

    public function test_the_wrong_current_password_blocks_a_password_change(): void
    {
        $user = $this->gardener();

        Livewire::actingAs($user)
            ->test(AccountSettings::class)
            ->set('currentPassword', 'not-my-password')
            ->set('password', 'a-fresh-passphrase')
            ->set('password_confirmation', 'a-fresh-passphrase')
            ->call('updatePassword')
            ->assertHasErrors('currentPassword');

        $this->assertTrue(Hash::check(self::PASSWORD, $user->fresh()->password));
    }

    // ────────────────────────────  WIPE DATA  ────────────────────────────

    public function test_wiping_data_removes_content_but_keeps_the_account(): void
    {
        $user = $this->gardener();
        $this->articleFor($this->memoFor($user, 'The dahlias are blooming early.'));
        $user->writingSamples()->create(['source' => 'paste', 'body' => str_repeat('words ', 40), 'include_in_profile' => true]);
        $user->voiceProfile->update(['profile_text' => 'Warm and plainspoken.', 'sample_count' => 3]);

        Livewire::actingAs($user)
            ->test(AccountSettings::class)
            ->set('wipeConfirmation', 'wipe')
            ->call('wipeData')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseCount('submissions', 0);
        $this->assertDatabaseCount('articles', 0);
        $this->assertDatabaseCount('transcripts', 0);
        $this->assertDatabaseCount('writing_samples', 0);
        $this->assertDatabaseHas('voice_profiles', [
            'user_id' => $user->id,
            'profile_text' => null,
            'sample_count' => 0,
        ]);
    }

    public function test_wiping_data_deletes_photo_files_too(): void
    {
        Storage::fake(config('pipeline.photos.disk'));

        $user = $this->gardener();
        $memo = $this->memoFor($user, 'The dahlias are blooming early.');

        $disk = Storage::disk(config('pipeline.photos.disk'));
        $disk->put('photos/one.jpg', 'display-bytes');
        $disk->put('photos/one_thumb.jpg', 'thumb-bytes');
        $memo->photos()->create(['path' => 'photos/one.jpg', 'thumb_path' => 'photos/one_thumb.jpg']);

        Livewire::actingAs($user)
            ->test(AccountSettings::class)
            ->set('wipeConfirmation', 'wipe')
            ->call('wipeData')
            ->assertHasNoErrors();

        // Deleting the rows must take the stored objects with it — that's the
        // revocation mechanism for the proxied photo URLs (ADR 0002).
        $this->assertDatabaseCount('photos', 0);
        $disk->assertMissing(['photos/one.jpg', 'photos/one_thumb.jpg']);
    }

    public function test_wiping_needs_the_word_wipe(): void
    {
        $user = $this->gardener();
        $this->memoFor($user, 'Keep me, please.');

        Livewire::actingAs($user)
            ->test(AccountSettings::class)
            ->set('wipeConfirmation', 'yes')
            ->call('wipeData')
            ->assertHasErrors('wipeConfirmation');

        $this->assertDatabaseCount('submissions', 1);
    }

    // ──────────────────────────  DELETE ACCOUNT  ──────────────────────────

    public function test_deleting_the_account_removes_everything_and_signs_out(): void
    {
        $user = $this->gardener();
        $this->articleFor($this->memoFor($user, 'The dahlias are blooming early.'));

        Livewire::actingAs($user)
            ->test(AccountSettings::class)
            ->set('deletePassword', self::PASSWORD)
            ->call('deleteAccount')
            ->assertHasNoErrors()
            ->assertRedirect(route('login'));

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseCount('submissions', 0);
        $this->assertDatabaseCount('articles', 0);
        $this->assertDatabaseCount('transcripts', 0);
        $this->assertDatabaseCount('voice_profiles', 0);
        $this->assertGuest();
    }

    public function test_the_wrong_password_blocks_account_deletion(): void
    {
        $user = $this->gardener();

        Livewire::actingAs($user)
            ->test(AccountSettings::class)
            ->set('deletePassword', 'not-my-password')
            ->call('deleteAccount')
            ->assertHasErrors('deletePassword');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    // ───────────────────────────────  HELPERS  ───────────────────────────

    private function gardener(string $email = 'gardener@example.test'): User
    {
        $user = User::fromEmail($email);
        $user->forceFill(['password' => Hash::make(self::PASSWORD)])->save();

        return $user;
    }

    private function confirmUrl(User $user, string $pendingEmail): string
    {
        return URL::temporarySignedRoute(
            'account.email.confirm',
            now()->addMinutes(60),
            ['user' => $user->id, 'hash' => sha1($pendingEmail)],
        );
    }

    private function articleFor(Submission $memo): Article
    {
        return Article::create([
            'user_id' => $memo->user_id,
            'submission_id' => $memo->id,
            'title' => 'Early dahlias',
            'body_md' => 'They came up before the last frost.',
        ]);
    }

    private function memoFor(User $user, string $transcript): Submission
    {
        $memo = Submission::create([
            'user_id' => $user->id,
            'source' => Submission::SOURCE_RECORD,
            'status' => Submission::STATUS_READY,
        ]);

        $memo->transcript()->create([
            'raw_text' => $transcript,
            'transcriber' => 'whisper_cpp',
        ]);

        return $memo;
    }
}
