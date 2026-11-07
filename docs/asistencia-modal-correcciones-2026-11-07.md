# Modal de asistencia: inscripciones y ocupación del docente

Cambios preparados exclusivamente en local a partir del reporte de la Persona #16740 e inscripción #13936. El usuario ejecutó dos diagnósticos de solo lectura en producción y confirmó que había seleccionado otra pestaña del modal. La clase no estaba ausente de la inscripción correcta. No se realizó ninguna conexión ni modificación de producción desde el agente.

El diagnóstico confirmó un único Estudiante #11995 y varias inscripciones con `vigente=1`, cinco de ellas en estado CORRIENDO. Solo la #13936 tenía clase el 07/11/2026: #128413, de 10:30 a 12:00, docente #69, aula #1, activa y habilitada. Su estado #6 es FINALIZADO, el estado #21 de la inscripción es CORRIENDO y no tiene clase adicional que haya consumido la original. La clase finalizada debe conservarse y mostrarse como tal; no se debe reabrir ni duplicar su entrada.

El caso del aviso «Sin clase hoy» queda identificado como selección de otra inscripción. Las mejoras de orden y contador permiten reconocer mejor las pestañas. El bloqueo concreto del docente no pudo atribuirse a una programación específica con estos diagnósticos; se conserva la regla confirmada y se mejoran sus mensajes para identificar un cruce autorizado cuando vuelva a ocurrir.

El usuario pidió mostrar únicamente pestañas de inscripciones activas o vigentes. Se comprobó que el filtro existente de ambos circuitos ya exige conjuntamente `vigente=1` y estado `CORRIENDO`, en concordancia con la validación de entrada. Se conserva ese filtro y se explica en el modal. En el diagnóstico cumplen ambas condiciones #11244, #11685, #12210, #13935 y #13936; las inscripciones que siguen marcadas así no se cierran ni ocultan por antigüedad automáticamente. Si alguna terminó realmente, corresponde revisar y cerrar su inscripción mediante el flujo existente, conservando el historial.

## Problemas reproducidos y corregidos

- Con varias inscripciones vigentes, el modal abría primero la más antigua aunque otra tuviera clase hoy. Ahora prioriza las pestañas con clases efectivas de hoy y muestra su cantidad, manteniendo separadas todas las inscripciones y matriculaciones.
- Las relaciones individuales `Persona::estudiante()` y `Persona::computacion()` podían dejar fuera inscripciones si la migración antigua había generado varios registros de ese papel para la misma Persona. La consulta del modal ahora recorre todas sus inscripciones/matriculaciones autorizadas mediante las relaciones existentes, sin fusionar, eliminar ni reasignar registros.
- Una clase original consumida por recuperación, adelanto o extra no se puede marcar nuevamente. Si corresponde a hoy y la clase efectiva está en otra fecha, se explica el vínculo en vez de anunciar simplemente que no hay clase.
- Las programaciones de inscripciones/matrículas que dejaron de estar vigentes o en curso podían bloquear la disponibilidad del docente o estudiante. Ya no reservan horario; las asistencias realmente registradas siguen comprobándose, aunque su inscripción esté inactiva.

## Regla de ocupación que se conserva

Actualmente se permiten varios estudiantes con un mismo docente en la misma aula y franja horaria, incluso de ambos circuitos. Se comprueba todo el intervalo de la nueva clase contra programaciones activas y asistencias, no solamente la hora en que se pulsa el botón.

El usuario confirmó que un docente debe estar en una sola aula por horario. Se conserva la incompatibilidad entre aulas diferentes y entre sucursales; no se ha relajado esa validación. También se conserva la comprobación de varios estudiantes en una misma aula.

La entrada rápida usa la hora actual más la duración de la clase, no necesariamente el intervalo programado. Para esta clase de 90 minutos, una entrada a las 11:00 termina a las 12:30 y puede coincidir con una reserva de otra aula a las 12:00. El botón «Usar horario programado» carga 10:30–12:00, para revisar el intervalo antes de confirmar; no se deben introducir horas que no correspondan a la asistencia real para eludir un cruce.

Se mejoró el error de ocupación: siempre muestra el horario solicitado. Para Admin/Secretaría con permiso de listado, añade el código de programación/asistencia, inscripción/matrícula, horario y aula del conflicto únicamente si ese registro y su inscripción son visibles en la Sucursal autorizada. Los cruces de otras sucursales o sin permiso de listado conservan un mensaje genérico, sin exponer sus códigos ni datos personales.

## Prueba local

1. Abrir el modal de una Persona con varias inscripciones vigentes, dejando una antigua sin clase hoy y otra con clase hoy. La segunda debe abrirse primero, con su código y contador de clases.
2. Comparar esa pestaña con el marcado general de la misma inscripción: deben utilizar la misma programación y fecha de Bolivia.
3. Revisar una clase de hoy que ya haya sido utilizada en una clase adicional de otra fecha: debe mostrarse el vínculo y conservarse el original.
4. Un docente y aula compartidos admiten varios estudiantes. Un cruce en otra aula/Sucursal continúa rechazándose. Una programación sin asistencia de una inscripción inactiva no debe bloquear.

Pruebas aisladas en SQLite en memoria:

```powershell
C:\xampp\php\php.exe vendor/phpunit/phpunit/phpunit -c phpunit.attendance.xml
C:\xampp\php\php.exe vendor/phpunit/phpunit/phpunit -c phpunit.user-access.xml
```

Resultado: 83 pruebas de asistencia/inscripciones y 59 de usuarios/seguridad, 142 pruebas y 1.037 aserciones en total. Incluyen trece regresiones nuevas para selección de pestaña, papeles duplicados, aislamiento por Persona/Sucursal, permisos, clases compensadas, programaciones inactivas, mensajes de conflicto sin filtración de datos restringidos y el caso confirmado de varias inscripciones con la clase de hoy finalizada.

Archivos modificados: `ClassAttendanceView.php`, `ClassAttendanceService.php`, `clase/asistencia/panel.blade.php`, `clase/modalmarcado_tabs.blade.php` y `ClassAttendanceTest.php`.

No requiere migraciones ni dependencias nuevas. No se ejecutaron commit, push ni despliegues; se conservaron los cambios previos de `.gitignore` y `resources/views/home/public/service.blade.php`.
