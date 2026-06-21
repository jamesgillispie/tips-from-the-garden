<?php

namespace App\Livewire;

use App\Models\WritingSample;
use Livewire\Attributes\Url;
use Livewire\Component;

class Dashboard extends Component
{
    /** The desk tabs a gardener can land on; anything else falls back to articles. */
    public const TABS = ['articles', 'recordings', 'voice'];

    /** Which tab is showing: 'articles', 'recordings', or 'voice'. */
    #[Url]
    public string $tab = 'articles';

    /** Free-text search over the title and body of the gardener's journal entries. */
    #[Url]
    public string $search = '';

    public string $sampleTitle = '';

    public string $sampleBody = '';

    public function mount(): void
    {
        $this->tab = in_array($this->tab, self::TABS, true) ? $this->tab : 'articles';
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, self::TABS, true) ? $tab : 'articles';
    }

    public function addSample(): void
    {
        $this->validate([
            'sampleTitle' => ['nullable', 'string', 'max:255'],
            'sampleBody' => ['required', 'string', 'min:100', 'max:50000'],
        ], [
            'sampleBody.min' => 'Give us at least a paragraph or two — short snippets don\'t teach us much about your voice.',
        ]);

        auth()->user()->writingSamples()->create([
            'source' => WritingSample::SOURCE_PASTE,
            'title' => $this->sampleTitle ?: null,
            'body' => $this->sampleBody,
            'include_in_profile' => true,
        ]);

        $this->reset('sampleTitle', 'sampleBody');

        session()->flash('sample-added', 'Sample saved — future journal entries will learn from it.');
    }

    public function toggleSample(int $sampleId): void
    {
        $sample = auth()->user()->writingSamples()->findOrFail($sampleId);

        $sample->update(['include_in_profile' => ! $sample->include_in_profile]);
    }

    public function deleteSample(int $sampleId): void
    {
        auth()->user()->writingSamples()->findOrFail($sampleId)->delete();
    }

    /**
     * Remove a finished article. The original recording stays on the desk, so
     * the gardener can still read or re-use the transcript.
     */
    public function deleteArticle(int $articleId): void
    {
        auth()->user()->articles()->findOrFail($articleId)->delete();

        session()->flash('desk-flash', 'Journal entry deleted.');
    }

    /**
     * Remove a recording and everything that came from it. The transcript
     * cascades with the submission; the article's foreign key only nulls on a
     * hard delete, so we delete it explicitly to keep it from lingering in the
     * Articles tab once its recording is gone.
     */
    public function deleteMemo(int $submissionId): void
    {
        $submission = auth()->user()->submissions()->findOrFail($submissionId);

        $submission->article?->delete();
        $submission->delete();

        session()->flash('desk-flash', 'Recording deleted.');
    }

    public function render()
    {
        $user = auth()->user();

        // Search applies only on the Journal tab, where the box lives. A blank
        // term leaves the list untouched, so it's a no-op everywhere else.
        $articles = $user->articles()->latest();

        if ($this->tab === 'articles' && ($term = trim($this->search)) !== '') {
            $articles->where(function ($query) use ($term) {
                $query->where('title', 'like', "%{$term}%")
                    ->orWhere('body_md', 'like', "%{$term}%");
            });
        }

        return view('livewire.dashboard', [
            'articles' => $articles->get(),
            'samples' => $user->writingSamples()->latest()->get(),
            'memos' => $user->submissions()->with(['transcript', 'article'])->latest()->get(),
            'profileText' => $user->voiceProfile?->profile_text,
        ])->layout('components.layouts.app', ['title' => 'My garden desk — '.config('app.name')]);
    }
}
