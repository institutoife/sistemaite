# Presentes y avisos de salida — pruebas locales

La implementación queda únicamente en local. No requiere servicios de notificaciones externos ni un worker: consulta las asistencias autorizadas cada 30 segundos mientras la página está abierta, cada 60 segundos si la pestaña está oculta y con espera progresiva si falla la conexión. Los contadores usan el reloj del servidor y `America/La_Paz`, actualizándose cada segundo sin consultar la base de datos.

## Qué cambió

- `/presentes` reúne inscripciones, matriculaciones y clases adicionales; conserva separadas las asistencias aunque pertenezcan a la misma persona. Incluye sucursal, materia, docente, aula, horario, tiempos, foto, edición autorizada e historial.
- Se eliminaron las uniones obligatorias con autores que excluían registros migrados y duplicaban filas cuando había varios usuarios. Se incluyen asistencias pendientes de días anteriores; las finalizadas permanecen en el historial.
- Verde: más de 10 minutos restantes. Naranja: entre 10 minutos y la finalización. Rojo claro: primer minuto desde la finalización. Rojo oscuro: desde un minuto de retraso.
- AdminLTE muestra un botón de salidas con contador y un único aviso visible en todas sus pantallas autorizadas. Un solo modal agrupa los pendientes; no se abren ventanas automáticamente ni se repiten toasts.
- **Avisar al estudiante** registra usuario, fecha y hora sin finalizar la asistencia. **Posponer** permite 5, 10 o 15 minutos para el usuario actual; otro funcionario conserva sus propios avisos. Los avisos registrados se ven también por los demás usuarios autorizados.
- **Marcar salida** pide confirmación, usa POST con CSRF, registra usuario y fecha/hora reales y es idempotente bajo bloqueo de filas. Actualiza la página y el modal. Conserva el horario de la programación original; la auditoría muestra explícitamente la salida real, incluso si corresponde a otro día.
- Se conservan las restricciones de sucursal y permisos. Docentes consultan sus propias asistencias; Secretaría y Administración actúan con sus permisos existentes. Una sesión revocada deja de consultar y elimina los datos mostrados por el componente.

## Migración

`database/migrations/2026_11_03_000001_create_asistencia_salida_avisos.php` crea exclusivamente `asistencia_salida_avisos`, con avisos y posposiciones por usuario y asistencia. No modifica ni rellena tablas antiguas. La auditoría reutiliza `clase_asistencia_eventos`.

Esta migración ya se aplicó en el entorno local, verificando que los datos anteriores de personas, usuarios, clases, programaciones e inscripciones no cambiaran. No se ejecutó ninguna operación en producción.

## Prueba personal

1. Ingresar en `http://127.0.0.1:8000` con un usuario autorizado y seleccionar la Sucursal adecuada.
2. Marcar entrada desde el modal de asistencia de `/home` o desde el marcado de una inscripción/matrícula. Probar ambos circuitos y una recuperación, adelanto o clase extra pagada.
3. Abrir `/presentes`. Verificar datos, fotografía y acceso al historial. Buscar y filtrar; observar los contadores sin recargar.
4. Para probar rápidamente el vencimiento, usar una clase local cuyo horario termine en pocos minutos. En otra pantalla AdminLTE aparecerán el contador y el aviso al terminar el horario. Si se acaban de registrar entradas en otra pestaña, pueden tardar hasta 30 segundos en aparecer.
5. Abrir el aviso. **Avisar al estudiante** debe conservarlo presente y mostrar el aviso registrado en el historial. **Posponer 5 min** debe retirar el aviso activo para ese usuario y recuperarlo al vencer; cerrar y abrir la página no cancela la posposición.
6. Cancelar una confirmación de salida: no debe cambiar nada. Confirmarla: la asistencia desaparece de `/presentes` y de las alertas, y queda la salida real en el historial. Otra clase del mismo estudiante debe permanecer abierta.
7. Probar con Docente y con un usuario sin permisos; no deben poder consultar o modificar asistencias restringidas, ni alterando el identificador en una solicitud.

Con el navegador cerrado no hay avisos externos. Al volver a abrir una página autorizada, se recuperan las asistencias y posposiciones guardadas. No es necesario dejar abierta `/presentes`.

## Verificación automatizada

Desde PowerShell, en la raíz del proyecto:

```powershell
C:\xampp\php\php.exe vendor/phpunit/phpunit/phpunit -c phpunit.attendance.xml
C:\xampp\php\php.exe vendor/phpunit/phpunit/phpunit -c phpunit.user-access.xml
C:\xampp\php\php.exe vendor/phpunit/phpunit/phpunit -c phpunit.google-contacts.xml
```

Las pruebas usan SQLite en memoria y solicitudes externas interceptadas. `tests/Browser/render-presentes.php` genera una interfaz con datos ficticios en `storage/framework/testing/presentes-ui/`; `tests/Browser/presentes-ui.js` prueba colores, reloj, agrupación, aviso, posposición, confirmación, salidas simultáneas, respuesta atrasada y revocación. Se comprueba en escritorio y en 320/390 píxeles, además de otra pantalla del sistema.

## Archivos principales

- Backend: `PresentAttendanceController`, `PresentAttendanceService`, `AsistenciaSalidaAviso`, migración nueva y rutas. `ClassAttendanceService` conserva el cierre transaccional y amplía su auditoría. Los controladores de clases reutilizan la misma consulta de presentes.
- Interfaz: `resources/views/clase/presentes.blade.php`, parciales `clase/asistencia/exit-*`, `present-actions` e historial; `public/assets/js/presentes.js` y `public/assets/css/presentes.css`.
- Integración: página y barras de AdminLTE, `AppServiceProvider`; confirmación y POST en asistencia, estudiantes, mis estudiantes y pantallas anteriores de estados/presentes.
- Pruebas: fixture común `tests/AttendanceTestCase.php`, `PresentAttendanceTest`, ajuste de `ClassAttendanceTest`, suite de asistencia y comprobaciones de navegador.

Los cambios previos del usuario en `.gitignore` y `resources/views/home/public/service.blade.php` se conservaron. No se ejecutaron commit, push ni despliegue.
