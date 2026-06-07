# Tips From The Garden

Record a voice memo while you walk your garden. Send it in. Get back a polished
article written in your own voice, ready to download.

See `../ARCHITECTURE.md` for the full design. Stack: Laravel 12 · Twill 3.5 ·
Livewire 4 · whisper.cpp · Ollama → Claude API.

## First-time setup (Mac, Laravel Herd)

1. **Install Herd** — download from [herd.laravel.com](https://herd.laravel.com).
   It bundles PHP 8.3+, nginx, and Composer.

2. **Install the local model tooling:**

   ```bash
   brew install whisper-cpp ffmpeg ollama
   ollama pull qwen3:32b          # or whatever model you want to test with
   ```

   Download a whisper model (large-v3-turbo is the speed/quality sweet spot):

   ```bash
   mkdir -p /opt/homebrew/share/whisper-cpp
   curl -L -o /opt/homebrew/share/whisper-cpp/ggml-large-v3-turbo.bin \
     https://huggingface.co/ggerganov/whisper.cpp/resolve/main/ggml-large-v3-turbo.bin
   ```

3. **Install the app:**

   ```bash
   cd tips-from-the-garden
   composer install
   cp .env.example .env
   php artisan key:generate
   createdb -h 127.0.0.1 -U postgres tips-from-the-garden   # Postgres (Herd ships one)
   php artisan migrate
   php artisan twill:install      # creates Twill tables + your superadmin login
   php artisan db:seed            # article templates (+ corpus, if present)
   npm install && npm run build
   ```

4. **Park it in Herd** — add the parent folder in Herd, or `cd` here and run
   `herd link tips-from-the-garden`. The site comes up at
   `http://tips-from-the-garden.test`, admin at `/admin`.

5. **Run the queue** (the pipeline lives on it):

   ```bash
   php artisan queue:work --timeout=3600
   ```

## Try the pipeline immediately

No UI needed — point the artisan command at any audio file:

```bash
php artisan pipeline:run ~/Downloads/garden-memo.m4a --email=you@example.com
```

No Ollama or whisper.cpp yet? Test the plumbing with the fake drivers:

```bash
# in .env: TRANSCRIBER_DRIVER=fake / WRITER_DRIVER=fake
php artisan pipeline:run anyfile.m4a
```

## Swapping model tiers

Everything is driver-based (`config/pipeline.php`):

| .env | Local (dev) | Frontier (quality) |
|---|---|---|
| `TRANSCRIBER_DRIVER` | `whisper_cpp` | `whisper_cpp` (good enough to keep) |
| `WRITER_DRIVER` | `ollama` | `anthropic` (+ `ANTHROPIC_API_KEY`) |

## The two doors

**Web upload** — the homepage. Drop in an audio file + email.

**Email** — configure a Postmark inbound webhook pointing to
`https://<host>/webhooks/postmark?token=<POSTMARK_INBOUND_TOKEN>`. For local
testing, expose Herd with `herd share` (or ngrok) and use that URL. Gardeners
share a Voice Memo from their phone straight to your inbound address.

Emails in local dev go to `storage/logs/laravel.log` (`MAIL_MAILER=log`).
Want a real inbox-like view? `herd` ships with Mailpit — set
`MAIL_MAILER=smtp`, `MAIL_HOST=127.0.0.1`, `MAIL_PORT=2525`.

## The practice corpus

Drop scraped posts into `database/corpus/` (md, txt, html, or json), then:

```bash
php artisan db:seed --class=Database\\Seeders\\CorpusSeeder
```

They become writing samples on `practice@tipsfromthegarden.test` for tuning
voice-matching. The corpus folder is gitignored.

## Tests

```bash
php artisan test
```

Tests run with fake drivers — no models or network needed.

## Map of the code

```
app/Pipeline/        contracts, drivers (whisper.cpp, Ollama, Anthropic, fakes)
app/Jobs/            TranscribeAudio → WriteArticle → DeliverArticle → UpdateVoiceProfile
app/Services/        SubmissionService — both doors funnel through here
app/Livewire/        UploadForm, SubmissionStatus, Dashboard
app/Http/Controllers/Twill/   admin modules (articles, templates, submissions)
database/seeders/    article templates + corpus importer
```
