#!/bin/sh
# Actualización de la instalación existente: ejecutar después del respaldo y git pull.
set -eu
SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
PROJECT_DIR=$(CDPATH= cd -- "$SCRIPT_DIR/../.." && pwd)
PHP_BIN=${PHP_BIN:-php}
export PHP_BIN
cd "$PROJECT_DIR"

# Reutiliza la comprobación PHP/vendor y las migraciones incrementales de Google.
sh "$SCRIPT_DIR/deploy-google-contacts.sh"
"$PHP_BIN" artisan crm:prepare --force
"$PHP_BIN" artisan view:clear
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan queue:restart
printf '%s\n' 'CRM preparado. Acceda al menú CRM con Admin o Secretaria. Se conserva la tarea Cron existente de Google Contacts.'
