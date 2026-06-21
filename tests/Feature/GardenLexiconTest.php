<?php

namespace Tests\Feature;

use App\Jobs\TranscribeAudio;
use App\Models\Submission;
use App\Models\User;
use App\Pipeline\Contracts\TranscriberContract;
use App\Pipeline\Data\TranscriptionResult;
use App\Pipeline\Support\GardenLexicon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GardenLexiconTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_corrects_the_reported_arugula_mishearing(): void
    {
        $this->assertSame(
            'I planted arugula by the fence.',
            GardenLexicon::normalize('I planted rucola by the fence.'),
        );
    }

    public function test_it_preserves_sentence_start_capitalization(): void
    {
        $this->assertSame(
            'Arugula is up already.',
            GardenLexicon::normalize('Rucola is up already.'),
        );
    }

    public function test_it_canonicalizes_split_and_miscased_cultivars(): void
    {
        $this->assertSame(
            'The Sungold and Cherokee Purple and Brandywine are in.',
            GardenLexicon::normalize('The sun gold and cherokee purple and brandy wine are in.'),
        );
    }

    public function test_it_leaves_already_correct_text_untouched(): void
    {
        $text = 'The arugula and Cherokee Purple tomatoes look great.';
        $this->assertSame($text, GardenLexicon::normalize($text));
    }

    public function test_it_respects_word_boundaries(): void
    {
        // A variant embedded in a longer token must not be rewritten.
        $this->assertSame('rucolas', GardenLexicon::normalize('rucolas'));
    }

    public function test_whisper_prompt_lists_tricky_names(): void
    {
        $prompt = GardenLexicon::whisperPrompt();

        $this->assertStringContainsString('arugula', $prompt);
        $this->assertStringContainsString('Cherokee Purple', $prompt);
    }

    public function test_the_transcription_job_normalizes_stored_text(): void
    {
        $this->app->bind(TranscriberContract::class, fn () => new class implements TranscriberContract
        {
            public function transcribe(string $audioPath): TranscriptionResult
            {
                return new TranscriptionResult(
                    text: 'Out by the beds the rucola bolted and the sun gold split.',
                    durationSeconds: 12.0,
                );
            }

            public function identifier(): string
            {
                return 'fake:lexicon-test';
            }
        });

        $user = User::fromEmail('gardener@example.test');
        $submission = Submission::create([
            'user_id' => $user->id,
            'source' => Submission::SOURCE_UPLOAD,
            'audio_path' => 'audio/fake.m4a',
        ]);

        TranscribeAudio::dispatchSync($submission);

        $this->assertSame(
            'Out by the beds the arugula bolted and the Sungold split.',
            $submission->fresh()->transcript->raw_text,
        );
    }
}
