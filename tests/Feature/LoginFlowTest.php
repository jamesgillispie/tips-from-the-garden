<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LoginFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_the_record_page_to_login(): void
    {
        $this->get(route('home'))->assertRedirect(route('login'));
    }

    public function test_the_login_landing_shows_the_password_form_and_turnstile(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Sign in')
            ->assertSee('name="password"', false)
            ->assertSee('cf-turnstile', false)
            ->assertSee(route('register'))
            ->assertSee(route('password.request'));
    }

    public function test_signed_in_visitors_skip_the_login_landing(): void
    {
        $user = User::fromEmail('rose@example.test');

        $this->actingAs($user)->get(route('login'))->assertRedirect(route('home'));
    }

    public function test_a_gardener_can_register_an_account(): void
    {
        $this->post(route('register'), [
            'name' => 'Rose',
            'email' => 'Rose@Example.test',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticated();

        // Email is normalised, the voice-profile invariant holds, and the
        // password is hashed.
        $user = User::where('email', 'rose@example.test')->sole();
        $this->assertNotNull($user->voiceProfile);
        $this->assertTrue(Hash::check('correct-horse-battery', $user->password));
    }

    public function test_registration_rejects_a_duplicate_email_case_insensitively(): void
    {
        User::fromEmail('rose@example.test');

        $this->post(route('register'), [
            'name' => 'Imposter',
            'email' => 'ROSE@example.test',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_a_gardener_can_sign_in_with_the_right_password(): void
    {
        $user = $this->gardenerWithPassword('rose@example.test', 'open-sesame-9');

        $this->post(route('login'), [
            'email' => 'rose@example.test',
            'password' => 'open-sesame-9',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_the_wrong_password_is_turned_away(): void
    {
        $this->gardenerWithPassword('rose@example.test', 'open-sesame-9');

        $this->post(route('login'), [
            'email' => 'rose@example.test',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_a_signed_in_gardener_can_sign_out(): void
    {
        $user = User::fromEmail('rose@example.test');

        $this->actingAs($user)->post(route('logout'))->assertRedirect();

        $this->assertGuest();
    }

    public function test_a_failed_turnstile_blocks_sign_in(): void
    {
        config(['services.turnstile.secret_key' => 'test-secret']);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false])]);

        $this->gardenerWithPassword('rose@example.test', 'open-sesame-9');

        $this->post(route('login'), [
            'email' => 'rose@example.test',
            'password' => 'open-sesame-9',
            'cf-turnstile-response' => 'bad-token',
        ])->assertSessionHasErrors('turnstile');

        $this->assertGuest();
    }

    public function test_a_valid_turnstile_lets_sign_in_through(): void
    {
        config(['services.turnstile.secret_key' => 'test-secret']);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);

        $user = $this->gardenerWithPassword('rose@example.test', 'open-sesame-9');

        $this->post(route('login'), [
            'email' => 'rose@example.test',
            'password' => 'open-sesame-9',
            'cf-turnstile-response' => 'a-token',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_forgot_password_emails_a_reset_link(): void
    {
        Notification::fake();
        $user = User::fromEmail('rose@example.test');

        $this->post(route('password.email'), [
            'email' => 'rose@example.test',
        ])->assertSessionHasNoErrors();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    private function gardenerWithPassword(string $email, string $password): User
    {
        $user = User::fromEmail($email);
        $user->forceFill(['password' => Hash::make($password)])->save();

        return $user;
    }
}
