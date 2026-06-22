<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\Article;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_a_guest_deep_linking_to_a_tab_is_returned_there_after_login(): void
    {
        $user = User::fromEmail('rose@example.test');
        $user->forceFill(['password' => Hash::make('open-sesame-9')])->save();

        // The login wall remembers where the guest was headed…
        $this->get(route('dashboard', ['tab' => 'recordings']))->assertRedirect(route('login'));

        // …and signing in returns them to that exact tab — no bespoke deep-link
        // params needed now that it rides Laravel's "intended URL".
        $this->post(route('login'), [
            'email' => 'rose@example.test',
            'password' => 'open-sesame-9',
        ])->assertRedirect(route('dashboard', ['tab' => 'recordings']));

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_gardener_can_delete_an_article_and_keep_the_recording(): void
    {
        $user = User::fromEmail('rose@example.test');
        $memo = $this->memoFor($user, 'The dahlias are blooming early this year.');
        $article = $this->articleFor($memo);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->call('deleteArticle', $article->id)
            ->assertHasNoErrors();

        $this->assertSoftDeleted('articles', ['id' => $article->id]);
        $this->assertNotSoftDeleted('submissions', ['id' => $memo->id]);
    }

    public function test_a_gardener_can_delete_a_recording_along_with_its_article(): void
    {
        $user = User::fromEmail('rose@example.test');
        $memo = $this->memoFor($user, 'The dahlias are blooming early this year.');
        $article = $this->articleFor($memo);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->call('deleteMemo', $memo->id)
            ->assertHasNoErrors();

        $this->assertSoftDeleted('submissions', ['id' => $memo->id]);
        $this->assertSoftDeleted('articles', ['id' => $article->id]);
    }

    public function test_a_gardener_cannot_delete_another_gardeners_recording(): void
    {
        $owner = User::fromEmail('owner@example.test');
        $memo = $this->memoFor($owner, 'My private garden notes.');

        $intruder = User::fromEmail('intruder@example.test');

        try {
            Livewire::actingAs($intruder)
                ->test(Dashboard::class)
                ->call('deleteMemo', $memo->id);
        } catch (ModelNotFoundException $e) {
            // Expected — submissions are scoped to their owner.
        }

        $this->assertNotSoftDeleted('submissions', ['id' => $memo->id]);
    }

    public function test_the_desk_advertises_the_configured_inbound_address(): void
    {
        config(['pipeline.inbound.address' => 'memos@manorhousegardens.org']);

        $user = User::fromEmail('rose@example.test');

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSee('memos@manorhousegardens.org');
    }

    public function test_the_desk_tells_gardeners_which_address_to_email_memos_from(): void
    {
        $user = User::fromEmail('rose@example.test');

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            // The identity guard: send from the address on this account, or the
            // memo lands nowhere the gardener can see it.
            ->assertSee('rose@example.test')
            ->assertSee('the address on this account')
            ->assertSee('Sign out');
    }

    public function test_a_gardener_can_search_their_journal_entries_by_title_and_body(): void
    {
        $user = User::fromEmail('rose@example.test');
        $this->articleFor($this->memoFor($user, 'memo one'), 'Tomatoes in June', 'Staking the heirlooms today.');
        $this->articleFor($this->memoFor($user, 'memo two'), 'Pruning roses', 'The climbers need a hard cut.');

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->set('tab', 'articles')
            // Matches on the title…
            ->set('search', 'tomatoes')
            ->assertSee('Tomatoes in June')
            ->assertDontSee('Pruning roses')
            // …and on the body text the gardener can't see in the list.
            ->set('search', 'climbers')
            ->assertSee('Pruning roses')
            ->assertDontSee('Tomatoes in June')
            // Clearing the box brings everything back.
            ->set('search', '')
            ->assertSee('Tomatoes in June')
            ->assertSee('Pruning roses');
    }

    public function test_search_only_returns_the_signed_in_gardeners_entries(): void
    {
        $owner = User::fromEmail('owner@example.test');
        $this->articleFor($this->memoFor($owner, 'owner memo'), 'Owner tomatoes');

        $intruder = User::fromEmail('intruder@example.test');
        $this->articleFor($this->memoFor($intruder, 'intruder memo'), 'Intruder tomatoes');

        Livewire::actingAs($intruder)
            ->test(Dashboard::class)
            ->set('tab', 'articles')
            ->set('search', 'tomatoes')
            ->assertSee('Intruder tomatoes')
            ->assertDontSee('Owner tomatoes');
    }

    private function articleFor(
        Submission $memo,
        string $title = 'Early dahlias',
        string $bodyMd = 'They came up before the last frost.',
    ): Article {
        return Article::create([
            'user_id' => $memo->user_id,
            'submission_id' => $memo->id,
            'title' => $title,
            'body_md' => $bodyMd,
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
