<?php

namespace App\Livewire;

use App\Services\SubmissionService;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class UploadForm extends Component
{
    use WithFileUploads;

    /** Which intake the form is showing: 'audio' (upload a file) or 'paste' (type a transcript). */
    public string $mode = 'audio';

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
        $this->mode = $mode === 'paste' ? 'paste' : 'audio';
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

        $submission = $this->mode === 'paste'
            ? $service->fromTranscript($this->transcript, $this->email)
            : $service->fromUpload($this->audio, $this->email);

        return $this->redirectRoute('submissions.status', ['submission' => $submission->uuid]);
    }

    public function render()
    {
        return view('livewire.upload-form')
            ->layout('components.layouts.app', ['title' => config('app.name')]);
    }
}
