# Asistencia: ubicación efectiva del docente y solapamientos

El usuario reportó el rechazo del intervalo 07:30–09:00 por la programación #126446 de la inscripción #13671, aula #4, en Sucursal Central. Después aclaró que estaba intentando dos clases simultáneas del mismo estudiante para reproducirlo y definió que la ubicación del docente debe establecerse con la primera asistencia efectiva.

El diagnóstico de solo lectura ejecutado por el usuario en BanaHosting mostró que la programación, la inscripción y el aula no tienen filas en `sede_registros`. Por tanto, una atribución diferente de Sucursal en esos registros no explica el mensaje reportado. No se capturó el `aula_id` enviado por aquel formulario y no se modificaron sus registros.

## Causa identificada en la lógica

La validación anterior mezclaba programaciones pendientes con asistencias efectivas para determinar la ubicación del docente. Una programación de otro estudiante en otra aula podía bloquear la primera entrada, aunque todavía no se hubiese registrado ninguna asistencia allí. Además, comprobaba al docente antes de terminar las comprobaciones del estudiante en ambos circuitos, por lo que un estudiante duplicado podía recibir un error de aula/docente en vez de su propio solapamiento.

También comparaba cualquier atribución de Sucursal relacionada como si todas fueran ubicaciones efectivas. Una atribución histórica de la inscripción podía contradecir una asistencia explícitamente registrada en la Sucursal correcta.

Se reprodujeron los problemas en seis casos aislados cargando el servicio anterior de Git en un proceso de prueba, sin restaurarlo en el código de trabajo. Los mismos casos pasan con la corrección.

## Corrección local

- Se comprueban primero los solapamientos del estudiante en apoyo y computación, aunque las clases tengan docentes distintos. Sus programaciones pendientes siguen protegiendo su horario; cuando hay asistencia, se compara su intervalo efectivo, sin volver a reservar el intervalo antiguo de esa programación.
- La ubicación del docente se contrasta con asistencias efectivamente registradas, tanto presentes como finalizadas en el intervalo consultado. Una programación pendiente no establece otra ubicación física.
- La primera asistencia establece aula y Sucursal durante su intervalo. Las siguientes pueden compartirla sin límite artificial de un estudiante. Se comprueban todas las asistencias superpuestas: si un estudiante permanece después de finalizar la primera clase, su intervalo continúa protegiendo esa ubicación.
- La Sucursal explícita de la asistencia tiene prioridad. Solo si falta, se consulta como respaldo la programación, el aula y finalmente la inscripción/matrícula, en ese orden. La Sucursal desconocida de registros antiguos no se inventa ni se escribe en esos registros.
- Se conservan los intervalos abiertos en el extremo final: existe cruce cuando `inicio_existente < fin_solicitado` y `fin_existente > inicio_solicitado`. Terminar a las 09:00 y empezar otra clase a las 09:00 es compatible.
- Los errores distinguen estudiante, otra aula, otra Sucursal y una asistencia antigua sin aula. Para personal autorizado incluyen el código y horario de la asistencia real que bloquea, más el aula solicitada y la Sucursal activa. Los registros restringidos siguen sin revelar sus códigos.
- Se reutilizan las transacciones, bloqueos de Persona/docente y el registro idempotente existente. Las consultas de comprobación y de asistencia existente usan lecturas bloqueantes. Se conservan habilitación por pago, permisos, auditoría y vínculos de recuperación/adelanto/extra.

La corrección está centralizada en `ClassAttendanceService.php`; la reutilizan el modal de `/home`, el marcado de inscripciones, el de matriculaciones y las clases adicionales. No se crean sesiones grupales paralelas ni se fusionan asistencias: cada estudiante conserva su registro y sus horas.

## Prueba manual en local

1. Seleccionar Sucursal Central. Preparar dos estudiantes distintos con inscripciones/matrículas activas y clases habilitadas por pago. Marcar al primero con un docente y aula de 07:30 a 09:00. Las programaciones sin asistencia de otros estudiantes en otras aulas no deben bloquear esa entrada.
2. Marcar al segundo con el mismo docente, aula y Sucursal, por ejemplo de 07:45 a 09:15. Debe guardarse; repetir la solicitud no crea otra asistencia.
3. Intentar una entrada de otro estudiante con ese docente en otra aula o Sucursal de 08:00 a 09:00. Debe indicar la asistencia efectiva incompatible. Mientras exista la clase de 07:45 a 09:15, cambiar de aula a las 09:00 sigue siendo incompatible.
4. Intentar una segunda clase del mismo estudiante en un intervalo superpuesto, incluso con otro docente. Debe mostrar un error del estudiante, no atribuirlo al docente. Dos programaciones pendientes simultáneas de ese estudiante tampoco se aceptan como dos clases compatibles.
5. Probar un intervalo que empiece exactamente cuando termina el último intervalo del docente en la ubicación anterior, por ejemplo 09:15. Debe admitirse. Una clase que terminó antes utiliza su salida real; su final programado anterior permanece intacto.
6. Repetir con recuperación, adelanto y extra pagadas y revisar el historial. Corregir una adicional no debe permitir ubicar al docente en otra aula mientras sigue atendiendo a otros estudiantes.

No es necesario reproducir estas pruebas con la base real del servidor ni modificar fechas, estados o Sucursales históricas de #13671.

## Verificación

```powershell
C:\xampp\php\php.exe vendor/phpunit/phpunit/phpunit -c phpunit.attendance.xml
C:\xampp\php\php.exe vendor/phpunit/phpunit/phpunit -c phpunit.user-access.xml
```

Resultado final: **169 pruebas y 1.350 aserciones correctas**: 97 de asistencia (674 aserciones) y 72 de usuarios/seguridad (676). Se usan SQLite en memoria y solicitudes externas simuladas; no se accede a la base real local ni de producción. Se verificaron clases compartidas, aulas y Sucursales diferentes, pendientes que no fijan ubicación, intervalos parciales y adyacentes, salidas anticipadas, aislamiento por Persona, repeticiones y correcciones. También pasaron el análisis sintáctico PHP y `git diff --check`.

Archivos de esta corrección: `app/Services/ClassAttendanceService.php`, `tests/Feature/ClassAttendanceTest.php` y este documento.

No hay nuevas migraciones ni dependencias. La implementación y las pruebas se realizaron exclusivamente en local. Después de recibir el resultado, el usuario autorizó publicar esta corrección. Los cambios ajenos de `.gitignore` y `resources/views/home/public/service.blade.php` quedan fuera de la publicación.

## Aplicación en BanaHosting

El commit de esta corrección contiene únicamente el servicio, sus pruebas y este documento. Parte de `5780c1ee`, la entrega anterior de asistencia y confianza 2FA. Publicarlo en Git no actualiza automáticamente ife.bo.

Después del push, ejecutar el siguiente bloque completo en la terminal de cPanel, sustituyendo `COMMIT_PUBLICADO` por el hash completo comunicado. Comprueba la versión anterior, rechaza cambios en archivos versionados y verifica el commit remoto antes de activar mantenimiento. Los `error_log` sin seguimiento no bloquean esos controles.

```sh
(
set -e
cd /home/vaninquz/ife.bo/sistemaite
PHP_BIN=/opt/cpanel/ea-php82/root/usr/bin/php
IFE_VERSION=COMMIT_PUBLICADO

test "$(git branch --show-current)" = main
git diff --quiet
git diff --cached --quiet
git merge-base --is-ancestor 5780c1ee HEAD || {
    echo 'Revise primero la actualizacion anterior de asistencia y 2FA.'
    exit 1
}
git fetch origin main
test "$(git rev-parse origin/main)" = "$IFE_VERSION"
git merge-base --is-ancestor HEAD origin/main
printf 'Version anterior: %s\n' "$(git rev-parse --short HEAD)"

"$PHP_BIN" artisan down
git pull --ff-only origin main
test "$(git rev-parse HEAD)" = "$IFE_VERSION"
"$PHP_BIN" -l app/Services/ClassAttendanceService.php
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

Este bloque no ejecuta Composer, migraciones, cambios de credenciales, asignaciones de Sucursal, datos de prueba ni modificaciones de registros históricos. Si un comando falla, se detiene; si ya se activó mantenimiento, conservará ese estado. Revisar la salida antes de continuar.

Después, comprobar el marcado en una operación real con estudiantes distintos, docente, aula y Sucursal compatibles. Las pruebas de rechazos y datos de ejemplo descritas arriba se realizan en local.
