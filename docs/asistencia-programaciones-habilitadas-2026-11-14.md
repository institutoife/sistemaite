# Marcado de programaciones en estado HABILITADO

## Causa

El caso comunicado de la inscripción #13976 muestra la programación #128648 en estado `HABILITADO`. El botón de `/home` detecta que existe una programación hoy, pero el formulario solo reconocía `INDEFINIDO` como pendiente. Por eso mostraba «Conserva su estado histórico»; el servicio de guardado también rechazaba ese estado.

## Corrección

- `ClassAttendanceService` centraliza los estados pendientes: `INDEFINIDO` y `HABILITADO`, resueltos por nombre sin asumir IDs numéricos.
- El formulario compartido por el modal y las páginas de marcado abre el registro para esos estados y los presenta como «PROGRAMADA».
- El servicio permite guardar su entrada, conservando fecha, duración y demás validaciones existentes. Repetir la solicitud devuelve la asistencia existente sin duplicarla.
- El atributo `habilitado` que representa cobertura por pago se sigue validando por separado. Un nombre de estado `HABILITADO` no sustituye al pago ni activa una programación inactiva.
- Se conservan los bloqueos de licencias, faltas, clases congeladas, asistencias realizadas, clases consumidas, conflictos horarios y permisos. La vista identifica explícitamente una programación inactiva.
- Los mismos estados pendientes se utilizan en la selección de adelantos/extras, en la recuperación de clases pasadas sin asistencia y en la comprobación de superposiciones del estudiante entre apoyo y Computación.

No se convierten estados en bloque ni se reescriben programaciones antiguas. Al registrar una entrada válida se aplica la transición habitual a `PRESENTE`, con su auditoría.

## Archivos

- `app/Services/ClassAttendanceService.php`.
- `resources/views/clase/asistencia/panel.blade.php`.
- `resources/views/clase/asistencia/add.blade.php`.
- `tests/Feature/LegacyScheduledAttendanceTest.php`.
- `phpunit.attendance.xml`.

## Verificación local

```powershell
C:\xampp\php\php.exe vendor/phpunit/phpunit/phpunit -c phpunit.attendance.xml
```

Las pruebas utilizan SQLite en memoria, colas y HTTP simulados. Se añaden 28 casos que cubren ambos circuitos: formulario visible, guardado y repetición, conservación de horarios e historial, pagos, inactividad, estados históricos, permisos, conflictos, recuperación y consumo único mediante adelanto o extra.

Para probar personalmente, abrir en local una inscripción vigente con una clase de hoy activa, pagada y en estado `HABILITADO`. Debe mostrar «Confirmar entrada» en el modal y en su página de marcado. Una asistencia ya registrada debe permitir consultar su estado o marcar salida, sin ofrecer una segunda entrada.

La corrección fue desarrollada y probada en local. Pasaron las 153 pruebas de asistencia (1254 comprobaciones), incluidos los 28 casos nuevos. El usuario autorizó su publicación. Se excluyen los cambios ajenos de `.gitignore` y `resources/views/home/public/service.blade.php`.

## Aplicar en BanaHosting

Esta entrega parte de `8a9934a3b1ebaf4924adeeefc800c5421b0c8afa`. No requiere Composer, dependencias ni migraciones. Publicar el commit en Git no actualiza automáticamente ife.bo.

En la terminal de cPanel, ejecutar el bloque completo sustituyendo `COMMIT_PUBLICADO` por el hash comunicado al publicar:

```sh
(
set -e
cd /home/vaninquz/ife.bo/sistemaite
PHP_BIN=/opt/cpanel/ea-php82/root/usr/bin/php
IFE_VERSION=COMMIT_PUBLICADO

test "$(git branch --show-current)" = main
git diff --quiet
git diff --cached --quiet
git merge-base --is-ancestor 8a9934a3b1ebaf4924adeeefc800c5421b0c8afa HEAD
git fetch origin main
test "$(git rev-parse origin/main)" = "$IFE_VERSION"
git merge-base --is-ancestor HEAD origin/main

"$PHP_BIN" artisan down
git pull --ff-only origin main
test "$(git rev-parse HEAD)" = "$IFE_VERSION"
"$PHP_BIN" -l app/Services/ClassAttendanceService.php
"$PHP_BIN" artisan view:clear
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan view:cache
"$PHP_BIN" artisan queue:restart
"$PHP_BIN" artisan up

git rev-parse --short HEAD
echo 'Actualizacion completada.'
)
```

Los `error_log` sin seguimiento no impiden las comprobaciones. Si falla un comando, el bloque se detiene; si ya se activó mantenimiento, se conserva para revisar el error.

Actualizar el navegador con `Ctrl+F5` y abrir el modal de la inscripción #13976. Si su programación sigue siendo de hoy, activa, cubierta por pago y sin asistencia, debe mostrar «Confirmar entrada». Si ya se marcó, debe conservar la asistencia y ofrecer su salida o historial. Una clase de otro día debe gestionarse mediante el flujo de fecha o recuperación correspondiente.
