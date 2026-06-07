# Testing Setup

How to configure `.env` and external systems (Postmark, Ollama, whisper.cpp,
mail) to test Tips From The Garden.

There are **3 intake doors** (paste, web upload, inbound email), **2 model
stages** (transcription, writing), and **email delivery**. You can test each
independently — pick a tier below.

> After any `.env` change, run `php artisan config:clear`.
> The **web** and **email** doors run on the queue — keep `php artisan queue:work`
> running. The `pipeline:run` artisan command runs synchronously (no worker needed).

---

## Tier 0 — Zero external setup (test the app flow today)

Exercises the **paste-transcript** and **web-upload** doors end-to-end with no
models, no Postmark, no API keys. Emails land in a log file.

```ini
TRANSCRIBER_DRIVER=fake
WRITER_DRIVER=fake
MAIL_MAILER=log
```

```bash
php artisan config:clear
php artisan queue:work          # REQUIRED — web/email doors run on the queue
```

- Visit `http://tips-from-the-garden.test` → **Paste text** tab (needs nothing)
  or **Upload audio** (any audio file; the fake driver ignores its contents).
- The "article ready" email is written to `storage/logs/laravel.log`.
- The public article link works without login.

CLI shortcut that bypasses the queue entirely (runs synchronously):

```bash
php artisan pipeline:run anyfile.m4a --email=you@example.com --deliver
```

---

## Tier 1 — Real local pipeline (actual transcription + writing)

### Transcription — whisper.cpp (currently NOT installed)

`ffmpeg` is present, but the binary and model are missing:

```bash
brew install whisper-cpp
mkdir -p /opt/homebrew/share/whisper-cpp
curl -L -o /opt/homebrew/share/whisper-cpp/ggml-large-v3-turbo.bin \
  https://huggingface.co/ggerganov/whisper.cpp/resolve/main/ggml-large-v3-turbo.bin
```

The `.env` paths (`WHISPER_BINARY=/opt/homebrew/bin/whisper-cli`,
`WHISPER_MODEL=...turbo.bin`) already match these — no edit needed once installed.

### Writing — Ollama (running, but configured model not pulled)

`.env` says `OLLAMA_MODEL=qwen3:32b`, but the only pulled model is `gemma4:31b`.
Pick one:

```bash
ollama pull qwen3:32b            # match the .env default, OR
```

…or change `.env` to what you already have:

```ini
OLLAMA_MODEL=gemma4:31b
```

### Enable

```ini
TRANSCRIBER_DRIVER=whisper_cpp
WRITER_DRIVER=ollama
```

```bash
php artisan config:clear
php artisan pipeline:run ~/path/to/real-memo.m4a --email=you@example.com
```

> To test the **paste door** with real writing only, leave
> `TRANSCRIBER_DRIVER=fake` and just set up Ollama — paste skips transcription.

---

## Tier 2 — Frontier writing (Claude API)

```ini
WRITER_DRIVER=anthropic
ANTHROPIC_API_KEY=sk-ant-...        # from console.anthropic.com
ANTHROPIC_MODEL=claude-opus-4-6
```

No package needed — it's a plain HTTP call. Run `php artisan config:clear` after.

---

## Email — seeing the outgoing articles

| Option | Setup | Where mail goes |
|---|---|---|
| **log** (current) | nothing | `storage/logs/laravel.log` |
| **Mailpit** (recommended for testing) | Herd ships it (not running on :8025 now) | web inbox |
| **Real Postmark delivery** | install package + token | real inboxes |

**Mailpit** — enable it in Herd, then:

```ini
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
```

**Real outbound via Postmark** — the transport package is **not installed**:

```bash
composer require symfony/postmark-mailer
```

```ini
MAIL_MAILER=postmark
POSTMARK_TOKEN=<your-postmark-server-token>
MAIL_FROM_ADDRESS="hello@yourverifieddomain.com"
```

---

## Tier 3 — Inbound email door (Postmark)

The only part needing a real external account. The webhook handler reads
`?token=` and compares it to `POSTMARK_INBOUND_TOKEN`.

1. **Create a Postmark account** → create a **Server**.
2. Open the server's **Default Inbound Stream**. Postmark gives you an inbound
   address like `<hash>@inbound.postmarkapp.com` (or configure a custom inbound
   domain / forwarding address — that's the address gardeners email). The
   homepage shows `memos@<your-host>` as display copy; update it once you have a
   real address.
3. **Expose your local site publicly** (Herd ships `expose`):

   ```bash
   expose share http://tips-from-the-garden.test
   ```

   Copy the `https://<something>.expose.host` URL it prints.
4. **Pick a shared secret** and set it in `.env`:

   ```ini
   POSTMARK_INBOUND_TOKEN=some-long-random-string
   ```

   ```bash
   php artisan config:clear
   ```
5. **Set the Inbound Webhook URL** in Postmark (Server → Settings → Inbound) to:

   ```
   https://<your-expose-subdomain>.expose.host/webhooks/postmark?token=some-long-random-string
   ```
6. **Test it** — email an audio attachment to the inbound address, or curl the
   webhook directly:

   ```bash
   curl -X POST "https://<sub>.expose.host/webhooks/postmark?token=some-long-random-string" \
     -H "Content-Type: application/json" \
     -d '{"FromFull":{"Email":"you@example.com"},"Attachments":[{"Name":"memo.m4a","ContentType":"audio/mp4","Content":"'"$(base64 -i ~/path/to/memo.m4a)"'"}]}'
   ```

   Expect `{"status":"queued",...}`. A wrong/missing token returns **403**; mail
   with no audio returns 200 `{"status":"no-audio"}` (by design, so Postmark
   doesn't retry). The **queue worker** must be running for inbound submissions
   to process.

---

## Quick reference: minimum to test each door

| Want to test | Drivers | External setup | Run |
|---|---|---|---|
| Paste door | `fake`/`fake` | none | `queue:work` + visit site |
| Upload door | `fake`/`fake` | none | `queue:work` + visit site |
| Real article quality | `whisper_cpp` or `fake` / `ollama` or `anthropic` | install whisper / pull model / API key | `pipeline:run` |
| Inbound email | any | Postmark account + `expose` tunnel + token | `queue:work` |

---

## Current-machine gotchas

- `OLLAMA_MODEL=qwen3:32b` **isn't pulled** — you have `gemma4:31b`. Real Ollama
  writing fails until you pull the model or change the env var.
- **whisper.cpp isn't installed** — audio transcription fails until Tier 1.
- The **paste door works with zero setup** regardless of the above.
