#!/bin/sh
# Ejecutar en el servidor después del respaldo, git pull y configuración de su .env.
set -eu

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
PROJECT_DIR=$(CDPATH= cd -- "$SCRIPT_DIR/../.." && pwd)
PHP_BIN=${PHP_BIN:-php}
COMPOSER_PATH=${COMPOSER_BIN:-}
cd "$PROJECT_DIR"

if [ -n "$COMPOSER_PATH" ]; then
  if ! command -v "$COMPOSER_PATH" >/dev/null 2>&1 && [ ! -f "$COMPOSER_PATH" ]; then
    printf '%s\n' 'No se encontró COMPOSER_BIN. Corrige su ruta o quita esa variable para detectar automáticamente.' >&2
    exit 1
  fi
elif command -v composer >/dev/null 2>&1; then
  COMPOSER_PATH=composer
elif [ -f "$PROJECT_DIR/composer.phar" ]; then
  COMPOSER_PATH="$PROJECT_DIR/composer.phar"
fi

run_composer() {
  case "$COMPOSER_PATH" in
    *.phar) "$PHP_BIN" "$COMPOSER_PATH" "$@" ;;
    *) "$COMPOSER_PATH" "$@" ;;
  esac
}

if [ -n "$COMPOSER_PATH" ]; then
  run_composer check-platform-reqs --no-dev
  # Leer la configuración real recién editada antes de tocar la base de datos.
  "$PHP_BIN" artisan config:clear
  run_composer dump-autoload --no-dev --optimize
else
  printf '%s\n' 'Composer no está en PATH. Comprobando las dependencias ya instaladas...'
  "$PHP_BIN" scripts/hosting/check-existing-vendor.php
  "$PHP_BIN" artisan config:clear
  "$PHP_BIN" artisan package:discover --ansi
fi
"$PHP_BIN" artisan migrate --force \
  --path=database/migrations/2024_03_17_153820_add_column_to_table.php \
  --path=database/migrations/2026_04_15_000007_add_gcontact_sync_queue_fields_to_personas_table.php \
  --path=database/migrations/2026_10_25_000001_create_google_contact_accounts_table.php \
  --path=database/migrations/2026_10_25_000002_extend_personas_google_contacts.php \
  --path=database/migrations/2026_10_25_000003_add_google_contacts_pause_controls.php \
  --path=database/migrations/2026_10_26_000001_extend_leads_google_contacts.php \
  --path=database/migrations/2026_10_26_000002_add_google_contact_photo_hash.php
"$PHP_BIN" artisan db:seed --class=GoogleContactsSeeder --force
"$PHP_BIN" artisan route:clear
"$PHP_BIN" artisan view:clear
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan queue:restart
"$PHP_BIN" artisan gcontact:check --production --allow-disconnected

printf '%s\n' 'Preparación finalizada. Conecta Google en AdminLTE y ejecuta gcontact:check --production antes de activar una única tarea Cron.'
