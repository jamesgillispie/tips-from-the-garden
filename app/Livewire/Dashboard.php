<?php

namespace App\Livewire;

use App\Models\WritingSample;
use App\Support\MagicLink;
use Livewire\Attributes\Url;
use Livewire\Component;

class Dashboard extends Component
{
    /** Which tab is showing: 'articles', 'recordings', or 'voice'. */
    #[Url]
    public string $tab = 'articles';

    public string $sampleTitle = '';

    public string $sampleBody = '';

    public function mount(): void
    {
        $this->tab = in_array($this->tab, MagicLink::TABS, true) ? $this->tab : 'articles';
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, MagicLink::TABS, true) ? $tab : 'articles';
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

        session()->flash('sample-added', 'Sample saved — future articles will learn from it.');
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

    public function render()
    {
        $user = auth()->user();

        return view('livewire.dashboard', [
            'articles' => $user->articles()->latest()->get(),
            'samples' => $user->writingSamples()->latest()->get(),
            'memos' => $user->submissions()->with(['transcript', 'article'])->latest()->get(),
            'profileText' => $user->voiceProfile?->profile_text,
        ])->layout('components.layouts.app', ['title' => 'My garden desk — '.config('app.name')]);
    }
}
