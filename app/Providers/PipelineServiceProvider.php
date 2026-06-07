<?php

namespace App\Providers;

use App\Pipeline\Contracts\TranscriberContract;
use App\Pipeline\Contracts\WriterContract;
use App\Pipeline\Transcribers\FakeTranscriber;
use App\Pipeline\Transcribers\WhisperCppTranscriber;
use App\Pipeline\Writers\AnthropicWriter;
use App\Pipeline\Writers\FakeWriter;
use App\Pipeline\Writers\OllamaWriter;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class PipelineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TranscriberContract::class, function () {
            $driver = config('pipeline.transcriber');

            return match ($driver) {
                'whisper_cpp' => new WhisperCppTranscriber(
                    binary: config('pipeline.whisper_cpp.binary'),
                    model: config('pipeline.whisper_cpp.model'),
                    ffmpeg: config('pipeline.whisper_cpp.ffmpeg'),
                    threads: (int) config('pipeline.whisper_cpp.threads'),
                ),
                'fake' => new FakeTranscriber,
                default => throw new InvalidArgumentException("Unknown transcriber driver [{$driver}]."),
            };
        });

        $this->app->bind(WriterContract::class, function () {
            $driver = config('pipeline.writer');

            return match ($driver) {
                'ollama' => new OllamaWriter(
                    baseUrl: config('pipeline.ollama.base_url'),
                    model: config('pipeline.ollama.model'),
                    timeout: (int) config('pipeline.ollama.timeout'),
                ),
                'anthropic' => new AnthropicWriter(
                    apiKey: (string) config('pipeline.anthropic.api_key'),
                    model: config('pipeline.anthropic.model'),
                    maxTokens: (int) config('pipeline.anthropic.max_tokens'),
                    timeout: (int) config('pipeline.anthropic.timeout'),
                ),
                'fake' => new FakeWriter,
                default => throw new InvalidArgumentException("Unknown writer driver [{$driver}]."),
            };
        });
    }
}
