# AGENTS.md

This file provides guidance to Codex (Codex.ai/code) when working with code in this repository.

## What this is

Voice memos from the garden → polished articles in the sender's own voice.
A gardener records a voice memo, sends it in (web upload or email), and gets
back a written article. Laravel 12 · Twill 3.5 (admin) · Livewire 4 ·
whisper.cpp (transcription) · Ollama → Codex API (writing).

## Reference docs

- `ARCHITECTURE.md` (root) — the design vision and rationale: the two front
  doors, provider-abstraction reasoning, the corpus-for-structure /
  user-for-voice split (§6), local→cloud path, and build order. Read it for
  *why* decisions were made. It predates the implementation, so where it
  diverges from the code the **code is authoritative** — e.g. the inbound route
  is `/webhooks/postmark` (not `/webhooks/email-inbound`), `SubmissionService`
  does the intake instead of a `StoreSubmission` job, `Article.body_md` is
  Markdown (not Twill blocks), and the queue runs on the `database` driver.
- `design-system/` — scaffolding only right now (empty `project/theme-preview/`);
  no tokens or theme files committed yet.

## Commands

```bash
php artisan test                              # full suite (fake drivers, no network/models needed)
php artisan test --filter test_name           # single test
php artisan test tests/Feature/PipelineTest.php  # single file
vendor/bin/pint                               # format (Laravel preset, no pint.json)
npm run build                                 # build front-end assets (Vite + Tailwind v4)
npm run dev                                   # Vite dev server
php artisan queue:work --timeout=3600         # REQUIRED to process the pipeline (jobs run on the queue)
```

End-to-end manual run against a real audio file, synchronously, bypassing the queue:

```bash
php artisan pipeline:run path/to/memo.m4a --email=you@example.com [--deliver]
```

With `TRANSCRIBER_DRIVER=fake` / `WRITER_DRIVER=fake` in `.env`, `pipeline:run`
works on any file path without whisper.cpp or Ollama installed (it never reads
audio content with fake drivers — only the file must exist).

## Test environment notes

- `phpunit.xml` is self-contained: it sets `APP_KEY`, forces `fake` drivers,
  `sqlite :memory:`, and array cache/session. Tests do **not** depend on `.env`.
- The base `tests/TestCase.php` calls `withoutVite()` in `setUp()` so views that
  use `@vite` render without a built manifest. Keep new test cases extending it.
- A local `.env` must exist (copy from `.env.example`, then `php artisan key:generate`).
  Without it, phpdotenv emits a `file_get_contents(.../.env)` warning on every boot.

## Architecture

### Driver-based pipeline (the core abstraction)
Every model-touching stage is provider-agnostic and swapped by `.env` alone —
no code changes. Two contracts in `app/Pipeline/Contracts/`:

- `TranscriberContract` — `whisper_cpp` | `fake`
- `WriterContract` — `ollama` | `anthropic` | `fake` (also does `summarizeStyle()`)

`PipelineServiceProvider` binds each contract to a concrete driver via a `match`
on `config('pipeline.*')`. **To add a driver:** implement the contract, add a
`match` arm in `PipelineServiceProvider`, and add its config block in
`config/pipeline.php` + env keys. Drivers are resolved by the container and
injected into jobs — never `new` them directly.

### The job chain
`SubmissionService` builds a `Bus::chain` through the private `dispatchChain()`
helper (which attaches the `.catch → markFailed`). Audio submissions use the
full chain via `dispatchPipeline()`:

```
TranscribeAudio → WriteArticle → DeliverArticle   (.catch → markFailed)
                                       └─ dispatches → UpdateVoiceProfile
```

Pasted-transcript submissions skip transcription and chain
`WriteArticle → DeliverArticle` directly. Each job advances the `Submission`
status state machine (`received → transcribing → transcribed → writing → ready`,
or `failed`) via `markAs()` / `markFailed()`. A failure anywhere in the chain
marks the whole submission failed. `UpdateVoiceProfile` runs *after* delivery
and must never fail the submission (the article already shipped) — it logs and
moves on.

### Four intake doors, one funnel
Every door creates a `Submission` through `SubmissionService`, then dispatches a
chain:

- **In-browser recording** — `App\Livewire\UploadForm` (homepage `/`, default
  `mode = 'record'`) → `fromUpload(..., source: 'record')`. The Alpine
  `voiceRecorder` component (`resources/js/app.js`) records with MediaRecorder
  and pushes the clip into the Livewire `audio` property via `$wire.upload()`,
  so it flows through the same path as an uploaded file. Browsers record
  webm/mp4/ogg — all in `pipeline.audio.mimes`. `config/livewire.php` raises
  Livewire's 12 MB temp-upload default to match `AUDIO_MAX_SIZE_KB`.
- **Web upload** — same `UploadForm` (`mode = 'audio'`) → `fromUpload()`.
- **Pasted transcript** — same `UploadForm` (`mode = 'paste'`) →
  `fromTranscript()`. No audio, so `audio_path` is null (migration
  `..._make_audio_path_nullable_on_submissions`), the submission starts at
  status `transcribed`, and a `Transcript` with `transcriber = 'paste'` is
  created up front so the chain can skip `TranscribeAudio`.
- **Inbound email** — `PostmarkInboundController` (`POST /webhooks/postmark`) →
  `fromEmail()`. Attachment arrives base64-encoded. The webhook is CSRF-exempt
  (`webhooks/*` in `bootstrap/app.php`) and always returns 200 for
  parseable-but-unusable mail (no sender / no audio) so Postmark stops retrying;
  it returns 403 **only** on a bad `?token=` (checked against
  `services.postmark.inbound_token`). Mail without an audio attachment gets a
  `NoAudioFound` reply with attach instructions, unless the sender looks
  automated (no-reply/mailer-daemon/etc — loop protection).

### The pipeline never goes silent
`Submission::markFailed()` queues a `SubmissionFailed` email to the gardener on
the **first** transition to failed only — the chain `.catch` and each job's
`failed()` hook can both call it, so the guard lives in the model. Jobs are
retry-idempotent: `TranscribeAudio` upserts its transcript, `WriteArticle`
skips writing when an article already exists, and `DeliverArticle` uses
`delivered_at` as the don't-email-twice flag. The `ArticleReady` email contains
the full article body, not just a link.

### Identity & auth
Email is the only identity. `User::fromEmail()` find-or-creates the user and
their `VoiceProfile`. Login is passwordless magic-link (`MagicLinkController`,
signed URLs). Delivered articles are viewable/downloadable **without login** via
a random 40-char `download_token` (`/a/{token}`, `+/download/{md|pdf}`; PDF via
dompdf).

### Voice-matching loop
Each delivered transcript is banked as a `WritingSample`. Every N samples
(`pipeline.voice_profile.regenerate_every`) the writer's `summarizeStyle()`
regenerates the user's `VoiceProfile.profile_text`, which is then injected into
future article prompts. Prompt construction lives in the
`BuildsArticlePrompts` trait (system prompt = ghostwriter rules + template
structure + voice profile + a strict output format). Writers parse the model
output as `# title\n\n<markdown body>`.

### Templates
`ArticleTemplate` (Twill module) supplies the article's structure via
`structure_prompt` / `example_skeleton`. `ArticleTemplate::pick()` currently
returns the first active template; this is the intended seam for
content-aware template selection later.

### Admin
Twill at `/admin` (`php artisan twill:install` creates tables + superadmin).
Three modules registered in `routes/twill.php` / `config/twill-navigation.php`:
`submissions`, `articles`, `articleTemplates`. `Article` and `ArticleTemplate`
use Twill revisions; `Submission`, `Article`, `ArticleTemplate` extend the
Twill base `Model`, while plain models (`User`, `Transcript`, `VoiceProfile`,
`WritingSample`) extend Eloquent.

## Seeding the practice corpus
Drop scraped posts (md/txt/html/json) into `database/corpus/` (gitignored), then
`php artisan db:seed --class=Database\\Seeders\\CorpusSeeder` to load them as
writing samples on `practice@tipsfromthegarden.test` for voice-matching tuning.
