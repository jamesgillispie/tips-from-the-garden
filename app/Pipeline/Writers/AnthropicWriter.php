<?php

namespace App\Pipeline\Writers;

use App\Pipeline\Concerns\BuildsArticlePrompts;
use App\Pipeline\Contracts\WriterContract;
use App\Pipeline\Data\ArticleDraft;
use App\Pipeline\Data\WriteRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AnthropicWriter implements WriterContract
{
    use BuildsArticlePrompts;

    public function __construct(
        protected string $apiKey,
        protected string $model,
        protected int $maxTokens = 8000,
        protected int $timeout = 300,
    ) {}

    public function write(WriteRequest $request): ArticleDraft
    {
        $raw = $this->complete($this->systemPrompt($request), $this->userPrompt($request));

        return $this->parseDraft($raw);
    }

    public function summarizeStyle(array $samples): string
    {
        return trim($this->complete($this->styleSystemPrompt(), $this->fencedSamples($samples)));
    }

    public function identifier(): string
    {
        return 'anthropic:'.$this->model;
    }

    protected function complete(string $system, string $user): string
    {
        if (blank($this->apiKey)) {
            throw new RuntimeException('ANTHROPIC_API_KEY is not set.');
        }

        // Retry the transient failures (429 rate limit, 529 overloaded, 5xx)
        // with exponential backoff. Without this a single momentary 429 burns
        // both of WriteArticle's tries back-to-back and emails the gardener a
        // failure for something that would have cleared in seconds.
        $response = Http::timeout($this->timeout)
            ->retry(3, 2000, function (\Throwable $e) {
                if (! $e instanceof RequestException) {
                    return true; // connection/timeout — worth another go
                }

                $status = $e->response->status();

                return $status === 429 || $status >= 500;
            }, throw: false)
            ->withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model,
                'max_tokens' => $this->maxTokens,
                'system' => $system,
                'messages' => [
                    ['role' => 'user', 'content' => $user],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                "Anthropic request failed ({$response->status()}): ".$response->body()
            );
        }

        $content = collect($response->json('content', []))
            ->where('type', 'text')
            ->pluck('text')
            ->implode("\n");

        if (trim($content) === '') {
            throw new RuntimeException('Anthropic returned an empty response.');
        }

        return $content;
    }
}
