<?php

namespace Tests\Feature;

use App\Jobs\DeliverArticle;
use App\Jobs\TranscribeAudio;
use App\Jobs\WriteArticle;
use App\Livewire\UploadForm;
use App\Models\Submission;
use App\Services\SubmissionService;
use Database\Seeders\ArticleTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class PasteTranscriptTest extends TestCase
{
    use RefreshDatabase;

    private const SAMPLE = 'The tomatoes finally set fruit after that cold snap, '
        .'and the basil next to them needs pinching back before it gets leggy.';

    public function test_pasted_transcript_produces_an_article_without_audio(): void
    {
        $this->seed(ArticleTemplateSeeder::class);
        Mail::fake();

        $submission = app(SubmissionService::class)
            ->fromTranscript(self::SAMPLE, 'gardener@example.test');

        $submission->refresh();

        $this->assertEquals(Submission::SOURCE_PASTE, $submission->source);
        $this->assertNull($submission->audio_path);
        $this->assertEquals(Submission::STATUS_READY, $submission->status);
        $this->assertEquals('paste', $submission->transcript->transcriber);
        $this->assertEquals(self::SAMPLE, $submission->transcript->raw_text);
        $this->assertNotNull($submission->article);
        $this->assertNotEmpty($submission->article->title);
    }

    public function test_paste_pipeline_skips_the_transcription_job(): void
    {
        Bus::fake();

        app(SubmissionService::class)->fromTranscript(self::SAMPLE, 'gardener@example.test');

        Bus::assertChained([
            WriteArticle::class,
            DeliverArticle::class,
        ]);
        Bus::assertNotDispatched(TranscribeAudio::class);
    }

    public function test_upload_form_accepts_a_pasted_transcript(): void
    {
        $this->seed(ArticleTemplateSeeder::class);
        Mail::fake();

        Livewire::test(UploadForm::class)
            ->set('mode', 'paste')
            ->set('transcript', self::SAMPLE)
            ->set('email', 'gardener@example.test')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('submissions', [
            'source' => Submission::SOURCE_PASTE,
            'audio_path' => null,
        ]);
    }

    public function test_pasted_transcript_must_not_be_trivially_short(): void
    {
        Livewire::test(UploadForm::class)
            ->set('mode', 'paste')
            ->set('transcript', 'too short')
            ->set('email', 'gardener@example.test')
            ->call('submit')
            ->assertHasErrors(['transcript']);

        $this->assertDatabaseCount('submissions', 0);
    }
}
