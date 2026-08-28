# AI Consent, Settings, Check-Ins & Output Labels

**Status:** Implemented  
**Related decisions:** [ADR 0004](adr/0004-ai-user-consent.md), [ADR 0005](adr/0005-eu-ai-act-compliance.md)

## Purpose

Tips From The Garden uses models for three distinct jobs:

1. **Transcription** — whisper.cpp turns a recording into text on our server.
2. **Journal writing** — Ollama or Anthropic shapes transcript text into an article.
3. **Voice learning** — the configured writer summarizes selected text samples into style notes.

Every stage is off for a new or migrated account until the gardener explicitly
enables AI. Settings can be changed at any time, and queued writing/voice jobs
check current permission again before calling a model.

A complete opt-out disables transcription too. whisper.cpp runs locally, but it
is still AI; describing it as a non-AI fallback would be misleading. Gardeners
who opt out can continue to type or paste notes, which are saved and returned
without model processing.

---

## Consent Model

### Source of truth

The database is authoritative for authenticated accounts. Browser state is a
friendly consent UI, never authorization for a queued job.

`users` stores:

```text
ai_opt_in                 Master permission; false by default
ai_opted_in_at            First time AI was enabled
last_ai_check_in_at       Last explicit choice or re-affirmation
ai_preferences            Granular settings and check-in channel (JSON)
```

The normalized JSON shape is:

```json
{
  "transcription": true,
  "article_writing": true,
  "voice_learning": true,
  "voice_learning_threshold": 3,
  "included_samples": ["transcript", "paste"],
  "check_in_channel": "browser"
}
```

`check_in_channel` is `browser` or `email`.

### Privacy banner federation

The existing `vanilla-cookieconsent` panel is now the single broad privacy
surface, with these categories:

- **Strictly necessary** — sessions, CSRF, security and the preference cookie.
- **Analytics** — GA/GTM through Google Consent Mode.
- **AI processing** — broad permission for transcription, writing and voice learning.

The package stores its first-party `cc_cookie`. Laravel exempts that one cookie
from encryption because JavaScript creates it.

For registration, hidden form inputs carry an already-recorded banner choice to
`CreateNewUser`. Google registration reads the same cookie after OAuth. No
recorded choice means opt-out; registration itself never implies consent.

For signed-in accounts:

- `POST /privacy/consent` synchronizes an explicit banner interaction.
- The endpoint is authenticated, CSRF-protected and throttled.
- An analytics-only change cannot expand AI permission.
- Account Settings remains authoritative for granular controls.
- A Livewire event aligns the broad browser category after settings are saved,
  without flattening granular choices back to “all on.”
- Existing users with no decision are asked explicitly on their next visit.

### Granular Account Settings

`/account` contains an **AI & privacy settings** card between Password and the
Danger Zone. It provides:

- A master AI switch.
- Independent Transcription, Journal entry writing and Voice learning switches.
- A per-user voice-profile refresh threshold (1–20 samples).
- Controls for whether memo transcripts and pasted samples may shape the voice profile.
- A plain-language data-flow modal.

The helper methods used everywhere are:

```php
$user->canUseAi();
$user->canTranscribe();
$user->canWriteArticles();
$user->canLearnVoice();
$user->aiVoiceLearningThreshold();
$user->aiIncludedSampleSources();
```

---

## Pipeline Behavior

`SubmissionService` builds the queue chain from current user settings.

| Input and settings | Chain | Result |
|---|---|---|
| Recording; transcription + writing on | `TranscribeAudio → WriteArticle → DeliverArticle` | AI-assisted article |
| Recording; transcription on, writing off | `TranscribeAudio → DeliverArticle` | Transcript-only email and saved notes |
| Recording; transcription off | No submission | Recording is not stored or processed |
| Pasted notes; writing on | `WriteArticle → DeliverArticle` | AI-assisted article |
| Pasted notes; writing off | `DeliverArticle` | Notes saved and returned unchanged |

Inbound email follows the same rules. If transcription is off, the attachment is
left untouched, no submission is created, and `AiTranscriptionDisabled` explains
how to review settings.

### Defense in depth

- `WriteArticle` checks `canWriteArticles()` again before calling the writer.
- `UpdateVoiceProfile` checks `canLearnVoice()` again before banking or summarizing a sample.
- The existing voice profile is not injected into writing prompts while voice
  learning is disabled.
- `UpdateVoiceProfile` honors the user’s threshold and included sample types.
- `TranscribeAudio`, `WriteArticle` and voice summarization set
  `submissions.ai_used = true` when a model is invoked.

A transcription job already executing when a choice changes is not forcibly
killed. New submissions are blocked immediately, while queued writing and voice
stages re-check before model use.

### Transcript-only delivery

`DeliverArticle` now handles either an article or a transcript. `submissions.delivered_at`
prevents duplicate transcript emails, just as `articles.delivered_at` prevents
duplicate article emails. Transcript-only submissions finish in the normal
`ready` state and have a dedicated status screen.

---

## Monthly Re-Affirmation

The check-in channel follows the gardener’s optional-cookie choice:

- **Analytics accepted:** `browser`. When the account is due and the gardener
  returns, the privacy preferences panel reopens.
- **Analytics declined:** `email`. The scheduled command queues a monthly message.

This does not make analytics consent a condition of AI use. It only selects the
reminder channel.

### Schedule

`routes/console.php` runs:

```text
ai:send-check-ins
```

at 09:00 on the first day of each month. A user is due after
`AI_CHECK_IN_DAYS` (30 by default). Silence does not update the timestamp, so an
email-channel user receives another reminder the following month.

### Email actions

`MonthlyAiCheckIn` shows current settings and links to one of two signed pages:

```text
GET /ai/check-in/{user}/confirm
GET /ai/check-in/{user}/disable
```

GET only displays a confirmation page. The actual update is a CSRF-protected
POST to the same signed URL. This prevents mail-security link scanners from
changing settings merely by inspecting an email.

- **Keep these choices** updates `last_ai_check_in_at`.
- **Stop AI processing** disables all three stages and queues
  `AiProcessingStopped`.
- Signed links expire after `AI_CHECK_IN_LINK_DAYS` (14 by default).

---

## AI Output Transparency

Generated articles record:

```text
articles.is_ai_assisted = true
articles.ai_model       = writer identifier, such as fake:writer
```

Pre-migration articles are backfilled as AI-assisted because all existing
articles came through `WriteArticle`.

AI-assisted output carries a declaration in every shareable format:

- **Web:** official EU basic AI icon and “AI-assisted article” before the title.
- **HTML metadata:** `generator:ai-assisted`, `generator:ai-model`, and
  `ai-content-declaration` meta tags.
- **PDF:** visible icon/label near the title and an AI-assisted footer note.
- **Markdown:** visible blockquote plus a machine-readable HTML comment.
- **Article-ready email:** plain-language AI-assisted notice.

Non-AI articles are not labeled.

Official EU assets are stored under:

```text
public/icons/eu-ai-labels/
```

Both basic AI and future AI-generated/AI-modified variants are retained in SVG
and PNG formats. The web view uses SVG; PDF generation uses PNG.

---

## Schema Changes

Three migrations implement the feature:

```text
2026_08_15_000001_add_ai_settings_to_users_table.php
2026_08_15_000002_add_ai_tracking_to_submissions_table.php
2026_08_15_000003_add_ai_transparency_to_articles_table.php
```

New submission fields:

```text
ai_used       Audit flag set when any model is invoked
delivered_at  Idempotency guard for transcript-only delivery
```

New article fields:

```text
is_ai_assisted
ai_model
```

Existing users remain opted out after migration and receive an explicit browser
choice on their next visit.

---

## Operations

Environment defaults:

```dotenv
AI_CHECK_IN_DAYS=30
AI_CHECK_IN_LINK_DAYS=14
```

Useful commands:

```bash
php artisan migrate
php artisan ai:send-check-ins
php artisan ai:send-check-ins --force
php artisan schedule:list
php artisan test
npm run build
```

`pipeline:run` remains a developer-only explicit invocation of the full pipeline.
If its local test account has no AI permission, the command records that choice
and reports it before processing.

---

## Verification

Feature coverage lives in:

```text
tests/Feature/AiConsentTest.php
tests/Feature/AiCheckInTest.php
tests/Feature/AiTransparencyTest.php
```

The tests cover conservative defaults, browser synchronization, granular
settings, transcript-only degradation, job guards, inbound-email opt-out,
federated reminders, signed confirmation POSTs, registration carry-over, audit
fields, visual labels and downloadable declarations.
