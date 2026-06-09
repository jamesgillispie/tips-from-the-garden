<?php

namespace Tests\Feature;

use App\Mail\SubmissionFailed;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FailureNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_gardener_is_emailed_when_a_submission_fails(): void
    {
        Mail::fake();

        $submission = $this->makeSubmission();

        $submission->markFailed('whisper.cpp failed: boom');

        $this->assertTrue($submission->fresh()->isFailed());
        $this->assertStringContainsString('boom', $submission->fresh()->error);

        Mail::assertQueued(SubmissionFailed::class, fn ($mail) => $mail->hasTo('gardener@example.test'));
    }

    public function test_the_failure_email_is_only_sent_once(): void
    {
        Mail::fake();

        $submission = $this->makeSubmission();

        // The chain's catch and the job's failed() hook can both land here.
        $submission->markFailed('first failure');
        $submission->fresh()->markFailed('second failure');

        Mail::assertQueuedCount(1);
        $this->assertStringContainsString('first failure', $submission->fresh()->error);
    }

    private function makeSubmission(): Submission
    {
        $user = User::fromEmail('gardener@example.test');

        return Submission::create([
            'user_id' => $user->id,
            'source' => Submission::SOURCE_UPLOAD,
            'audio_path' => 'audio/fake.m4a',
            'original_filename' => 'fake.m4a',
        ]);
    }
}
