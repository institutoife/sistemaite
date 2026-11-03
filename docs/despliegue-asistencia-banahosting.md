# Actualización de asistencia en ife.bo

Esta versión incorpora las mejoras de asistencia, recuperación, adelanto y Clase extra, además de la corrección de Sucursal al guardar inscripciones. Clase extra consume una clase programada y pagada como Adelanto; no crea otro cargo ni descuenta horas dos veces.

Publicar el código mediante Git no actualiza por sí solo la instalación de ife.bo. Los siguientes comandos se ejecutan en la terminal de cPanel, después del commit y push local.

## Antes de actualizar

- Comprobar que JetBackup tenga una copia reciente recuperable de archivos y base de datos.
- Ejecutar durante una pausa de atención: el sistema estará en mantenimiento mientras se actualiza.
- Conservar `.env`, `APP_KEY`, la base de datos, las fotografías, los secretos 2FA y el Cron existente de Google Contacts. Esta actualización no requiere cambiar sus valores ni copiar datos locales.
- No hay nuevas dependencias. Se utiliza el Composer ya instalado en `/home/vaninquz/.local/bin/composer.phar` para regenerar el autoload, sin `install` ni `update`.

## En la terminal de BanaHosting

Copiar el bloque completo. Cada comando ocupa su propia línea y el bloque se detiene ante un error.

```sh
(
set -e

cd /home/vaninquz/ife.bo/sistemaite
PHP_BIN=/opt/cpanel/ea-php82/root/usr/bin/php
export PHP_BIN

if [ "$(git branch --show-current)" != main ]; then
    echo 'La instalacion no esta en main. Revise antes de continuar.'
    exit 1
fi
if ! git diff --quiet || ! git diff --cached --quiet; then
    echo 'Hay cambios locales en archivos del servidor. Revise git status --short antes de continuar.'
    exit 1
fi
test -f /home/vaninquz/.local/bin/composer.phar

git fetch origin main
if ! git merge-base --is-ancestor HEAD origin/main; then
    echo 'El historial del servidor difiere de origin/main. Revise antes de continuar.'
    exit 1
fi

git rev-parse --short HEAD
"$PHP_BIN" artisan down
git pull --ff-only origin main
COMPOSER_BIN=/home/vaninquz/.local/bin/composer.phar sh scripts/hosting/deploy-asistencia.sh
git rev-parse --short HEAD
)
```

Los archivos `error_log` y `public/error_log` sin seguimiento no se eliminan y no bloquean esta comprobación. Si existe otro archivo sin seguimiento que impida el pull, Git lo indicará; no sobrescribirlo automáticamente.

El script comprueba producción, depuración desactivada y `https://ife.bo`; entra en mantenimiento; regenera el autoload; aplica solo las dos migraciones de esta versión; renueva cachés; verifica esquema, rutas y clases; reinicia los workers y habilita el sistema.

Migraciones incluidas:

1. `2026_11_02_000001_create_clases_adicionales`: crea los metadatos y la auditoría de clases adicionales.
2. `2026_11_02_000002_add_topic_to_clases_adicionales`: añade el tema opcional, relacionado con el catálogo existente.

Son incrementales: conservan personas, inscripciones, matrículas, clases, pagos e historiales. No se ejecutan otras migraciones pendientes, seeders, restablecimientos ni regeneraciones de claves. Las migraciones ya aplicadas se omiten al repetir el comando.

Si aparece un error después de entrar en mantenimiento, el script se detiene y mantiene esa condición. Conservar la salida del error para corregirlo; no ejecutar `migrate:fresh`, `db:wipe` ni una reversión que borre el historial. Después de resolver la causa, volver a ejecutar el script. No desactivar el mantenimiento de una actualización incompleta.

## Comprobación después de actualizar

1. Abrir `https://ife.bo/home` y recargar con **Ctrl+F5**. Iniciar sesión con la cuenta y el autenticador de producción.
2. Seleccionar una Sucursal autorizada y revisar el modal de asistencia, `/programa/marcar/{id}` y `/programacioncom/marcar/{id}` con una inscripción real autorizada para la prueba.
3. Comprobar tipo inicialmente vacío, selección de original, precarga, duración y Tema opcional con buscador.
4. En una operación real de Clase extra, seleccionar una programada pagada. Confirmar que conserva la duración, queda vinculada y no aumenta las horas contratadas ni genera otro cargo. Una original ya consumida no debe volver a estar disponible.
5. Revisar historial, reportes y guardado de inscripciones con Sucursal. Conservar el Cron de Google Contacts existente.

No crear pagos ni asistencias ficticias en producción para comprobar la interfaz. Las pruebas automáticas se realizaron con SQLite aislado y solicitudes externas simuladas.
