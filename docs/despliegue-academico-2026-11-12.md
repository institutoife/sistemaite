# Actualización de inscripciones, asistencia y resumen de pagos

Esta entrega parte de `1fda4ddc30e10db97bf008d1bc8580210128e61c`. Incluye el acceso académico entre sucursales, conservación del origen de inscripción, formulario de entrada siempre visible, horarios reales, avisos de llegada, ocupación de docentes y resumen compacto con Monto, A cuenta y Saldo. El panel azul ocupa el 42% del ancho en escritorio.

El resumen financiero utiliza `Listar Pagos` para apoyo o `Listar Pagoscomputacion` para Computación; también acepta `Reporte Pagos`. Una cuenta sin esos permisos ve un aviso de acceso, sin importes. Se mantienen los vínculos familiares y permisos de cada operación.

No hay migraciones nuevas ni cambios de dependencias. Composer se usa para regenerar el autoload de las clases y el trait incorporados, sin instalar ni actualizar paquetes.

Se excluyen los cambios ajenos de `.gitignore` y `resources/views/home/public/service.blade.php`.

## Aplicar en BanaHosting

Publicar en Git no actualiza automáticamente ife.bo. En la terminal de cPanel, ejecutar el bloque completo sustituyendo `COMMIT_PUBLICADO` por el hash completo indicado al publicar esta entrega. Usa las rutas verificadas de esta instalación y la copia habitual disponible en JetBackup 5.

```sh
(
set -e
cd /home/vaninquz/ife.bo/sistemaite
PHP_BIN=/opt/cpanel/ea-php82/root/usr/bin/php
IFE_COMPOSER=/home/vaninquz/.local/bin/composer.phar
IFE_VERSION=COMMIT_PUBLICADO

test "$(git branch --show-current)" = main
git diff --quiet
git diff --cached --quiet
git merge-base --is-ancestor 1fda4ddc30e10db97bf008d1bc8580210128e61c HEAD
test -f "$IFE_COMPOSER"
test -f vendor/autoload.php

git fetch origin main
test "$(git rev-parse origin/main)" = "$IFE_VERSION"
git merge-base --is-ancestor HEAD origin/main
printf 'Version anterior: %s\n' "$(git rev-parse HEAD)"

"$PHP_BIN" artisan down
git pull --ff-only origin main
test "$(git rev-parse HEAD)" = "$IFE_VERSION"

"$PHP_BIN" "$IFE_COMPOSER" dump-autoload --no-dev --optimize --no-scripts
"$PHP_BIN" -r 'require "vendor/autoload.php"; if (!class_exists(\App\Services\TeacherPresence::class) || !trait_exists(\App\Models\Concerns\HasEnrollmentOrigin::class)) { fwrite(STDERR, "Revise el autoload antes de continuar.\n"); exit(1); } echo "Clases nuevas disponibles.\n";'

"$PHP_BIN" artisan route:clear
"$PHP_BIN" artisan view:clear
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan view:cache
"$PHP_BIN" artisan queue:restart
"$PHP_BIN" artisan up

git rev-parse --short HEAD
echo 'Actualizacion completada.'
)
```

El bloque comprueba la rama, la versión y los archivos versionados antes de activar mantenimiento. Los `error_log` sin seguimiento no impiden esas comprobaciones. Si falla un comando, el bloque se detiene; si el sistema ya estaba en mantenimiento, se conserva ese estado para revisar el error.

## Comprobar en ife.bo

1. Actualizar el navegador con `Ctrl+F5`. Abrir el modal desde `/home` y comprobar la descripción amplia, indicadores y Monto/A cuenta/Saldo con una cuenta que pueda consultar pagos.
2. Revisar una inscripción de otra Sucursal: debe conservar su origen, mostrar todos sus pagos y permitir las acciones del rol sin cambiar la Sucursal activa del operador. Repetir con una matrícula de Computación.
3. En el siguiente marcado real, comprobar que el formulario está visible, la finalización corresponde a la duración individual y se actualizan los presentes del docente. Registrar salida antes de comenzar otra clase del mismo estudiante.
4. Comparar el resumen financiero con los recibos existentes. Las llegadas fuera de la tolerancia de 10 minutos muestran un aviso temporal y conservan el horario inscrito.

Las pruebas con datos ficticios, rechazos y cambios de permisos se realizan en local. La validación automática de esta entrega usa SQLite en memoria y servicios externos simulados.

Resultado final: 417 pruebas PHP satisfactorias (125 de asistencia, 72 de acceso y 220 de CRM/Contactos) y 4 de JavaScript. También pasaron la revisión de sintaxis de PHP/JavaScript y `git diff --check`.
