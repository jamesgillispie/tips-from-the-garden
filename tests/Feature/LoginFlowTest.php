<?php

namespace Tests\Feature;

use App\Mail\MagicLinkMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LoginFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_the_record_page_to_login(): void
    {
        $this->get(route('home'))->assertRedirect(route('login'));
    }

    public function test_the_login_landing_shows_the_email_form_and_turnstile(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Email me a sign-in link')
            ->assertSee('cf-turnstile', false);
    }

    public function test_signed_in_visitors_skip_the_login_landing(): void
    {
        $user = User::fromEmail('rose@example.test');

        $this->actingAs($user)->get(route('login'))->assertRedirect(route('home'));
    }

    public function test_a_valid_turnstile_lets_the_magic_link_through(): void
    {
        Mail::fake();
        config(['services.turnstile.secret_key' => 'test-secret']);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);

        $this->post(route('auth.magic.send'), [
            'email' => 'gardener@example.test',
            'cf-turnstile-response' => 'a-token',
        ])->assertRedirect();

        Mail::assertSent(MagicLinkMail::class);
        $this->assertDatabaseHas('users', ['email' => 'gardener@example.test']);
    }

    public function test_a_failed_turnstile_blocks_the_magic_link(): void
    {
        Mail::fake();
        config(['services.turnstile.secret_key' => 'test-secret']);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false])]);

        $this->post(route('auth.magic.send'), [
            'email' => 'gardener@example.test',
            'cf-turnstile-response' => 'bad-token',
        ])->assertSessionHasErrors('turnstile');

        Mail::assertNothingSent();
        $this->assertDatabaseMissing('users', ['email' => 'gardener@example.test']);
    }
}
