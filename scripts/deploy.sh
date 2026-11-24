#!/bin/bash
set -Eeuo pipefail

APP_DIR=/var/www/green-deahan-booking
WEB_USER=www-data
BACKUP_DIR="$HOME/backup-db"
ARTISAN="sudo -u $WEB_USER php artisan"

echo "=== Deploy dimulai: $(date) ==="

cd "$APP_DIR"

# Kalau ada langkah gagal setelah situs masuk mode maintenance, situs sengaja
# DIBIARKAN maintenance supaya pembeli tidak kena kode setengah jadi.
trap 'echo "!!! Deploy GAGAL di baris $LINENO. Situs masih mode maintenance. Perbaiki lalu: sudo -u '"$WEB_USER"' php artisan up"' ERR

echo "--- Backup database ---"
mkdir -p "$BACKUP_DIR"
envval() { grep -E "^$1=" .env | tail -n1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//"; }
DB_FILE="$BACKUP_DIR/$(envval DB_DATABASE)-$(date +%Y%m%d-%H%M%S).sql.gz"
MYSQL_PWD="$(envval DB_PASSWORD)" mysqldump --no-tablespaces --single-transaction \
    -h "$(envval DB_HOST)" -P "$(envval DB_PORT)" -u "$(envval DB_USERNAME)" "$(envval DB_DATABASE)" | gzip > "$DB_FILE"
test -s "$DB_FILE"
echo "Backup: $DB_FILE"
# simpan 14 backup terakhir
ls -1t "$BACKUP_DIR"/*.sql.gz 2>/dev/null | tail -n +15 | xargs -r rm --

echo "--- Mode maintenance ON ---"
$ARTISAN down --retry=60

echo "--- Pull kode terbaru ---"
git pull origin main

echo "--- Install PHP dependencies ---"
composer install --no-dev --optimize-autoloader --no-interaction

echo "--- Install dan build frontend ---"
npm ci
npm run build

echo "--- Jalankan migration ---"
$ARTISAN migrate --force

echo "--- Clear dan rebuild cache ---"
$ARTISAN optimize:clear
$ARTISAN config:cache
$ARTISAN route:cache
$ARTISAN view:cache
$ARTISAN event:cache

echo "--- Restart queue worker (pakai kode baru) ---"
$ARTISAN queue:restart

echo "--- Reload PHP-FPM ---"
sudo systemctl reload php8.4-fpm.service

echo "--- Mode maintenance OFF ---"
$ARTISAN up

echo "=== Deploy selesai: $(date) ==="
echo "Scheduler & queue worker jalan lewat cron (scripts/cron/green-deahan-booking),"
echo "tiap tick memakai proses baru dengan kode hasil deploy ini."
