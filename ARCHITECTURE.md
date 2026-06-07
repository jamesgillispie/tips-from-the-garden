# Tips From The Garden — Architecture

A voice-to-article pipeline for gardeners. Record a voice memo in the garden, send it in with as little friction as possible, get back a polished article written in your own voice, ready to download.

Built for non-technical end users. Runs locally first (M5 Max), swaps to frontier models and cloud hosting later without structural change.

---

## 1. The two front doors

### Door A: Email (mobile, primary)
The gardener records in their phone's native Voice Memos app — battle-tested, offline-safe, survives locked screens and dead spots in the garden. When they're back on signal, they share the memo from the share sheet to a dedicated intake address (e.g. `memos@tipsfromthegarden.com`).

- Inbound mail handled by Postmark (or Mailgun) inbound webhook → `POST /webhooks/email-inbound`
- Sender's email address identifies the user; audio attachment is extracted and stored
- Reply email confirms receipt immediately, second email delivers the article link when ready

### Door B: Web upload (desktop, secondary)
A simple public page with a Livewire 4 upload form: drop in an audio file, enter your email, submit. Built-in Livewire file-upload progress. After submitting, the same page becomes a live status view (transcribing… writing… done) via polling — Livewire 4 islands keep the status region updating independently.

No live browser recording in v1. Web pages are a fragile place to hold the only copy of a recording; the native app already solves offline capture.

Both doors converge on the same thing: an audio file + an email address → one pipeline.

---

## 2. Stack

| Layer | Choice | Why |
|---|---|---|
| Framework | Laravel 12 | Queues, mail, storage, driver pattern all first-class |
| Admin CMS | Twill | James's cockpit: review/edit articles, manage templates and users. Never seen by gardeners |
| Public frontend | Livewire 4 | Upload form, live status, article view/download. Single-file components, islands, wire:transition |
| Transcription (local) | whisper.cpp, Metal, large-v3 | Fast and accurate on M5 Max; may never need swapping |
| Generation (local) | Ollama (OpenAI-compatible endpoint) | Dev/testing tier; model swappable via config |
| Generation (frontier) | Claude API | Quality tier, especially for voice-matching; enabled by `.env` change |
| Queue | Redis + Horizon (database driver fine for local dev) | Each pipeline stage is an isolated, retryable job |
| Inbound email | Postmark inbound webhook | Reliable parsing, good attachment handling |
| Storage | Local disk → S3-compatible in cloud | Laravel filesystem abstraction |

---

## 3. Data model

```
User            email (identity), name? — magic-link auth, no passwords
WritingSample   user_id, source (upload|paste|transcript|seed), title?, body, include_in_profile
VoiceProfile    user_id, profile_text (learned style notes), sample_count, updated_at
Submission      user_id, source (email|upload), audio_path, status
                (received|transcribing|transcribed|writing|ready|failed), error?
Transcript      submission_id, raw_text, duration, transcriber (driver + model), confidence?
Article         submission_id, user_id, title, body (Twill blocks), template_used,
                writer (driver + model), download_token, published_at?
ArticleTemplate Twill-managed: name, structure_prompt, example_skeleton, active
```

`Article` is a Twill module — drafts, revisions, and media handling come free. `ArticleTemplate` is also Twill-managed so article structures can be tuned in the admin without deploys.

---

## 4. The pipeline

Every stage is a queued job. Jobs are chained; each is small, retryable, and provider-agnostic.

```
[Email webhook]──┐
                 ├─→ StoreSubmission ─→ TranscribeAudio ─→ WriteArticle ─→ DeliverArticle
[Upload form]────┘                          │                  │               │
                                       Transcript          Article        email w/ link
                                                          (Twill draft)
```

1. **StoreSubmission** — persist audio to storage, create `Submission` (status: received), resolve or create `User` by email, send receipt confirmation (email door only).
2. **TranscribeAudio** — calls `TranscriberContract`. Status: transcribing → transcribed. Stores `Transcript`.
3. **WriteArticle** — calls `WriterContract` with: transcript + selected `ArticleTemplate` + user's `VoiceProfile` (if any). Creates `Article` as a Twill draft. Status: writing → ready.
4. **DeliverArticle** — emails the gardener a tokenized link to view/download. Optionally updates `VoiceProfile` from the new transcript (their actual spoken voice is the voice source — see §6).

Failure at any stage sets `status: failed` with a stored error, visible in Twill, retry button in admin.

---

## 5. Provider abstraction (the swap-out layer)

```php
interface TranscriberContract {
    public function transcribe(string $audioPath): TranscriptionResult;
}

interface WriterContract {
    public function write(WriteRequest $request): ArticleDraft;
    // WriteRequest = transcript + template + voice profile + metadata
}
```

Bindings live in `config/pipeline.php`, selected by `.env`:

```
TRANSCRIBER_DRIVER=whisper_cpp   # whisper_cpp | openai_whisper
WRITER_DRIVER=ollama             # ollama | anthropic
OLLAMA_BASE_URL=http://localhost:11434
OLLAMA_MODEL=qwen3:32b           # whatever's loaded
ANTHROPIC_MODEL=claude-opus-4-6
```

Drivers for v1:

- `WhisperCppTranscriber` — shells out to whisper.cpp binary with Metal; large-v3
- `OllamaWriter` — HTTP to Ollama's OpenAI-compatible endpoint (LM Studio also works here unchanged, since it speaks the same API shape)
- `AnthropicWriter` — Claude API; same contract, better prose

Local is the dev/plumbing tier. The moment article quality matters, flip `WRITER_DRIVER=anthropic`.

---

## 6. Voice and the Martha corpus

Two distinct ingredients, kept deliberately separate:

**Structure comes from the corpus.** The Martha blog scrape is mined for article anatomy, not language: how garden articles open (seasonal hook, anecdote), how task instructions are sequenced, typical lengths, how plant specifics are woven in, how they close. This distills into a handful of `ArticleTemplate` records — skeletons and structure prompts. Her words are never reused or imitated; her formats inform the scaffolding. This keeps the output non-derivative.

**Voice comes from the user**, via two inputs:

1. **Writing samples.** Users can paste or upload their own existing writing (blog posts, newsletters) as `WritingSample` records on their account. This seeds an accurate voice on day one for people who already write. Samples are toggleable (`include_in_profile`) so users control what informs their voice.
2. **Their own transcripts.** Every memo they submit is also a voice source — vocabulary, cadence, pet phrases, how they actually talk about their garden. Transcripts accumulate as samples automatically.

The `VoiceProfile` is a maintained style summary regenerated from active samples + transcript history, injected into the writer prompt. Users with no samples still get faithful, clean prose drawn from the transcript itself; voice-matching improves with use.

**Practice/dev workflow:** the Martha scrape doubles as test fixtures — seed her posts as `WritingSample` records (`source: seed`) on a test account, then run the pipeline against a known, distinctive voice to tune the voice-matching prompt before real users exist. Seed samples never ship to production accounts.

---

## 7. End-user experience (magic-link accounts)

Accounts exist, but passwords don't. Email address is identity; signing in means entering your email and clicking a link (Laravel signed URLs — no package required).

- Submitting stays login-free: the email door maps the sender address to an account (created on first contact); the upload form just asks for an email
- Article-ready emails carry a tokenized per-article link — readable and downloadable (PDF, docx, markdown) without logging in
- Logging in unlocks the dashboard: article library, writing samples (paste/upload, toggle which inform the voice profile), profile basics
- A first-time submitter gets an account implicitly; the delivery email invites them to sign in and add writing samples to improve their voice match

Twill remains admin-only: James reviews submissions and articles, edits drafts, tunes templates, monitors failures.

---

## 8. Local → cloud path

| Concern | Local (now) | Cloud (later) |
|---|---|---|
| Hosting | `php artisan serve` / Valet on M5 Max | Forge/Vapor/Cloud VPS |
| Transcription | whisper.cpp local binary | Same binary on server, or OpenAI Whisper API |
| Generation | Ollama | Claude API |
| Queue | database driver | Redis + Horizon |
| Storage | local disk | S3 |
| Inbound email | Postmark webhook → ngrok/Expose tunnel for local testing | Postmark webhook direct |

Nothing structural changes — every row is a config swap because of §5 and Laravel's native abstractions.

---

## 9. Build order

1. Laravel + Twill + Livewire 4 skeleton; models and migrations (§3)
2. Pipeline contracts, jobs, and local drivers (whisper.cpp + Ollama); artisan command to run the pipeline on a local audio file (testable before any UI exists)
3. Web upload door: Livewire form + status page + article view/download
4. Email door: Postmark inbound webhook + tunnel for local testing
5. Corpus mining → first `ArticleTemplate` set
6. Magic-link auth + dashboard (article library, writing samples); voice profiles (samples + transcripts → prompt injection); Martha-seeded test account for voice-matching practice
7. Frontier swap + deploy

## 10. Open questions

- Article output formats for v1: PDF + markdown, or docx too?
- Should James approve articles in Twill before delivery, or auto-deliver with admin visibility? (Auto-deliver suggested for v1; an approval toggle is cheap to add.)
- Submission limits / abuse guard on the public form and inbound email (rate limit by email + max audio duration?)
- Corpus mining approach: one-time analysis session producing templates, or a repeatable script?
