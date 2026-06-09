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
     * Which intake the form is showing:
     * 'record' (record right on the page), 'audio' (upload a file),
     * or 'paste' (type a transcript).
     */
    public string $mode = 'record';

    public ?TemporaryUploadedFile $audio = null;

    public string $transcript = '';

    public string $email = '';

    public function mount(): void
    {
        if (auth()->check()) {
            $this->email = auth()->user()->email;
        }
    }

    public function setMode(string $mode): void
    {
        $this->mode = in_array($mode, ['record', 'audio', 'paste'], true) ? $mode : 'record';
        $this->resetErrorBag();
    }

    protected function rules(): array
    {
        $rules = ['email' => ['required', 'email']];

        if ($this->mode === 'paste') {
            $rules['transcript'] = ['required', 'string', 'min:40', 'max:50000'];
        } else {
            $rules['audio'] = [
                'required',
                'file',
                'mimes:'.implode(',', config('pipeline.audio.mimes')),
                'max:'.config('pipeline.audio.max_size_kb'),
            ];
        }

        return $rules;
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
            'email.required' => 'Enter your email so we know where to send your article.',
            'email.email' => "That email address doesn't look quite right — double-check it?",
        ];
    }

    public function submit(SubmissionService $service)
    {
        $this->validate();

        $field = $this->mode === 'paste' ? 'transcript' : 'audio';

        $key = 'submit:'.strtolower($this->email);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError($field, 'Too many submissions — give us a few minutes to catch up.');

            return;
        }

        RateLimiter::hit($key, 600);

        $submission = match ($this->mode) {
            'paste' => $service->fromTranscript($this->transcript, $this->email),
            'record' => $service->fromUpload($this->audio, $this->email, Submission::SOURCE_RECORD),
            default => $service->fromUpload($this->audio, $this->email),
        };

        return $this->redirectRoute('submissions.status', ['submission' => $submission->uuid]);
    }

    public function render()
    {
        return view('livewire.upload-form')
            ->layout('components.layouts.app', ['title' => config('app.name')]);
    }
}
