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
    public function __construct(
        protected PhotoStorer $photoStorer,
    ) {}

    /**
     * Intake from the web form — an uploaded file or an in-browser recording.
     *
     * @param  array<int, UploadedFile>  $photos
     */
    public function fromUpload(UploadedFile $file, string $email, string $source = Submission::SOURCE_UPLOAD, array $photos = []): Submission
    {
        $user = User::fromEmail($email);

        $path = $file->store(
            config('pipeline.audio.path'),
            config('pipeline.audio.disk'),
        );

        $submission = Submission::create([
            'user_id' => $user->id,
            'source' => $source,
            'audio_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
        ]);

        $this->storePhotos($submission, $photos);

        $this->dispatchPipeline($submission);

        return $submission;
    }

    /**
     * Intake from a transcript the user typed or pasted directly — no audio,
     * so the pipeline skips transcription and goes straight to writing.
     *
     * @param  array<int, UploadedFile>  $photos
     */
    public function fromTranscript(string $transcript, string $email, array $photos = []): Submission
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

        $this->storePhotos($submission, $photos);

        $this->dispatchChain($submission, [
            new WriteArticle($submission),
            new DeliverArticle($submission),
        ]);

        return $submission;
    }

    /**
     * Bank photos against the recording *before* the chain dispatches — on a
     * sync queue delivery runs inside dispatch, and the delivered email
     * includes the photos.
     *
     * @param  array<int, UploadedFile>  $photos
     */
    protected function storePhotos(Submission $submission, array $photos): void
    {
        foreach ($photos as $photo) {
            $this->photoStorer->attach($submission, $photo->get(), $photo->getClientOriginalName());
        }
    }

    /**
     * Intake from the inbound email webhook (attachments arrive base64-encoded).
     * The account is resolved by the controller — only an existing gardener can
     * email a memo in, so this never creates a user.
     *
     * @param  array<int, array{Name?: string, Content?: string}>  $photoAttachments
     */
    public function fromEmail(User $user, string $filename, string $base64Content, array $photoAttachments = []): Submission
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION)) ?: 'm4a';
        $path = config('pipeline.audio.path').'/'.Str::uuid().'.'.$extension;

        Storage::disk(config('pipeline.audio.disk'))->put($path, base64_decode($base64Content));

        $submission = Submission::create([
            'user_id' => $user->id,
            'source' => Submission::SOURCE_EMAIL,
            'audio_path' => $path,
            'original_filename' => $filename,
        ]);

        foreach ($photoAttachments as $photo) {
            $this->photoStorer->attach(
                $submission,
                base64_decode($photo['Content'] ?? ''),
                $photo['Name'] ?? null,
            );
        }

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
