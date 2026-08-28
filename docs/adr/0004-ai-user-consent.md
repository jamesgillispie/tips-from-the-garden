# ADR 0004: AI User Consent & Opt-In

**Status:** Accepted and implemented  
**Date:** 2026-08-15  
**Decider:** James Gillispie

## Context

The app uses AI for transcription (whisper.cpp), article generation
(Ollama/Anthropic), and voice-profile summarization. Originally, every account
received the full pipeline automatically. There was no explicit consent,
per-feature control, audit flag, or periodic reminder.

Gardeners share identifiable speech and personal writing. Browser-only consent
is insufficient because queued jobs may run long after a request ends, while a
second bespoke consent modal would duplicate the existing privacy banner.

## Decision

We use database-backed, explicit AI consent with a federated browser/email UX.

### 1. Conservative default

`users.ai_opt_in` defaults to `false` for new and existing users. Registration
never implies consent. Every model-backed stage is gated by the master opt-in
and its granular preference.

whisper.cpp counts as AI even though it runs locally. A complete opt-out
therefore disables transcription as well as writing and voice learning.
Opted-out gardeners may still type or paste notes, which are saved and returned
without model processing.

### 2. One broad privacy surface

The existing `vanilla-cookieconsent` panel gains an **AI processing** category
alongside necessary cookies and analytics. It captures the broad first choice.
The database remains authoritative once a user is authenticated.

- Password registration carries a recorded banner choice in hidden fields.
- New Google accounts read the same first-party preference cookie after OAuth.
- Signed-in changes sync through authenticated, CSRF-protected
  `POST /privacy/consent`.
- An analytics-only change cannot broaden AI permission.
- Existing users with no decision are prompted on their next visit.

### 3. Granular account controls

Account Settings provides independent controls for:

- Transcription
- Journal entry writing
- Voice learning
- Voice-profile refresh threshold
- Transcript and pasted-sample inclusion

`User::canTranscribe()`, `canWriteArticles()` and `canLearnVoice()` are the
shared policy boundary used by intake, queue construction, jobs and UI.

### 4. Pipeline branching and safety checks

`SubmissionService` selects the chain from current settings:

```text
Recording + writing:       TranscribeAudio → WriteArticle → DeliverArticle
Recording, transcript only: TranscribeAudio → DeliverArticle
Pasted notes + writing:    WriteArticle → DeliverArticle
Pasted notes, no writing:  DeliverArticle
```

A recording is rejected before storage when transcription is off. Inbound
email attachments are likewise left untouched.

`WriteArticle` and `UpdateVoiceProfile` re-check permission when the queued job
starts. This prevents a later writing or voice-model call after opt-out. A
transcription process already executing is not forcibly killed.

### 5. Federated monthly check-in

The optional-cookie choice selects the reminder channel; it does not control AI
permission itself.

- Analytics accepted: re-open the browser privacy panel when due.
- Analytics declined: send `MonthlyAiCheckIn` on the monthly schedule.

Email links are temporary signed URLs. GET displays a confirmation page; only a
CSRF-protected POST changes state, preventing email scanners from opting a user
in or out.

Silence does not update `last_ai_check_in_at`, so reminders repeat when the next
monthly run occurs.

## Consequences

### Positive

- No model use is enabled by default.
- Queued work relies on durable account policy, not a browser cookie.
- Gardeners can choose transcript-only, writing without voice learning, or the
  complete experience.
- Typed notes remain useful with AI fully disabled.
- `submissions.ai_used` provides a per-submission audit signal.
- One privacy UI avoids competing consent prompts.
- Reminder delivery respects the user’s optional-cookie choice.

### Negative

- Audio cannot be transcribed after a complete opt-out; local inference is
  still AI and cannot honestly be presented as a non-AI fallback.
- Queue construction and delivery now have transcript-only branches.
- Browser and account state require explicit synchronization logic.
- A transcription job already running when consent changes may finish.
- Monthly check-in delivery requires both the scheduler and queue worker.

### Neutral

- Existing voice profiles and writing samples remain stored after opt-out but
  are not used while the relevant feature is disabled. Existing account/data
  deletion controls still remove them.
- Existing users are intentionally interrupted for a first decision after the
  migration.

## Alternatives Considered

1. **Global AI on/off only** — rejected because transcription, writing and voice
   learning have different data consequences.
2. **Permissive migration default** — rejected because it treats prior use as
   consent for future processing.
3. **A separate first-use modal** — rejected because it duplicates the existing
   privacy-choice surface.
4. **Browser cookie as the job authorization source** — rejected because queue
   workers cannot safely rely on mutable client state.
5. **State-changing GET links in email** — rejected because link scanners can
   follow them automatically.
6. **Continue whisper transcription after full opt-out** — rejected because
   whisper.cpp is a model-backed AI feature despite being self-hosted.

## Implementation Reference

See [AI Consent, Settings, Check-Ins & Output Labels](../ai-opt-in.md) for the
schema, routes, pipeline matrix, operations and test coverage.
