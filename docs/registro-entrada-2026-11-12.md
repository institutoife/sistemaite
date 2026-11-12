# Registro de entrada: revisión local

Implementación desarrollada y probada en local. No requiere migraciones ni modifica datos históricos. Incluye el acceso académico entre sucursales. La publicación autorizada se aplica siguiendo `docs/despliegue-academico-2026-11-12.md`.

## Comportamiento

- El formulario completo permanece visible en el modal de `/home` y en las páginas de marcado de inscripciones y matrículas. Se retiraron el resumen duplicado, «Entrada rápida» de esa tarjeta y el desplegable de revisión.
- La tarjeta tiene borde turquesa, encabezado azul, programación, horario inscrito y estado «PROGRAMADA» cuando está pendiente. El objetivo se presenta como texto legible, decodificando entidades y espacios del editor sin modificar lo almacenado.
- La entrada se precarga con la hora de Bolivia y la finalización prevista respeta la duración inscrita. Cambiar la entrada recalcula la finalización. Los ajustes manuales y «Usar horario programado» se conservan; este botón solo llena campos. El reloj actualiza las horas sin editar mientras el formulario está visible.
- La programación conserva fecha, horas, docente, materia y aula originales. Su estado de asistencia se sigue actualizando mediante el flujo existente. Los valores efectivos se guardan en `clases` y `clasecoms`; al marcar salida se registra la hora real y el usuario mediante la auditoría existente.
- Después de guardar una entrada con más de 10 minutos de diferencia aparece un aviso azul (anticipada) o naranja (tardía), durante 8 segundos, con cierre manual. Se incluyen horario inscrito, entrada real y diferencia. Los accesos rápidos existentes fuera de la tarjeta también muestran el aviso al llegar a `/presentes`.
- Los docentes muestran aula y estudiantes sin salida, contando personas distintas de apoyo y Computación. El contador considera la ubicación física registrada en la asistencia o el aula; no utiliza la sucursal comercial de la inscripción. Los registros antiguos sin ubicación conservan su aula conocida. Si hay varias ubicaciones pendientes se muestran separadas.
- Los contadores se actualizan tras una entrada/salida y cada 30 segundos mientras el panel está visible. La consulta agrega ambas tablas sin cargar nombres de estudiantes y requiere permisos de gestión de asistencia; las cuentas familiares no pueden consultarla.
- Diferentes estudiantes pueden compartir docente, aula y horarios parcialmente superpuestos, materias y duraciones distintas. Se mantienen las validaciones de aulas/sucursales incompatibles. Un estudiante con asistencia activa sin salida debe cerrarla antes de comenzar otra, aunque haya vencido la finalización prevista o corresponda al día anterior.
- El resumen compacto muestra la descripción en un panel azul del 42% del ancho, ocho indicadores en dos filas y tarjetas de Monto, A cuenta y Saldo. Los importes requieren `Listar Pagos` o `Listar Pagoscomputacion`, según el circuito; también se acepta `Reporte Pagos`. Se utiliza el costo real, la suma de recibos y el saldo pendiente. Las cuentas sin permiso ven un aviso sin importes.

## Archivos de esta mejora

- `app/Services/ClassAttendanceService.php`: conservación de datos inscritos, aviso de llegada y control de asistencia activa.
- `app/Services/ClassAttendanceView.php`: objetivo legible y carga conjunta de docentes, aulas y ocupación.
- `app/Services/TeacherPresence.php`: nuevo agregado de personas presentes por docente, aula y sucursal.
- `app/Http/Controllers/ClaseController.php`, `ClasecomController.php`: aviso en guardado normal y rápido.
- `app/Http/Controllers/ClaseAdicionalController.php` y `routes/web.php`: consulta protegida de ocupación.
- `resources/views/clase/asistencia/panel.blade.php`, `fields.blade.php`: tarjeta completa, controles y etiquetas.
- `resources/views/clase/asistencia/summary.blade.php`: descripción, indicadores y resumen de pagos con permisos por circuito.
- `resources/views/clase/asistencia/arrival-notice.blade.php`, `public/vistas/clase/arrival-notice.js`: aviso temporal y recuperación después de redirecciones.
- `public/vistas/clase/asistencia.css`, `asistencia.js`: diseño adaptable, cálculo de horas y actualización de docentes.
- `resources/views/vendor/adminlte/page.blade.php`: estilos y aviso compartido.
- `resources/views/persona/estudiantes.blade.php`, `programacion/marcadoGeneral.blade.php`, `programacioncom/marcadoGeneral.blade.php` y `clase/asistencia/report.blade.php`: versión de archivo para evitar JavaScript antiguo en caché.
- `tests/Feature/AttendanceEntryTest.php`, `ClassAttendanceTest.php`, `tests/JavaScript/attendance-entry.test.cjs`, `phpunit.attendance.xml`: cobertura nueva y adaptación de pruebas a la salida obligatoria entre clases.

## Pruebas automatizadas

Se usan SQLite en memoria, HTTP simulado y colas simuladas; no se accede a producción ni se migra la base local de trabajo.

```powershell
C:\xampp\php\php.exe vendor/phpunit/phpunit/phpunit -c phpunit.attendance.xml
C:\xampp\php\php.exe vendor/phpunit/phpunit/phpunit -c phpunit.user-access.xml
C:\xampp\php\php.exe vendor/phpunit/phpunit/phpunit -c phpunit.google-contacts.xml
node tests/JavaScript/attendance-entry.test.cjs
```

Resultados finales satisfactorios: 125 pruebas de asistencia (995 comprobaciones), 72 de acceso (675 comprobaciones), 220 de CRM/Contactos (1628 comprobaciones) y 4 de JavaScript. Son 417 pruebas PHP y 4 de JavaScript. Los resultados de la entrega se registran en `storage/framework/testing/release-academico-*-2026-11-12.xml`. También se comprobaron sintaxis PHP/JavaScript y espacios del diff.

La inspección visual con el navegador no pudo completarse: la herramienta detuvo el acceso al no poder identificar con seguridad su dirección. No se realizaron acciones sobre la interfaz ni se modificó la configuración del navegador.

## Cómo probar personalmente

1. Abrir el sistema local y actualizar con `Ctrl+F5`. Desde `/home`, abrir asistencia de una inscripción vigente con clase hoy. Comprobar que se ven todos los campos sin desplegar nada y que el objetivo no muestra `&nbsp;`.
2. Probar una clase de 90 minutos: escribir entrada `14:20`; debe mostrar finalización `15:50`. Repetir con una clase de 60 minutos: `14:20` → `15:20`. Verificar que los ajustes manuales válidos se conservan.
3. Pulsar «Usar horario programado». Las horas deben cambiar sin registrar asistencia. Revisar docente, materia, aula y tema opcional, y pulsar «Confirmar entrada» cuando se quiera guardar.
4. Para horario inscrito `09:00`, probar con estudiantes distintos entrada `08:49` (aviso azul), `09:11` (naranja), `08:50` y `09:10` (sin aviso). El aviso debe cerrarse manualmente o desaparecer a los 8 segundos. Cada intento que se guarde crea una asistencia real de prueba; cerrar su salida antes de otra clase del mismo estudiante.
5. Marcar otros estudiantes con el mismo docente y aula, usando duraciones o materias diferentes. Comprobar el contador del selector; marcar sus salidas y verificar que baja. Cambiar el aula entre sucursales con ubicación registrada y revisar las etiquetas correspondientes.
6. Intentar una segunda entrada del mismo estudiante sin salida: debe explicar que hay una asistencia activa. Registrar primero la salida y volver a intentar una clase compatible, sin superposición.
7. Repetir desde `/programa/marcar/{id}` y desde el marcado de una matrícula de Computación. Revisar en `/presentes` y en el historial las horas efectivas; el horario inscrito de la programación debe conservarse.
