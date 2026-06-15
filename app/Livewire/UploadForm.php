<?php

namespace App\Livewire;

use App\Models\Submission;
use App\Services\SubmissionService;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class UploadForm extends Component
{
    use WithFileUploads;

    /**
     * Which intake is showing:
     * 'record' (record on the page), 'audio' (upload a file),
     * or 'paste' (type a transcript). This component is reached only when
     * signed in, so the memo is always filed to the current gardener.
     */
    public string $mode = 'record';

    public ?TemporaryUploadedFile $audio = null;

    public string $transcript = '';

    public function setMode(string $mode): void
    {
        $this->mode = in_array($mode, ['record', 'audio', 'paste'], true) ? $mode : 'record';
        $this->resetErrorBag();
    }

    protected function rules(): array
    {
        if ($this->mode === 'paste') {
            return ['transcript' => ['required', 'string', 'min:40', 'max:50000']];
        }

        return [
            'audio' => [
                'required',
                'file',
                'mimes:'.implode(',', config('pipeline.audio.mimes')),
                'max:'.config('pipeline.audio.max_size_kb'),
            ],
        ];
    }

    protected function messages(): array
    {
        return [
            'audio.required' => $this->mode === 'record'
                ? 'Record your memo first — press the big green button above.'
                : 'Choose a voice memo file first.',
            'audio.mimes' => "That doesn't look like a recording we can read — an m4a, mp3, or wav file works best.",
            'audio.max' => 'That recording is too large — anything under about an hour is fine.',
            'transcript.required' => 'Type or paste your garden notes first.',
            'transcript.min' => "Give us a few sentences at least — a short note doesn't tell us much.",
        ];
    }

    public function submit(SubmissionService $service)
    {
        $this->validate();

        $email = auth()->user()->email;
        $field = $this->mode === 'paste' ? 'transcript' : 'audio';
        $key = 'submit:'.strtolower($email);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError($field, 'Too many submissions — give us a few minutes to catch up.');

            return;
        }

        RateLimiter::hit($key, 600);

        match ($this->mode) {
            'paste' => $service->fromTranscript($this->transcript, $email),
            'record' => $service->fromUpload($this->audio, $email, Submission::SOURCE_RECORD),
            default => $service->fromUpload($this->audio, $email),
        };

        return $this->redirectRoute('dashboard', ['tab' => 'recordings']);
    }

    public function render()
    {
        return view('livewire.upload-form')
            ->layout('components.layouts.app', ['title' => config('app.name')]);
    }
}
