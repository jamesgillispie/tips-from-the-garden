<?php

namespace App\Livewire;

use App\Models\Submission;
use App\Services\SubmissionService;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
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

    /**
     * The intake the previous render committed — so render() can crossfade the
     * panel on a real tab switch but stay still during an upload-progress
     * re-render. Server-set only, hence #[Locked].
     */
    #[Locked]
    public ?string $renderedMode = null;

    public ?TemporaryUploadedFile $audio = null;

    public string $transcript = '';

    /**
     * Photos captured alongside the memo. Shared across all three intake
     * tabs — swapping how you tell the story shouldn't drop what you saw.
     *
     * @var array<int, TemporaryUploadedFile>
     */
    public array $photos = [];

    /**
     * The intake tabs bind straight to $mode via wire:model. Whenever it
     * changes we clear any stale validation errors and keep the value to the
     * three known modes (the tab names are controlled, but stay defensive).
     */
    public function updatedMode(): void
    {
        $this->mode = in_array($this->mode, ['record', 'audio', 'paste'], true) ? $this->mode : 'record';
        $this->resetErrorBag();
    }

    protected function rules(): array
    {
        $rules = [
            'photos' => ['array', 'max:'.config('pipeline.photos.max_per_submission')],
            'photos.*' => [
                'file',
                'mimes:'.implode(',', config('pipeline.photos.mimes')),
                'max:'.config('pipeline.photos.max_size_kb'),
            ],
        ];

        if ($this->mode === 'paste') {
            return $rules + ['transcript' => ['required', 'string', 'min:40', 'max:50000']];
        }

        return $rules + [
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
            'photos.max' => 'Up to '.config('pipeline.photos.max_per_submission').' photos per memo — pick your favourites.',
            'photos.*.mimes' => "That file doesn't look like a photo — a JPEG, PNG, or HEIC from your camera works best.",
            'photos.*.max' => 'That photo is too large — anything straight from a phone camera is fine.',
        ];
    }

    /** Drop a staged photo before it's submitted. */
    public function removePhoto(int $index): void
    {
        unset($this->photos[$index]);
        $this->photos = array_values($this->photos);
        $this->resetErrorBag();
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

        $submission = match ($this->mode) {
            'paste' => $service->fromTranscript($this->transcript, $email, $this->photos),
            'record' => $service->fromUpload($this->audio, $email, Submission::SOURCE_RECORD, $this->photos),
            default => $service->fromUpload($this->audio, $email, photos: $this->photos),
        };

        // Straight to the live processing page — it polls the pipeline and then
        // shows the finished article in place.
        return $this->redirectRoute('submissions.status', ['submission' => $submission->uuid]);
    }

    public function render()
    {
        // Crossfade the intake panel only when the tab itself changed.
        $animateMode = $this->renderedMode !== null && $this->renderedMode !== $this->mode;
        $this->renderedMode = $this->mode;

        return view('livewire.upload-form', ['animateMode' => $animateMode])
            ->layout('components.layouts.app', ['title' => config('app.name')]);
    }
}
