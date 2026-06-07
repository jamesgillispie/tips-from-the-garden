<?php

namespace Database\Seeders;

use App\Models\ArticleTemplate;
use Illuminate\Database\Seeder;

class ArticleTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'title' => 'Garden walk dispatch',
                'description' => 'The default: a rambling walk-the-garden memo becomes a warm, observational dispatch.',
                'structure_prompt' => <<<'PROMPT'
Open with a short seasonal or sensory hook drawn from the memo — what the
gardener noticed first, the weather, the moment. One or two sentences.

Then move through the garden the way the memo does: group related
observations into 2-4 sections with short, concrete subheadings. Within each
section, lead with the observation, then any lesson or tip the gardener drew
from it.

If the memo contains a "note to self" or task, fold it in naturally as a
forward-looking line ("next week I'll…").

Close with a single short paragraph that looks ahead — to the next task, the
next season, or what they're watching for. No summary, no moralizing.

Length: roughly 400-700 words, scaled to how much material the memo offers.
Never pad.
PROMPT,
                'example_skeleton' => <<<'SKELETON'
# [Title drawn from the most vivid observation]

[Seasonal/sensory opening — 1-2 sentences]

## [First area or theme]
[Observation, then the lesson or tip]

## [Second area or theme]
[Observation, then the lesson or tip]

[Forward-looking close — 1 short paragraph]
SKELETON,
                'published' => true,
            ],
            [
                'title' => 'How-to from the beds',
                'description' => 'For memos that explain a technique step by step (pruning, pest control, propagation).',
                'structure_prompt' => <<<'PROMPT'
Open with one or two sentences on why this task matters right now — the
problem it solves or the season that demands it, taken from the memo.

Then a short "what you need" line if the gardener mentioned tools or
materials. Skip it if they didn't.

Present the technique as a numbered sequence of steps, each step beginning
with the action. Keep the gardener's own tips, warnings, and asides attached
to the step they belong to — these asides are the personality, keep them.

Close with how to know it worked, or what to watch for afterward, if the
memo includes it.

Length: 300-600 words. Precision over flourish.
PROMPT,
                'example_skeleton' => <<<'SKELETON'
# [Task-focused title, e.g. "Dealing With Squash Bugs Without Spraying"]

[Why now — 1-2 sentences]

[Optional: what you need]

1. [First step, with the gardener's aside]
2. [Second step]
3. [Third step]

[How to know it worked]
SKELETON,
                'published' => true,
            ],
        ];

        foreach ($templates as $template) {
            ArticleTemplate::updateOrCreate(
                ['title' => $template['title']],
                $template,
            );
        }
    }
}
