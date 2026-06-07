<?php

namespace App\Livewire;

use App\Models\Submission;
use Livewire\Component;

class SubmissionStatus extends Component
{
    public Submission $submission;

    public function mount(Submission $submission): void
    {
        $this->submission = $submission;
    }

    public function render()
    {
        $this->submission->refresh();

        return view('livewire.submission-status')
            ->layout('components.layouts.app', ['title' => 'Your article — '.config('app.name')]);
    }
}
