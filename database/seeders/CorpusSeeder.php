<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\WritingSample;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Seeds a practice account from files dropped into database/corpus/.
 *
 * Supported formats:
 *   - .md / .txt  → one sample per file (first heading or filename = title)
 *   - .html       → tags stripped, one sample per file
 *   - .json       → either an array of {"title": ..., "body": ...} objects,
 *                   or one such object per file
 *
 * The practice account lets you tune voice-matching against a known,
 * distinctive voice before real users exist. Never seed in production.
 */
class CorpusSeeder extends Seeder
{
    public const PRACTICE_EMAIL = 'practice@tipsfromthegarden.test';

    public function run(): void
    {
        $corpusPath = database_path('corpus');

        if (! File::isDirectory($corpusPath)) {
            return;
        }

        $files = collect(File::files($corpusPath))
            ->filter(fn ($file) => in_array(
                strtolower($file->getExtension()),
                ['md', 'txt', 'html', 'htm', 'json'],
                true,
            ));

        if ($files->isEmpty()) {
            $this->command?->info('No corpus files found in database/corpus — skipping practice account.');

            return;
        }

        $user = User::fromEmail(self::PRACTICE_EMAIL, 'Practice Account');

        $count = 0;

        foreach ($files as $file) {
            foreach ($this->extractSamples($file->getPathname(), strtolower($file->getExtension())) as $sample) {
                if (mb_strlen(trim($sample['body'])) < 200) {
                    continue; // Too short to teach voice.
                }

                WritingSample::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'title' => $sample['title'],
                        'source' => WritingSample::SOURCE_SEED,
                    ],
                    [
                        'body' => trim($sample['body']),
                        'include_in_profile' => true,
                    ],
                );

                $count++;
            }
        }

        $this->command?->info("Seeded {$count} corpus sample(s) onto ".self::PRACTICE_EMAIL);
    }

    /**
     * @return array<int, array{title: string, body: string}>
     */
    protected function extractSamples(string $path, string $extension): array
    {
        $content = File::get($path);
        $fallbackTitle = Str::headline(pathinfo($path, PATHINFO_FILENAME));

        return match ($extension) {
            'json' => $this->fromJson($content, $fallbackTitle),
            'html', 'htm' => [[
                'title' => $this->htmlTitle($content) ?? $fallbackTitle,
                'body' => trim(preg_replace('/\s+/', ' ', strip_tags($content)) ?? ''),
            ]],
            default => [[
                'title' => $this->markdownTitle($content) ?? $fallbackTitle,
                'body' => $content,
            ]],
        };
    }

    protected function fromJson(string $content, string $fallbackTitle): array
    {
        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            return [];
        }

        // Single object or list of objects.
        $items = array_is_list($decoded) ? $decoded : [$decoded];

        return collect($items)
            ->filter(fn ($item) => is_array($item) && ! empty($item['body'] ?? $item['content'] ?? null))
            ->map(fn ($item) => [
                'title' => (string) ($item['title'] ?? $fallbackTitle),
                'body' => (string) ($item['body'] ?? $item['content']),
            ])
            ->values()
            ->all();
    }

    protected function markdownTitle(string $content): ?string
    {
        return preg_match('/^#\s+(.+)$/m', $content, $m) ? trim($m[1]) : null;
    }

    protected function htmlTitle(string $content): ?string
    {
        if (preg_match('/<title>(.*?)<\/title>/is', $content, $m)) {
            return trim(html_entity_decode($m[1]));
        }

        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $content, $m)) {
            return trim(html_entity_decode(strip_tags($m[1])));
        }

        return null;
    }
}
