<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class GardenDeskTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_a_signed_in_gardener(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_recordings_tab_lists_memos_with_a_transcript_to_download(): void
    {
        $user = User::fromEmail('rose@example.test');
        $memo = $this->memoFor($user, 'The dahlias are blooming early this year.');

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->set('tab', 'recordings')
            ->assertSee('Recorded here')
            ->assertSee('The dahlias are blooming early this year.')
            ->assertSee('Download transcript')
            ->assertSee(route('memos.transcript', ['submission' => $memo->uuid]));
    }

    public function test_a_gardener_can_download_their_memo_transcript_as_markdown(): void
    {
        $user = User::fromEmail('rose@example.test');
        $memo = $this->memoFor($user, 'The dahlias are blooming early this year.');

        $this->actingAs($user)
            ->get(route('memos.transcript', ['submission' => $memo->uuid]))
            ->assertOk()
            ->assertHeader('content-type', 'text/markdown; charset=UTF-8')
            ->assertSee('The dahlias are blooming early this year.', false);
    }

    public function test_a_gardener_cannot_download_another_gardeners_transcript(): void
    {
        $owner = User::fromEmail('owner@example.test');
        $memo = $this->memoFor($owner, 'My private garden notes.');

        $intruder = User::fromEmail('intruder@example.test');

        $this->actingAs($intruder)
            ->get(route('memos.transcript', ['submission' => $memo->uuid]))
            ->assertForbidden();
    }

    public function test_a_tab_aware_magic_link_lands_on_the_recordings_tab(): void
    {
        $user = User::fromEmail('rose@example.test');

        $url = URL::temporarySignedRoute(
            'auth.magic.login',
            now()->addMinutes(30),
            ['user' => $user->id, 'tab' => 'recordings'],
        );

        $this->get($url)->assertRedirect(route('dashboard', ['tab' => 'recordings']));
        $this->assertAuthenticatedAs($user);
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
