#!/bin/sh
# Ejecutar en BanaHosting despues de git pull y con un respaldo reciente disponible.
set -eu

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
PROJECT_DIR=$(CDPATH= cd -- "$SCRIPT_DIR/../.." && pwd)
PHP_BIN=${PHP_BIN:-/opt/cpanel/ea-php82/root/usr/bin/php}
COMPOSER_BIN=${COMPOSER_BIN:-$HOME/.local/bin/composer.phar}
cd "$PROJECT_DIR"

if [ ! -f vendor/autoload.php ] || [ ! -f "$COMPOSER_BIN" ]; then
    printf '%s\n' 'No se encontro vendor/autoload.php o Composer PHAR. Revise COMPOSER_BIN antes de continuar.' >&2
    exit 1
fi

"$PHP_BIN" artisan config:clear
"$PHP_BIN" -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!$app->environment("production") || config("app.debug") || rtrim(config("app.url"), "/") !== "https://ife.bo") {
    fwrite(STDERR, "Revise APP_ENV=production, APP_DEBUG=false y APP_URL=https://ife.bo. No se realizaron migraciones.\n");
    exit(1);
}
echo "Configuracion de produccion verificada.\n";
'

"$PHP_BIN" artisan down
DEPLOY_FINISHED=0
trap 'if [ "$DEPLOY_FINISHED" -ne 1 ]; then printf "%s\n" "Actualizacion interrumpida. Se conserva el mantenimiento; revise el ultimo error antes de habilitar el sistema." >&2; fi' 0

# No hay nuevas dependencias: solo regenerar el autoload de las clases incorporadas.
"$PHP_BIN" "$COMPOSER_BIN" dump-autoload --no-dev --optimize --no-scripts
"$PHP_BIN" artisan migrate --path=database/migrations/2026_11_02_000001_create_clases_adicionales.php --force
"$PHP_BIN" artisan migrate --path=database/migrations/2026_11_02_000002_add_topic_to_clases_adicionales.php --force

"$PHP_BIN" artisan route:clear
"$PHP_BIN" artisan view:clear
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan view:cache

"$PHP_BIN" -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!\Illuminate\Support\Facades\Schema::hasTable("clases_adicionales")
    || !\Illuminate\Support\Facades\Schema::hasTable("clase_asistencia_eventos")
    || !\Illuminate\Support\Facades\Schema::hasColumn("clases_adicionales", "tema_id")
    || !\Illuminate\Support\Facades\Route::has("asistencia.adicional.store")
    || !\Illuminate\Support\Facades\Route::has("asistencia.temas")
    || !class_exists(\App\Services\ClassAttendanceService::class)) {
    fwrite(STDERR, "La comprobacion del esquema, rutas o clases no fue satisfactoria.\n");
    exit(1);
}
echo "Esquema, rutas y clases de asistencia verificados.\n";
'

"$PHP_BIN" artisan queue:restart
"$PHP_BIN" artisan up
DEPLOY_FINISHED=1
printf '%s\n' 'Actualizacion finalizada. Recargue con Ctrl+F5 y pruebe asistencia, Clase extra e inscripciones en ife.bo.'
