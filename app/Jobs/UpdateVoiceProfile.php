<?php

namespace App\Jobs;

use App\Models\Submission;
use App\Models\WritingSample;
use App\Pipeline\Contracts\WriterContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * After delivery: bank the transcript as a writing sample and
 * periodically regenerate the user's voice profile.
 */
class UpdateVoiceProfile implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $timeout = 1800;

    public function __construct(
        public Submission $submission,
    ) {}

    public function handle(WriterContract $writer): void
    {
        $user = $this->submission->user;
        $transcript = $this->submission->transcript;

        if ($transcript === null) {
            return;
        }

        $user->writingSamples()->create([
            'source' => WritingSample::SOURCE_TRANSCRIPT,
            'title' => 'Voice memo — '.$this->submission->created_at?->format('M j, Y'),
            'body' => $transcript->raw_text,
            'include_in_profile' => true,
        ]);

        $profile = $user->voiceProfile()->firstOrCreate([]);
        $profile->increment('sample_count');

        $every = max(1, (int) config('pipeline.voice_profile.regenerate_every'));

        if ($profile->sample_count % $every !== 0 && $profile->profile_text !== null) {
            return;
        }

        $samples = $user->writingSamples()
            ->active()
            ->latest()
            ->limit((int) config('pipeline.voice_profile.max_samples_in_prompt'))
            ->pluck('body')
            ->all();

        if ($samples === []) {
            return;
        }

        try {
            $profile->update(['profile_text' => $writer->summarizeStyle($samples)]);
        } catch (\Throwable $e) {
            // A failed profile refresh should never look like a failed
            // submission — the article already shipped. Log and move on.
            Log::warning('Voice profile regeneration failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
