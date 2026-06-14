# Deploying & running Tips From The Garden

Operations runbook for hosting the app on the **always-on Mac mini**, edited
from the **laptop**. Read the next section first — almost all confusion comes
from not knowing *which machine* a command runs on.

---

## 🧭 The two machines (read this first)

There are **two separate copies** of the app. They each have their own `.env`,
their own database, and their own role. Code flows one way: laptop → mini.

```
🖥️  LAPTOP  (your dev machine)                🖧  MAC MINI  (the live host)
    ──────────────────────────                   ──────────────────────────
    Path:  /Users/jamesgillispie/                Path:  /Users/jdg/
           Websites/tips-from-the-garden                tips-from-the-garden
    Reach: it's right here                       Reach: ssh jdg@100.113.188.77  (Tailscale)
    Serve: HERD → tips-from-the-garden.test      Serve: php artisan serve (Step 4)
    Role:  WHERE YOU EDIT CODE                   Role:  WHAT GARDENERS ACTUALLY USE
    .env:  pgsql · ollama · log-mail (dev)        .env:  sqlite · Anthropic · whisper (live)
                                  │
                                  │   rsync  (the "deploy" — see Operations)
                                  └──────────────────────────────▶
```

**What this means in practice:**

- **The live site runs on the MINI.** SQLite, the Anthropic writer, whisper —
  that copy is what the Cloudflare tunnel will expose to a gardener.
- **The laptop copy is for development.** Herd serves it at
  `tips-from-the-garden.test` so you can build and test locally. Editing it
  changes *only* local dev.
- **Two different `.env` files.** Editing the laptop's `.env` (e.g. in your IDE)
  does **nothing** to the live site. To change live config you edit
  `.env` **on the mini** and re-cache (see the gotcha in Step 4b).
- **Herd Pro is a laptop-only dev tool.** The mini has no Herd and doesn't need
  it (see the note in Step 4c if you'd rather change that).

> **Convention below:** every command block is tagged **`# 🖧 ON THE MINI`** or
> **`# 🖥️ ON THE LAPTOP`**. "On the mini" means: `ssh jdg@100.113.188.77` first,
> then `cd ~/tips-from-the-garden`.

---

## TL;DR — what's left

1. [x] ~~Add the Anthropic API key~~ ✅ done
2. [x] ~~Prove a full memo→article round-trip~~ ✅ **verified (9s on the M4)**
3. [x] ~~Test from your phone (quick tunnel)~~ ✅ done
4. [x] ~~Make it permanent: php limits, production mode, background services~~ ✅ **done — 3 LaunchAgents live**
5. [x] ~~Give it a stable URL~~ ✅ **LIVE at https://journal.manorhousegardens.org**
6. [x] ~~Turn on real email (Postmark)~~ ✅ **done — outbound + inbound verified end-to-end**
7. [ ] (Optional) Set up the `/admin` back-office → **Step 7**
8. [ ] (Recommended) Cloudflare WAF skip rule for the webhook → **Step 8**

> **Current status:** fully live and working end-to-end — on-page recorder AND
> the `memos@manorhousegardens.org` email door both flow through transcribe →
> Claude → article, with real Postmark emails (delivery, magic-link, failure
> notices) going out. Replies go to `visit@` → your Yahoo. Remaining items are
> optional hardening (Step 8) and the admin console (Step 7).

---

## Current state (already done, on the mini)

| Thing | Status |
|---|---|
| Homebrew toolchain | ✅ php 8.5, composer, ffmpeg, whisper-cpp, cloudflared |
| App code | ✅ at `/Users/jdg/tips-from-the-garden` (rsync'd from the laptop) |
| PHP + JS dependencies | ✅ `composer install` + `npm run build` done |
| Database | ✅ SQLite, migrated, article template seeded |
| Whisper transcription | ✅ verified (~real-time on the M4) |
| **Anthropic writer** | ✅ **key added, full pipeline verified end-to-end** |
| Web app | ✅ verified booting; homepage, recorder, login all serve |
| Sleep | ✅ disabled — true always-on box |
| Public URL / tunnel | ⬜ not yet (Steps 3 & 5) |
| Background services | ⬜ not yet (Step 4) |
| Real email | ⬜ currently `log` driver (Step 6) |

### Paths & credentials (on the mini)

| | |
|---|---|
| SSH in | `ssh jdg@100.113.188.77` |
| App directory | `/Users/jdg/tips-from-the-garden` |
| PHP (use full path in scripts) | `/opt/homebrew/bin/php` |
| Composer | `/opt/homebrew/bin/composer` |
| Database | SQLite at `database/database.sqlite` |
| Whisper binary / model | `/opt/homebrew/bin/whisper-cli` · `/opt/homebrew/share/whisper-cpp/ggml-large-v3-turbo.bin` |
| ffmpeg | `/opt/homebrew/bin/ffmpeg` |
| App logs | `storage/logs/laravel.log` |

---

## Step 1 — Anthropic API key ✅ DONE

The key is in the mini's `.env` and the writer is working. For reference, to
change it later:

```bash
# 🖧 ON THE MINI
cd ~/tips-from-the-garden
sed -i '' 's|^ANTHROPIC_API_KEY=.*|ANTHROPIC_API_KEY=sk-ant-NEWKEY|' .env
php artisan config:clear     # (or config:cache if you've cached — see Step 4b)
```

Model is `claude-sonnet-4-6` (great voice-matching, pennies/article). For the
top model set `ANTHROPIC_MODEL=claude-opus-4-8`.

---

## Step 2 — Full pipeline test ✅ DONE

Verified: whisper transcribed locally → Claude wrote the article → 9s total. To
re-run anytime:

```bash
# 🖧 ON THE MINI
cd ~/tips-from-the-garden
say -o /tmp/memo.aiff "Out by the raised beds this morning, the Cherokee Purple \
tomatoes finally set fruit after that cold snap. The basil needs pinching back."
php artisan pipeline:run /tmp/memo.aiff --email=studio@jamesgillispie.com
```

Add `--deliver` to also send the article-ready email (goes to
`storage/logs/laravel.log` until Postmark is set up in Step 6).

---

## Step 3 — Test from your phone TODAY (free quick tunnel)

No domain or account needed. Gives a temporary public HTTPS URL so you can try
the in-browser recorder on a real phone. **The URL changes on restart — testing
only.** Run three things at once (use `tmux`, three SSH tabs, or the `nohup …&`
form):

```bash
# 🖧 ON THE MINI — terminal 1: the app
cd ~/tips-from-the-garden && php artisan serve --host=127.0.0.1 --port=8000

# 🖧 ON THE MINI — terminal 2: the queue worker (or nothing processes)
cd ~/tips-from-the-garden && php artisan queue:work --timeout=3600

# 🖧 ON THE MINI — terminal 3: the tunnel (prints a https://….trycloudflare.com URL)
cloudflared tunnel --url http://localhost:8000
```

Open the printed `https://….trycloudflare.com` URL on your phone, record a memo,
submit, and watch it flow (the "ready" email lands in `storage/logs/laravel.log`
until Step 6). Once this works, make it permanent ↓.

---

## Step 4 — Make it permanent (all on the mini)

### 4a. Raise PHP's upload limits (important!)

Homebrew PHP caps uploads at **2 MB** by default — a long voice memo gets
rejected. Raise it:

```bash
# 🖧 ON THE MINI
PHPINI=/opt/homebrew/etc/php/8.5/php.ini      # if the path differs, run `php --ini`
sed -i '' 's/^upload_max_filesize = .*/upload_max_filesize = 120M/' "$PHPINI"
sed -i '' 's/^post_max_size = .*/post_max_size = 120M/' "$PHPINI"
sed -i '' 's/^memory_limit = .*/memory_limit = 512M/' "$PHPINI"
```

### 4b. Switch to production mode

```bash
# 🖧 ON THE MINI
cd ~/tips-from-the-garden
sed -i '' 's/^APP_ENV=.*/APP_ENV=production/' .env
sed -i '' 's/^APP_DEBUG=.*/APP_DEBUG=false/' .env
php artisan config:clear   # see the gotcha below before you cache anything
```

> ⚠️ **Do NOT run `php artisan route:cache` on this app** — it has a closure route
> (the logout handler in `routes/web.php`), and route caching refuses to
> serialize closures. It will error. `config:cache` and `view:cache` are fine.
>
> ⚠️ **Cached-config gotcha:** if you *do* run `php artisan config:cache` (a perf
> win), then **`.env` edits do nothing** until you re-run `config:cache` (or
> `config:clear`). During this setup phase we're leaving config **uncached** so
> `.env` stays live-editable — `APP_DEBUG=false` already covers the security need.
> Cache it once everything (incl. Postmark) is final.

### 4c. Background services that survive reboots

Two LaunchAgents keep the **web server** and **queue worker** running and
auto-restart them. **Requires the mini to auto-login to the desktop** (System
Settings → Users & Groups → Automatic login) or they won't start after a reboot.

Create **`~/Library/LaunchAgents/com.tipsfromthegarden.web.plist`**:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0"><dict>
  <key>Label</key><string>com.tipsfromthegarden.web</string>
  <key>ProgramArguments</key><array>
    <string>/opt/homebrew/bin/php</string>
    <string>artisan</string><string>serve</string>
    <string>--host=127.0.0.1</string><string>--port=8000</string>
  </array>
  <key>WorkingDirectory</key><string>/Users/jdg/tips-from-the-garden</string>
  <key>EnvironmentVariables</key><dict>
    <key>PATH</key><string>/opt/homebrew/bin:/usr/bin:/bin:/usr/sbin:/sbin</string>
    <key>PHP_CLI_SERVER_WORKERS</key><string>4</string>
  </dict>
  <key>RunAtLoad</key><true/><key>KeepAlive</key><true/>
  <key>StandardOutPath</key><string>/Users/jdg/tips-from-the-garden/storage/logs/web.log</string>
  <key>StandardErrorPath</key><string>/Users/jdg/tips-from-the-garden/storage/logs/web.log</string>
</dict></plist>
```

Create **`~/Library/LaunchAgents/com.tipsfromthegarden.queue.plist`**:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0"><dict>
  <key>Label</key><string>com.tipsfromthegarden.queue</string>
  <key>ProgramArguments</key><array>
    <string>/opt/homebrew/bin/php</string>
    <string>artisan</string><string>queue:work</string>
    <string>--timeout=3600</string><string>--tries=3</string>
    <string>--sleep=3</string><string>--rest=1</string>
  </array>
  <key>WorkingDirectory</key><string>/Users/jdg/tips-from-the-garden</string>
  <key>EnvironmentVariables</key><dict>
    <key>PATH</key><string>/opt/homebrew/bin:/usr/bin:/bin:/usr/sbin:/sbin</string>
  </dict>
  <key>RunAtLoad</key><true/><key>KeepAlive</key><true/>
  <key>StandardOutPath</key><string>/Users/jdg/tips-from-the-garden/storage/logs/queue.log</string>
  <key>StandardErrorPath</key><string>/Users/jdg/tips-from-the-garden/storage/logs/queue.log</string>
</dict></plist>
```

Load them:
```bash
# 🖧 ON THE MINI
launchctl bootstrap gui/$(id -u) ~/Library/LaunchAgents/com.tipsfromthegarden.web.plist
launchctl bootstrap gui/$(id -u) ~/Library/LaunchAgents/com.tipsfromthegarden.queue.plist
launchctl list | grep tipsfromthegarden        # confirm both are listed
```

Controls for later: `launchctl kickstart -k gui/$(id -u)/com.tipsfromthegarden.queue`
(restart — do this after every deploy) · `launchctl bootout gui/$(id -u)/com.tipsfromthegarden.web` (stop).

> **Note on Herd:** the mini has no Herd, and the `php artisan serve` agent above
> is the simplest fully-headless way to serve it. You *could* instead install
> Herd on the mini to serve the site (nicer php-fpm, but it's a GUI app needing
> the desktop session) — if you do, skip the **web** plist above but still create
> the **queue** plist and still do the tunnel (Step 5). Herd doesn't run the
> queue worker or the tunnel for you. The cloudflared tunnel is installed as its
> own system service (Step 5), not as a LaunchAgent.

---

## Step 5 — Stable URL: domain + named tunnel

The quick tunnel (Step 3) changes URL on restart. For a permanent address — and
for the `memos@` email + Postmark webhook — you need a domain on Cloudflare and a
**named** tunnel.

1. **Domain onto Cloudflare** — register (~$10/yr) or move an existing domain's
   nameservers to Cloudflare; dashboard → add site.
2. **Create the tunnel** — Cloudflare **Zero Trust** dashboard → Networks →
   Tunnels → *Create a tunnel* → **Cloudflared** → name it `garden`. Copy the
   token it shows.
3. **Install it on the mini as a system service** (persists across reboots):
   ```bash
   # 🖧 ON THE MINI
   sudo cloudflared service install <TOKEN_FROM_DASHBOARD>
   ```
4. **Route a hostname** — in the tunnel's *Public Hostname* tab: add
   `garden.yourdomain.com` → service **HTTP** → `localhost:8000`.
5. **Point the app at the real URL:**
   ```bash
   # 🖧 ON THE MINI
   cd ~/tips-from-the-garden
   sed -i '' 's|^APP_URL=.*|APP_URL=https://garden.yourdomain.com|' .env
   php artisan config:cache
   ```

The app already trusts proxy headers (`bootstrap/app.php`), so HTTPS and signed
magic-login links work correctly behind the tunnel.

---

## Step 6 — Real email (Postmark) ✅ DONE

Configured and verified end-to-end. For reference, here's what's in place:

**Outbound** — Postmark SMTP, set in the mini's `.env`:
```ini
MAIL_MAILER=smtp
MAIL_HOST=smtp.postmarkapp.com
MAIL_PORT=587
MAIL_USERNAME=<Postmark Server API Token>      # same token for both
MAIL_PASSWORD=<Postmark Server API Token>
MAIL_FROM_ADDRESS="hello@manorhousegardens.org"
MAIL_REPLY_TO_ADDRESS=visit@manorhousegardens.org   # replies forward to your Yahoo
INBOUND_EMAIL_ADDRESS=memos@manorhousegardens.org   # shown on the homepage
POSTMARK_INBOUND_TOKEN=<secret matching the ?token= in the Postmark webhook URL>
```
(Domain `manorhousegardens.org` is DKIM + Return-Path verified in Postmark; those
records were added in Cloudflare DNS, MX/Email-Routing untouched.)

**Inbound (`memos@` door)** — a **Cloudflare Email Routing** rule forwards
`memos@manorhousegardens.org` → the Postmark inbound address; Postmark's inbound
webhook posts to `https://journal.manorhousegardens.org/webhooks/postmark?token=…`.
The app validates the token and runs the pipeline. Verified: real audio in →
article out → emails sent.

**Two Cloudflare gotchas hit during setup (both handled):**

1. **Email obfuscation** — Cloudflare's Scrape Shield was rewriting the on-page
   `memos@…` address into a `[email protected]` JS link. Fixed in the blade by
   wrapping it in `<!--email_off-->…<!--/email_off-->`, which tells Cloudflare to
   leave it alone. (Alternatively: Scrape Shield → Email Address Obfuscation → off.)
2. **Bot challenge on the webhook** — a programmatic POST with a `Python-urllib`
   user-agent got a 403 from Cloudflare's bot detection. Real Postmark POSTs use a
   normal UA + known IPs and pass fine, but see **Step 8** to make it bulletproof.

After any `.env` change here: `php artisan config:clear` (config is intentionally
left uncached — see Step 4b).

---

## Step 7 — (Optional) the `/admin` back-office

The gardener never needs this; it's your console for templates/submissions.
`/admin` currently 404s because Twill isn't installed:

```bash
# 🖧 ON THE MINI
cd ~/tips-from-the-garden
php artisan twill:superadmin    # create your admin login
php artisan config:cache
```

Then visit `https://journal.manorhousegardens.org/admin`.

---

## Step 8 — (Recommended) shield the webhook from Cloudflare bot rules

Inbound email depends on Postmark being able to POST to
`/webhooks/postmark`. It works today, but Cloudflare's Bot Fight Mode / WAF can
occasionally challenge automated POSTs. To guarantee Postmark is never blocked,
add a **skip rule** in the Cloudflare dashboard (zone `manorhousegardens.org`):

- **Security → WAF → Custom rules → Create rule** (or **Settings → Bots**):
  - **If** `URI Path equals /webhooks/postmark` (and optionally `Hostname equals
    journal.manorhousegardens.org`)
  - **Then** choose **Skip** → tick **All managed rules**, **Bot Fight Mode**, and
    **Security Level**.
- Deploy.

This leaves the rest of the site protected while letting the webhook through. The
app still secures the endpoint itself via the `?token=` secret, so skipping
Cloudflare's bot checks here is safe.

**Confirm inbound for real:** email a short voice memo from a phone to
`memos@manorhousegardens.org`, then on the mini watch it land:
`tail -f storage/logs/queue.log` and check `/admin` or the DB for a new
`source=email` submission going `received → ready`.

---

## Operations

### Deploy a code change (laptop → mini)

When you change code on the laptop, push it over and refresh the mini:

```bash
# 🖥️ ON THE LAPTOP — sync code (run from the project dir)
rsync -az -e ssh \
  --exclude='vendor' --exclude='node_modules' --exclude='.git' --exclude='.env' \
  --exclude='public/build' --exclude='storage/logs/*.log' \
  --exclude='storage/framework/cache/*' --exclude='storage/app/audio/*' \
  /Users/jamesgillispie/Websites/tips-from-the-garden/ \
  jdg@100.113.188.77:/Users/jdg/tips-from-the-garden/

# 🖧 then refresh the MINI (one command from the laptop)
ssh jdg@100.113.188.77 'cd ~/tips-from-the-garden && \
  /opt/homebrew/bin/composer install --no-dev -o --no-interaction && \
  /opt/homebrew/bin/npm ci && /opt/homebrew/bin/npm run build && \
  /opt/homebrew/bin/php artisan migrate --force && \
  /opt/homebrew/bin/php artisan config:cache && /opt/homebrew/bin/php artisan view:cache && \
  launchctl kickstart -k gui/$(id -u)/com.tipsfromthegarden.queue && \
  launchctl kickstart -k gui/$(id -u)/com.tipsfromthegarden.web'
```

> The IMPORTANT bit: **always restart the queue worker after a deploy**
> (`kickstart` above) — workers hold old code in memory until restarted.
>
> Longer term, consider committing to GitHub and deploying via `git pull` instead
> of rsync. The remote (`git@github.com:jamesgillispie/tips-from-the-garden.git`)
> exists, but **today's work is uncommitted on the laptop** — commit it when you can.

### Logs (on the mini)

```bash
# 🖧 ON THE MINI
tail -f storage/logs/laravel.log   # app errors
tail -f storage/logs/queue.log     # the pipeline worker
tail -f storage/logs/web.log       # the web server
```

### Health checks (on the mini)

```bash
# 🖧 ON THE MINI
launchctl list | grep tipsfromthegarden                            # services up?
curl -s -o /dev/null -w '%{http_code}\n' http://127.0.0.1:8000/    # app responding?
php artisan queue:work --once                                      # process one job by hand
```

### Troubleshooting

| Symptom | Look at |
|---|---|
| Submissions stuck at "Received" | queue worker down — `launchctl list \| grep queue`; `storage/logs/queue.log` |
| 500 in the browser | `storage/logs/laravel.log`; temporarily set `APP_DEBUG=true` + `config:clear` |
| ".env change did nothing" | config is cached — re-run `php artisan config:cache` |
| Upload rejected / fails | PHP upload limits (Step 4a); restart the web agent |
| Transcription fails | check `WHISPER_BINARY` / `WHISPER_MODEL` paths exist |
| Writing fails | API key/model in `.env`; `storage/logs/laravel.log` |
| Tunnel down | `sudo launchctl list \| grep cloudflared`; re-run service install |
| Nothing restarts after reboot | enable **automatic login** for the desktop user |

---

## Rough cost

| Item | Cost |
|---|---|
| Mac mini hosting | $0 (own it, already on) |
| Domain | ~$10/yr |
| Cloudflare Tunnel + Email Routing | free |
| Postmark | free tier (100 emails/mo ≈ 30–40 memos) |
| Anthropic API (Sonnet) | a few cents per article |

**≈ $1–2/month at testing volume, plus the domain.**
