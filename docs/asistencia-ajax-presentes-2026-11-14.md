# Programación Ajax y verificación del marcado en presentes

## Error de las tablas de programación

Se reprodujo el fallo al crear un adelanto y consultar las tablas de programación. Las consultas de hoy e historial/futuro cargaban las relaciones de clases adicionales, pero omitían `inscripcione_id` o `matriculacion_id` de la proyección del modelo.

Al construir el botón «Asistencia e historial» de una clase original consumida o de su adicional, faltaba el parámetro de la ruta. La respuesta de DataTables contenía un error y no presentaba las filas. La tabla `tabla_hoy` usa estas consultas desde la página de marcado general.

La corrección conserva el código padre en las cuatro consultas de apoyo/Computación. Las consultas de hoy utilizan la fecha de Bolivia con comparación de fecha. Se mantienen los enlaces tanto del original como de la clase efectiva y los permisos de listado de cada módulo.

## Marcados que no aparecen en presentes

Las pruebas HTTP de esta corrección verifican que enviar el formulario normal con fecha de hoy crea una asistencia `PRESENTE`, aparece en `/presentes/estado` y solo desaparece al registrar su salida real. Se comprobó el comportamiento tanto en apoyo como en Computación, incluidos adelantos, extras y recuperaciones.

La consulta de solo lectura aportada por el usuario confirmó que la programación #129021 de la inscripción #14040 correspondía al 14/11/2026, estaba activa y cubierta por pago, pero no tenía filas en `clases`. Por tanto, en el momento de esa consulta no existía una entrada que pudiera aparecer en presentes. El dato no permite reconstruir por sí solo qué acción mostró «PRESENTE» anteriormente.

Se encontró una vía concreta para generar esa contradicción: los editores de programación permitían cambiar su estado a `PRESENTE` sin crear una asistencia. Ahora las ediciones validan el estado contra la asistencia real, dentro de una transacción con bloqueo de la programación. Poner `PRESENTE` requiere una entrada existente; cambiar el estado de una asistencia en curso requiere registrar primero su salida. El rechazo no deja observaciones ni modificaciones parciales. Los estados del formulario antiguo enviados por nombre también se resuelven a sus IDs válidos.

Si un registro antiguo ya indica `PRESENTE` sin una entrada, el modal lo explica expresamente. No se fabrica una asistencia ni se reabre un registro histórico de forma automática.

Los formularios normales de apoyo y Computación precargan horas en formato `HH:mm`, utilizan pasos de un minuto y conservan los datos introducidos al fallar la validación. Sus errores son visibles, sus campos obligatorios están identificados y cambiar el inicio recalcula la finalización mediante el JavaScript de asistencia existente. La redirección posterior a un guardado nativo muestra la confirmación con el número real de asistencia; los registros finalizados indican fecha y estado.

También se corrigen la ambigüedad de `fecha` en el orden del historial del formulario normal y la lectura de los nombres de horas enviados por el editor de Computación. Los editores de las páginas generales muestran el mensaje de validación del servidor.

## Validación

Pasaron 169 pruebas de asistencia e inscripciones (1478 comprobaciones). Los dieciséis casos de integración nuevos cubren seis escenarios de clases adicionales en las tablas de programación, dos recorridos completos desde el formulario real hasta entrada/salida, dos controles de permisos por circuito, cuatro casos de edición de estados y dos estados antiguos sin asistencia real. El recorrido de formularios verifica horas con segundos del servidor, error visible, conservación de datos, guardado, repetición y salida.

Se comprobaron además la sintaxis PHP, el JavaScript embebido de las páginas modificadas y `git diff --check`.

## Archivos modificados

- `app/Http/Controllers/ProgramacionController.php` y `ProgramacioncomController.php`: consultas Ajax, horarios precargados y edición coherente de estados.
- `app/Services/ClassAttendanceService.php`: validación reutilizable del estado editado.
- `app/Http/Controllers/ClaseController.php` y `ClasecomController.php`: confirmación del guardado nativo.
- `resources/views/clase/form.blade.php`, `clasecom/form.blade.php`, `clase/create.blade.php` y `clasecom/create.blade.php`: validación visible, datos anteriores y horas compatibles.
- `resources/views/clase/asistencia/panel.blade.php` y `clase/presentes.blade.php`: advertencia de estado incoherente y confirmaciones.
- `resources/views/programacion/marcadoGeneral.blade.php` y `programacioncom/marcadoGeneral.blade.php`: errores de edición explicados.
- `tests/Feature/AttendanceRoutingTest.php` y `phpunit.attendance.xml`: integración y regresiones.

```powershell
C:\xampp\php\php.exe vendor/phpunit/phpunit/phpunit -c phpunit.attendance.xml
```

Las pruebas usan SQLite en memoria y servicios externos simulados. El usuario autorizó publicar esta corrección. Se excluyen los cambios ajenos de `.gitignore` y `resources/views/home/public/service.blade.php`.

## Actualización en BanaHosting

La entrega parte de `4beef03c7f1ea5c295bd483d3e1d28aa1c3531d6`. No agrega clases de aplicación ni dependencias y no requiere Composer o migraciones. La actualización de archivos y cachés se aplica desde la terminal de cPanel después de publicar el commit:

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
test "$(git rev-parse origin/main)" = "$IFE_VERSION"
git merge-base --is-ancestor HEAD origin/main

"$PHP_BIN" artisan down
git pull --ff-only origin main
test "$(git rev-parse HEAD)" = "$IFE_VERSION"
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

Sustituir `COMMIT_PUBLICADO` por el hash completo comunicado. Los cambios versionados locales del servidor detienen la actualización; los `error_log` sin seguimiento no la bloquean. Un fallo detiene el bloque y conserva el mantenimiento si ya estaba activo.

Después de actualizar con `Ctrl+F5`, comprobar que la programación de una inscripción con adelanto carga sus filas sin error Ajax. En el siguiente marcado real, confirmar que aparece el número de asistencia y que la entrada figura en `/presentes`. Seleccionar un estado en el editor de programación no sustituye el marcado de entrada o salida. Los registros antiguos incoherentes muestran su advertencia para revisión; no se crean asistencias automáticamente.
