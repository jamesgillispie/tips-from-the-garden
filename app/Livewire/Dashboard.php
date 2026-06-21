<?php

namespace App\Livewire;

use App\Models\WritingSample;
use App\Support\MagicLink;
use Flux\Flux;
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

    /**
     * The item a confirm-delete modal is currently asking about:
     * ['kind' => 'article'|'memo'|'sample', 'id' => int, 'heading', 'body', 'confirm'].
     * Null when no modal is open. The actual mutators below still take an id and
     * work when called directly (the modal is just a friendlier front door).
     */
    public ?array $pendingDelete = null;

    public function mount(): void
    {
        $this->tab = in_array($this->tab, self::TABS, true) ? $this->tab : 'articles';
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, self::TABS, true) ? $tab : 'articles';
    }

    /**
     * Open the shared confirm-delete modal for a given item. The destructive
     * work itself stays in the dedicated mutators below, so direct calls (and
     * tests) keep working without going through the modal.
     */
    public function confirmDelete(string $kind, int $id): void
    {
        $this->pendingDelete = match ($kind) {
            'article' => [
                'kind' => 'article', 'id' => $id,
                'heading' => 'Delete this journal entry?',
                'body' => 'Your original recording stays in Recordings. This can’t be undone.',
                'confirm' => 'Delete entry',
            ],
            'memo' => [
                'kind' => 'memo', 'id' => $id,
                'heading' => 'Delete this recording?',
                'body' => 'Its journal entry and transcript go too. This can’t be undone.',
                'confirm' => 'Delete recording',
            ],
            'sample' => [
                'kind' => 'sample', 'id' => $id,
                'heading' => 'Delete this writing sample?',
                'body' => 'It will no longer shape how your journal entries sound. This can’t be undone.',
                'confirm' => 'Delete sample',
            ],
            default => null,
        };

        if ($this->pendingDelete) {
            Flux::modal('confirm-delete')->show();
        }
    }

    /** Carry out the deletion the confirm-delete modal is asking about. */
    public function performDelete(): void
    {
        $pending = $this->pendingDelete;

        if (! $pending) {
            return;
        }

        match ($pending['kind']) {
            'article' => $this->deleteArticle($pending['id']),
            'memo' => $this->deleteMemo($pending['id']),
            'sample' => $this->deleteSample($pending['id']),
            default => null,
        };

        $this->pendingDelete = null;
        Flux::modal('confirm-delete')->close();
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

        Flux::toast(text: 'Sample saved — future journal entries will learn from it.', variant: 'success');
    }

    public function toggleSample(int $sampleId): void
    {
        $sample = auth()->user()->writingSamples()->findOrFail($sampleId);

        $sample->update(['include_in_profile' => ! $sample->include_in_profile]);

        Flux::toast(text: $sample->include_in_profile
            ? 'Now shaping your voice again.'
            : 'Set aside — it won’t shape your voice.');
    }

    public function deleteSample(int $sampleId): void
    {
        auth()->user()->writingSamples()->findOrFail($sampleId)->delete();

        Flux::toast(text: 'Writing sample deleted.', variant: 'success');
    }

    /**
     * Remove a finished article. The original recording stays on the desk, so
     * the gardener can still read or re-use the transcript.
     */
    public function deleteArticle(int $articleId): void
    {
        auth()->user()->articles()->findOrFail($articleId)->delete();

        Flux::toast(text: 'Journal entry deleted.', variant: 'success');
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

        Flux::toast(text: 'Recording deleted.', variant: 'success');
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
        ])->layout('components.layouts.app', ['title' => 'My garden desk — '.config('app.name'), 'appShell' => true]);
    }
}
