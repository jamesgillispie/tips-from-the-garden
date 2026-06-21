#!/usr/bin/env bash
# Deploy laptop → live Mac mini (https://journal.manorhousegardens.org).
# Mirrors the runbook in DEPLOYMENT.md → Operations → "Deploy a code change".
# Usage:  ./deploy.sh   (or:  sh deploy.sh)
set -euo pipefail

LAPTOP_DIR="/Users/jamesgillispie/Websites/tips-from-the-garden/"
MINI="jdg@100.113.188.77"
MINI_DIR="/Users/jdg/tips-from-the-garden/"

echo "==> 1/2  Syncing code to the mini…"
# Excludes are ANCHORED with a leading "/" so they only match the top-level
# dir. An unanchored "vendor" would also skip resources/views/vendor/ (the
# published mail components + email theme), which is exactly what broke a deploy.
rsync -az --stats -e ssh \
  --exclude='/vendor' --exclude='/node_modules' --exclude='/.git' --exclude='/.env' \
  --exclude='/public/build' --exclude='/storage/logs/*.log' \
  --exclude='/storage/framework/cache/*' --exclude='/storage/app/audio/*' \
  "$LAPTOP_DIR" "$MINI:$MINI_DIR"

echo "==> 2/2  Rebuilding + restarting services on the mini…"
ssh "$MINI" 'cd ~/tips-from-the-garden && \
  /opt/homebrew/bin/composer install --no-dev -o --no-interaction && \
  /opt/homebrew/bin/npm ci && \
  /opt/homebrew/bin/npm run build && \
  /opt/homebrew/bin/php artisan migrate --force && \
  /opt/homebrew/bin/php artisan optimize:clear && \
  /opt/homebrew/bin/php artisan config:cache && \
  /opt/homebrew/bin/php artisan view:cache && \
  launchctl kickstart -k gui/$(id -u)/com.tipsfromthegarden.queue && \
  launchctl kickstart -k gui/$(id -u)/com.tipsfromthegarden.web'

echo "==> Done. Live at https://journal.manorhousegardens.org"
