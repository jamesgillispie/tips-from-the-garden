<?php

namespace App\Services;

use App\Jobs\DeliverArticle;
use App\Jobs\TranscribeAudio;
use App\Jobs\WriteArticle;
use App\Mail\SubmissionReceived;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SubmissionService
{
    /**
     * Intake from the web upload form.
     */
    public function fromUpload(UploadedFile $file, string $email): Submission
    {
        $user = User::fromEmail($email);

        $path = $file->store(
            config('pipeline.audio.path'),
            config('pipeline.audio.disk'),
        );

        $submission = Submission::create([
            'user_id' => $user->id,
            'source' => Submission::SOURCE_UPLOAD,
            'audio_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
        ]);

        $this->dispatchPipeline($submission);

        return $submission;
    }

    /**
     * Intake from a transcript the user typed or pasted directly — no audio,
     * so the pipeline skips transcription and goes straight to writing.
     */
    public function fromTranscript(string $transcript, string $email): Submission
    {
        $user = User::fromEmail($email);

        $submission = Submission::create([
            'user_id' => $user->id,
            'source' => Submission::SOURCE_PASTE,
            'audio_path' => null,
            'status' => Submission::STATUS_TRANSCRIBED,
        ]);

        $submission->transcript()->create([
            'raw_text' => trim($transcript),
            'transcriber' => 'paste',
        ]);

        $this->dispatchChain($submission, [
            new WriteArticle($submission),
            new DeliverArticle($submission),
        ]);

        return $submission;
    }

    /**
     * Intake from the inbound email webhook (attachment arrives base64-encoded).
     */
    public function fromEmail(string $email, ?string $name, string $filename, string $base64Content): Submission
    {
        $user = User::fromEmail($email, $name);

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION)) ?: 'm4a';
        $path = config('pipeline.audio.path').'/'.Str::uuid().'.'.$extension;

        Storage::disk(config('pipeline.audio.disk'))->put($path, base64_decode($base64Content));

        $submission = Submission::create([
            'user_id' => $user->id,
            'source' => Submission::SOURCE_EMAIL,
            'audio_path' => $path,
            'original_filename' => $filename,
        ]);

        Mail::to($user->email)->queue(new SubmissionReceived($submission));

        $this->dispatchPipeline($submission);

        return $submission;
    }

    public function dispatchPipeline(Submission $submission): void
    {
        $this->dispatchChain($submission, [
            new TranscribeAudio($submission),
            new WriteArticle($submission),
            new DeliverArticle($submission),
        ]);
    }

    /**
     * Run a list of pipeline jobs as a chain, marking the submission failed
     * if any link throws.
     *
     * @param  array<int, object>  $jobs
     */
    protected function dispatchChain(Submission $submission, array $jobs): void
    {
        Bus::chain($jobs)
            ->catch(function (\Throwable $e) use ($submission) {
                $submission->fresh()?->markFailed($e->getMessage());
            })
            ->dispatch();
    }
}
