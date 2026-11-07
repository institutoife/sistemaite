# Actualización de asistencia y confianza 2FA en BanaHosting

Esta entrega parte de `85899c62` e incluye los ajustes del modal de asistencia y la confianza independiente para cada cuenta que utiliza un mismo navegador. El usuario autorizó preparar la publicación el 7 de noviembre de 2026. Publicar el commit no actualiza por sí solo ife.bo: el bloque de servidor se ejecuta desde cPanel.

## Alcance

- Modal de asistencia: solo inscripciones/matrículas vigentes en estado CORRIENDO, prioridad para las que tienen clase hoy y contadores por pestaña. Incluye todos los registros del mismo estudiante migrado sin fusionarlos.
- Disponibilidad: las programaciones de inscripciones inactivas no reservan horario; las asistencias reales siguen comprobándose. Se conserva la regla de un aula/Sucursal por docente y horario, con mensajes más precisos según permisos.
- 2FA: cada cuenta conserva su cookie de confianza durante su plazo original de 30 días. Alternar cuentas o revocar una no borra la confianza de otra. La contraseña sigue siendo obligatoria.

No hay nuevas clases PHP, paquetes, recursos compilados ni migraciones. No es necesario ejecutar Composer ni `artisan migrate` para esta entrega. Se mantienen `.env`, APP_KEY, secretos TOTP, sesiones, registros históricos, fotografías y el Cron de Google Contacts.

Los cambios locales ajenos de `.gitignore` y `resources/views/home/public/service.blade.php` quedan fuera de esta publicación.

## Servidor

Aplicar sobre la instalación que ya tiene la entrega de `/presentes` (`85899c62`). Si el servidor está en otra versión, revisar primero las actualizaciones pendientes y sus migraciones. Usar el commit exacto comunicado después del push y tener localizada la copia recuperable habitual de JetBackup.

En la terminal de BanaHosting, dentro de `/home/vaninquz/ife.bo/sistemaite`, comprobar:

```sh
git status --short
git rev-parse --short HEAD
```

`error_log` y `public/error_log` sin seguimiento no bloquean esta actualización. Los cambios en archivos versionados deben revisarse antes del pull; no sobrescribirlos ni ejecutar un reset automático.

Ejecutar este bloque completo después de sustituir `COMMIT_PUBLICADO` por el hash exacto comunicado:

```sh
(
set -e
cd /home/vaninquz/ife.bo/sistemaite
PHP_BIN=/opt/cpanel/ea-php82/root/usr/bin/php
IFE_VERSION=COMMIT_PUBLICADO

test "$(git branch --show-current)" = main
git diff --quiet
git diff --cached --quiet
git fetch origin main

if [ "$(git rev-parse origin/main)" != "$IFE_VERSION" ]; then
    echo 'origin/main no coincide con la version indicada. Revise antes de actualizar.'
    exit 1
fi
git merge-base --is-ancestor HEAD origin/main
IFE_PREVIOUS=$(git rev-parse HEAD)
printf 'Version anterior: %s\n' "$IFE_PREVIOUS"

"$PHP_BIN" artisan down
IFE_FINISHED=0
trap 'if [ "$IFE_FINISHED" -ne 1 ]; then echo "Actualizacion interrumpida. Conserve el ultimo error para revisarlo antes de habilitar el sistema." >&2; fi' 0

git pull --ff-only origin main
test "$(git rev-parse HEAD)" = "$IFE_VERSION"
"$PHP_BIN" artisan route:clear
"$PHP_BIN" artisan view:clear
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan view:cache
"$PHP_BIN" artisan queue:restart
"$PHP_BIN" artisan up
IFE_FINISHED=1

git rev-parse --short HEAD
echo 'Actualizacion completada.'
)
```

Si un comando falla después de `artisan down`, el bloque se detiene y el sitio conserva el mantenimiento. Copiar el error para resolverlo; no continuar a ciegas ni ejecutar migraciones, restablecimientos de datos o regeneración de claves.

## Comprobación

1. Recargar ife.bo y abrir el modal de asistencia. Las pestañas muestran inscripciones vigentes/en curso; las que tienen clases efectivas hoy aparecen primero con su contador. No se cierran automáticamente inscripciones antiguas que siguen vigentes en la base.
2. En el navegador compartido, cada cuenta verifica su propio 2FA y marca confianza. Cerrar sesión al terminar y alternar cuentas: cada nuevo ingreso exige contraseña y conserva la confianza independiente. No borrar cookies ni usar una ventana privada para esta prueba.
3. Una cookie anterior válida se adapta al nuevo nombre de su titular sin ampliar el plazo. Si ya se perdió por el fallo anterior, esa cuenta necesitará verificar 2FA una vez más y aceptar confianza; después podrá alternar normalmente.

Verificación local previa: 375 pruebas y 2.890 aserciones correctas (72 usuarios/2FA, 83 asistencia y 220 CRM/Google Contacts), usando SQLite en memoria y solicitudes externas simuladas. También pasó `git diff --check`. No se ejecutan pruebas automatizadas ni datos de ejemplo en producción.

Detalles de los cambios: [2FA en equipos compartidos](2fa-equipos-compartidos-2026-11-07.md) y [modal de asistencia](asistencia-modal-correcciones-2026-11-07.md).
