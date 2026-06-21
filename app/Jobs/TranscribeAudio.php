<?php

namespace App\Jobs;

use App\Models\Submission;
use App\Pipeline\Contracts\TranscriberContract;
use App\Pipeline\Support\GardenLexicon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class TranscribeAudio implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $timeout = 3600;

    public int $tries = 2;

    public function __construct(
        public Submission $submission,
    ) {}

    public function handle(TranscriberContract $transcriber): void
    {
        $this->submission->markAs(Submission::STATUS_TRANSCRIBING);

        $audioPath = Storage::disk(config('pipeline.audio.disk'))
            ->path($this->submission->audio_path);

        $result = $transcriber->transcribe($audioPath);

        // Correct known plant/cultivar mishearings (e.g. "rucola" -> "arugula")
        // before the transcript is stored, written from, or shown to the gardener.
        $text = config('pipeline.lexicon.enabled')
            ? GardenLexicon::normalize($result->text)
            : $result->text;

        // updateOrCreate keeps a retried attempt from leaving two transcripts.
        $this->submission->transcript()->updateOrCreate([], [
            'raw_text' => $text,
            'transcriber' => $transcriber->identifier(),
            'duration_seconds' => $result->durationSeconds,
        ]);

        $this->submission->markAs(Submission::STATUS_TRANSCRIBED);
    }

    public function failed(?\Throwable $exception): void
    {
        $this->submission->fresh()?->markFailed(
            $exception?->getMessage() ?? 'Transcription failed.'
        );
    }
}
