# Gap Analysis — Tips From The Garden

**Date:** 2026-07-19 · **Audited:** 58 PHP classes, 12 migrations, deploy + config
**Baseline:** commit `3236a0a`, 71 passing tests. Re-verified against `c085421` (the photos feature) before fixing — all five top findings still applied. Suite now **102 passing**.

**Verdict:** the architecture is sound and the code is clean — no hardcoded secrets, no TODO debt, FKs mostly well-constrained, tests green in 9s. The gaps are almost entirely *operational hardening*, not design. Five of them can bite you in production today.

---

## Fix now (before more traffic) — ✅ all five done on `harden/audit-fixes`

| # | Gap | Where | Why it bites | Status |
|---|-----|-------|--------------|--------|
| 1 | **Job timeout > queue `retry_after`** — no `config/queue.php`, so `retry_after` is the framework default 90s, while `TranscribeAudio` sets `timeout=3600` | `app/Jobs/TranscribeAudio.php:17` | The worker releases the job back to the queue at 90s while it's *still running*. Every transcription longer than 90s gets duplicated — double whisper runs, double Claude spend, possible double article. | ✅ Published `config/queue.php` with `retry_after` 3900 |
| 2 | **Webhook fails open** — token check is skipped entirely when the env var is blank | `PostmarkInboundController.php:27` | Drop `POSTMARK_INBOUND_TOKEN` in a deploy and the intake webhook is open to the internet. Same fail-open pattern in `Turnstile.php:18`. | ✅ Both fail closed + log critical; 3 new tests |
| 3 | **Prompt injection via transcript** — transcript is wrapped in `<transcript>` but never escaped | `BuildsArticlePrompts.php:58` | Paste mode (50k chars, no audio needed) can close the tag and issue its own instructions. Worse: `summarizeStyle()` has the same hole and its output persists to `voice_profiles.profile_text`, which is re-injected into *every future* article prompt. Persistent injection. | ✅ `fenced()`/`fencedSamples()` strip delimiters + data-not-instructions rule; 5 new tests |
| 4 | **No retry/backoff on model calls** — single `Http::post`, `tries=2`, no `$backoff` | `AnthropicWriter.php:50`, `WriteArticle.php:20` | One transient 429/529 burns both attempts back-to-back and emails the gardener a failure for a problem that would've cleared in 3 seconds. | ✅ `->retry(3, 2000)` on 429/5xx in both writers; `WriteArticle` now `tries=3` with `[60, 300]` backoff |
| 5 | **No CI** — no `.github/`, no scheduled tasks, no failed-job alerting | repo root, `routes/console.php` | 71 good tests that only run when someone remembers. `failed_jobs` fills silently. | ✅ `.github/workflows/ci.yml` (pint + tests + asset build); `queue:prune-failed` scheduled |

**One-time setup still needed for CI:** add `FLUX_USERNAME` and `FLUX_LICENSE_KEY` repository secrets (Settings → Secrets → Actions) — the values from your local `auth.json`. Without them `composer install` can't reach the private `flux-pro` repo and CI fails. The scheduled prune also needs `php artisan schedule:run` every minute on the mini.

---

## Fix soon

6. **Missing composite indexes.** `Dashboard.php:186-198` loads a user's articles/samples/memos with `->latest()` on every keystroke of the `#[Url] $search` property — and none of `articles`, `writing_samples`, or `submissions` has a `(user_id, created_at)` index. The search itself is a double leading-wildcard `LIKE` on `title` + `body_md` (unindexable full scan), and nothing is paginated. Also missing: unique index on `users.google_id` (nothing stops two users claiming one Google account), index on `writing_samples.include_in_profile`, index on `deleted_at` for the three soft-deleted Twill tables. — *2 hrs*

7. **Twill request classes under-validate.** `SubmissionRequest::rules()` returns `[]` — the fields are `->disabled()` in the form, but that's client-side only, so a crafted POST writes any `status` string straight through `$fillable`. `ArticleRequest` omits `download_token` while it *is* fillable, so an admin POST can pick an article's public URL. — *45 min*

8. **No transactions in intake.** `SubmissionService.php:31-92` writes file-then-row (orphan audio on DB failure) and submission-then-transcript (orphan submission stuck in `transcribed`). No audio cleanup anywhere. — *1 hr*

9. **Email intake has no size limit and no extension allowlist.** The web door enforces `pipeline.audio.max_size_kb`; the email door enforces nothing, and `SubmissionService.php:78` takes the extension from the client-supplied filename. That file then reaches ffmpeg's full demuxer surface with no `-protocol_whitelist`. — *45 min*

10. **`trustProxies(at: '*')`** trusts forwarded headers from any source, which makes `$request->ip()` spoofable and defeats the Turnstile `remoteip` check + any IP throttling. Pin to Cloudflare ranges. — *20 min*

---

## Test & ops gaps

- **No `database/factories/`.** This is *the* reason coverage clusters — every test hand-builds models. Adding factories is the highest-leverage test investment. — *2 hrs*
- **Zero coverage on:** `GoogleAuthController` (whole OAuth flow), `VerifyTurnstile` + `Turnstile` (and `phpunit.xml` blanks the secret, so the verify path short-circuits in every test), `UpdateVoiceProfile`, `SubmissionStatus`, all Twill controllers/requests, all three repositories, and every real pipeline driver (only `Fake*` is exercised).
- **`deploy.sh`** runs `migrate --force` with no `artisan down`, no backup, no rollback, and no post-deploy health check. A failed migration leaves the app half-deployed.
- **`auth.json` is rsynced to production — this turns out to be *correct*, not a leak.** `livewire/flux-pro` is a private repo in `require` (not `require-dev`), so `composer install --no-dev` on the mini genuinely needs those credentials. Leave the rsync alone. Optional tidy-up: move it to Composer's global config on the mini (`composer config --global http-basic.composer.fluxui.dev ...`) so the credential doesn't live in the app directory.
- **`.env.example` is missing ~13 vars** the config reads, notably `GTM_ID` and `AUDIO_MAX_SIZE_KB`.

## Frontend gaps

- **No `@error` display** in `dashboard`, `account-settings`, or `submission-status` blades. `AccountSettings` has 17 tests covering server-side validation whose messages the user never sees.
- **No empty state** on the dashboard — a brand-new gardener sees a bare list.
- Recorder error callouts (`upload-form.blade.php:88-107`) are `x-show`-toggled with no `aria-live`, so screen-reader users get no announcement when recording fails.

---

## Not problems (checked, came back clean)

- No hardcoded secrets; all via `env()`. No `.env` in git.
- No `TODO`/`FIXME` debt anywhere in `app/`, `config/`, `routes/`, `database/`.
- **No command injection** in `WhisperCppTranscriber` — `Process::run()` gets an array, so args are escaped.
- `users.email` unique constraint *is* present, which contains the email-change race.
- FKs are otherwise well done — explicit `cascadeOnDelete`/`nullOnDelete` throughout. (Two exceptions: `article_revisions.user_id` and `article_template_revisions.user_id` are bare `foreignId()` with no `constrained()`.)

---

## What's next

Items 1–5 are done. Remaining, in order:

1. **Factories** (`database/factories/` doesn't exist) — highest-leverage test investment, unblocks everything below.
2. **Backfill tests** for `GoogleAuthController` and `VerifyTurnstile` — both are auth-path code with zero coverage.
3. **Items 6–10** above (indexes, Twill validation, transactions, email-door limits, `trustProxies`).
4. **`deploy.sh` safety** — `artisan down`, a DB dump before `migrate --force`, and a post-deploy health check.
