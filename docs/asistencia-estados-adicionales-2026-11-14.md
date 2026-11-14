# Estados de clases adicionales

Actualización de los estados de clases adicionales en inscripciones y matriculaciones de Computación.

## Comportamiento

- Una clase adicional sin entrada conserva INDEFINIDO. Programarla no cuenta como asistencia.
- Al registrar la entrada de hoy, queda PRESENTE y continúa en `/presentes` hasta marcar su salida real.
- Al finalizar, la programación efectiva queda RECUPERADA, ADELANTADA o EXTRA, según su vínculo en `clases_adicionales`.
- Una asistencia registrada para una fecha pasada recibe directamente ese estado en su programación efectiva.
- La asistencia individual de `clases` o `clasecoms` conserva PRESENTE / FINALIZADO para controlar entradas, salidas, alertas y disponibilidad del docente.
- Las clases normales continúan terminando en FINALIZADO.
- La programación original conserva fecha, estado y horas históricas. Se mantiene el detalle que identifica la original y la fecha efectiva.
- Las estadísticas, gráficos de clases terminadas y seguimientos reconocen los tres estados sin duplicar horas ni volver a mostrar ausencias resueltas.

## Migración

`database/migrations/2026_11_14_000001_preserve_additional_class_states.php`

Reutiliza los IDs de los estados existentes. Si faltan nombres en el catálogo, solo agrega esos nombres. No crea tablas ni altera columnas.

Corrige las programaciones efectivas de ambos módulos que tienen FINALIZADO y una asistencia real finalizada. Excluye programaciones originales consumidas, clases sin entrada y asistencias abiertas. Cada reclasificación deja un evento `estado_adicional_actualizado` con estado anterior y nuevo. No modifica asistencias, horarios, pagos ni registros de auditoría anteriores.

La migración puede repetirse sin duplicar estados ni eventos. Su reversión conserva el catálogo y la trazabilidad.

Para aplicarla **solo en local**, desde la carpeta del proyecto:

```powershell
C:\xampp\php\php.exe artisan migrate --path=database/migrations/2026_11_14_000001_preserve_additional_class_states.php
```

## Archivos modificados

- `app/Models/ClaseAdicional.php`: correspondencia entre tipo y estado final.
- `app/Services/ClassAttendanceService.php`: registro histórico, salida, correcciones y seguimiento.
- `app/Services/ClassAttendanceView.php`: contadores de asistencia y clases adicionales.
- `app/Http/Controllers/ProgramacionController.php` y `ClasecomController.php`: conteo de terminadas.
- `resources/views/clase/asistencia/panel.blade.php`: reconocimiento de las clases terminadas.
- La migración anterior, `tests/Feature/AdditionalClassStatesTest.php`, `tests/AttendanceTestCase.php` y `phpunit.attendance.xml`.

## Prueba manual local

1. En una inscripción activa con clases pagadas, registrar una recuperación, adelanto o extra, seleccionando su clase original.
2. Antes de marcar salida, comprobar PRESENTE y la presencia en `/presentes`.
3. Marcar salida. La clase desaparece de presentes y su programación muestra RECUPERADA, ADELANTADA o EXTRA, conservando el detalle de fechas y original.
4. Revisar que aumente Asistidas y el contador del tipo correspondiente. La recuperación atendida deja de figurar como pendiente.
5. Repetir en una matrícula de Computación y con una clase normal, que debe finalizar con FINALIZADO.
6. Programar una adicional futura sin entrada: debe seguir pendiente y permitir marcarla cuando llegue su fecha.
7. Tras la migración, revisar una adicional ya finalizada: debe mostrar su tipo y conservar la asistencia y sus horas reales.

## Verificación automatizada

```powershell
C:\xampp\php\php.exe vendor/phpunit/phpunit/phpunit -c phpunit.attendance.xml
```

195 pruebas y 1756 aserciones correctas. Incluyen ambos módulos, los tres tipos, marcado y salida repetidos, estadísticas, enlaces Ajax, correcciones, migración idempotente y reversión de una salida cuando falta un estado del catálogo. Base SQLite en memoria; sin acceso a producción.

## Actualización en BanaHosting

Publicación autorizada por el usuario. En la terminal del servidor, ejecutar cada línea y continuar si finaliza sin errores:

```bash
cd /home/vaninquz/ife.bo/sistemaite
git pull --ff-only origin main
ea-php82 artisan migrate --path=database/migrations/2026_11_14_000001_preserve_additional_class_states.php --force
ea-php82 artisan view:clear
```

Después, recargar el navegador con Ctrl+F5 y revisar una clase adicional terminada. Esta actualización no requiere instalar dependencias ni cambiar la configuración.
