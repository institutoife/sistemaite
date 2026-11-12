# Inscripciones y matriculaciones compartidas entre sucursales

## Comportamiento

El personal autorizado consulta y gestiona inscripciones académicas y matriculaciones de Computación de todas las sucursales, sin cambiar la Sucursal activa. Se mantienen los permisos de listado, creación, edición, pagos y asistencia, así como autenticación, estados de cuenta, horarios de acceso y 2FA. Padres y estudiantes conservan exclusivamente el acceso familiar existente.

La Sucursal original se muestra junto al estado en la misma celda y en las tarjetas móviles. No se agrega una columna. Se obtiene de la atribución existente en `sede_registros`, enlazada con `crm_sedes`. Si un registro migrado no tiene atribución, aparece **Sin registrar**; no se asigna una Sucursal ficticia. Las atribuciones antiguas se conservan incluso si la Sucursal dejó de estar activa.

Al crear una inscripción o matrícula se puede indicar cualquier Sucursal activa como origen. Este dato no cambia la Sucursal seleccionada del usuario ni sus asignaciones laborales. Editar, cobrar o marcar asistencia no reasigna la Sucursal de la inscripción original.

## Integración

- `SedeAccess` permite el acceso compartido al circuito académico completo: inscripciones, matrículas, programaciones, sesiones, clases, estudiantes y catálogos de docentes/aulas. Sus personas relacionadas y apoderados pueden resolverse aunque sean de otra Sucursal.
- Los pagos de inscripciones y matrículas se consultan y registran entre sucursales. Los saldos incluyen todos sus recibos, con el tipo de inscripción correspondiente. Se mantienen las restricciones existentes de ventas, pagos ajenos al circuito académico, CRM y administración de cuentas.
- El historial académico relacionado mediante observaciones, licencias, archivos y calificaciones mantiene sus permisos y referencias existentes. La ampliación para el personal no se aplica al filtro familiar.
- La ubicación de una clase se determina por su aula y por la asistencia efectiva; no por la Sucursal donde está conectado el operador ni por el origen comercial de la inscripción. Las asistencias nuevas utilizan la Sucursal del aula cuando está identificada. Se conservan los bloqueos de solapamiento del estudiante y de ubicación física incompatible del docente.
- `HasEnrollmentOrigin` incorpora el nombre de la Sucursal mediante una subconsulta indexada, sin una consulta por fila. El API general utiliza DataTables sobre Eloquent para conservar paginación del servidor.
- Se muestran estado y origen en el listado general, la variante de Guardería y los listados/tarjetas individuales de inscripciones y Computación. Los nombres de Sucursal se escapan al renderizar HTML.
- Los saldos de registros migrados sin fecha de próximo pago responden **Sin fecha programada**, en lugar de producir un error 500.

No se crearon migraciones, catálogos duplicados, permisos paralelos ni tablas nuevas. No se ejecutaron operaciones sobre la base real local ni de producción; las pruebas usan SQLite en memoria y servicios externos simulados.

## Prueba manual en local

1. Entrar con Secretaría u otra cuenta que tenga permisos de inscripciones, Computación, pagos y asistencia. Mantener seleccionada IFE Central.
2. Consultar las inscripciones de un estudiante con registros de IFE Villa o IFE Plan. Deben aparecer junto con las de Central; el estado muestra la Sucursal original en la misma celda. Revisar también las tarjetas móviles y Computación.
3. Abrir y editar una inscripción de otra Sucursal. Guardar y comprobar que conserva su origen y que el selector superior sigue en Central.
4. Registrar un pago autorizado en esa inscripción o matrícula. Comprobar que el saldo incluye los pagos recibidos anteriormente en otras sucursales y únicamente los del tipo de inscripción correcto.
5. Marcar una clase habilitada por pago de otra Sucursal, con su docente y aula reales. La ubicación del operador no debe rechazarla. Siguen rechazándose dos clases simultáneas del mismo estudiante o del docente en ubicaciones físicas incompatibles.
6. Ver un registro migrado sin Sucursal: debe mostrar **Sin registrar**. Crear otra inscripción indicando una Sucursal diferente: debe conservar la Sucursal activa del operador y registrar el origen elegido.
7. Probar una cuenta sin permiso de edición o pagos y una cuenta familiar. El acceso compartido del personal no debe habilitarles acciones nuevas.

## Archivos

- `app/Services/SedeAccess.php`: política de acceso y atribución.
- `app/Services/ClassAttendanceService.php`: ubicación física independiente de la sesión del operador.
- `app/Models/Concerns/HasEnrollmentOrigin.php`, `Inscripcione.php` y `Matriculacion.php`: consulta reutilizable del origen.
- `app/Http/Controllers/InscripcioneController.php` y `MatriculacionController.php`: origen, permisos de consulta y saldos compatibles con fechas ausentes.
- `routes/api.php`: listado paginado y estado/Sucursal para el listado general.
- `resources/views/inscripcione/estado-sucursal.blade.php`, `index.blade.php`, `guarderia/index.blade.php`, `tusinscripciones.blade.php`, `sucursal.blade.php` y `resources/views/matriculacion/form.blade.php`: presentación y selección informativa.
- `tests/Feature/SharedEnrollmentsTest.php`, pruebas existentes de usuarios, asistencia, presentes y creación, y `phpunit.attendance.xml`: cobertura de la política nueva y de los controles que se conservan.

## Verificación

```powershell
C:\xampp\php\php.exe vendor/phpunit/phpunit/phpunit -c phpunit.attendance.xml
C:\xampp\php\php.exe vendor/phpunit/phpunit/phpunit -c phpunit.user-access.xml
C:\xampp\php\php.exe vendor/phpunit/phpunit/phpunit -c phpunit.google-contacts.xml
```

Pasaron **398 pruebas**: 106 de asistencia/inscripciones, 72 de usuarios/2FA y 220 de CRM/Google Contacts. Se añadieron pruebas HTTP de listado, pagos, marcado remoto, edición y conservación del origen; también se comprobaron los permisos, el aislamiento de operaciones no académicas y el escape de nombres. Se verificó la sintaxis PHP de los archivos modificados y JavaScript de los tres listados afectados.

Cambios desarrollados y probados en local. La publicación autorizada junto con las mejoras posteriores de asistencia sigue `docs/despliegue-academico-2026-11-12.md`. Se excluyen las modificaciones ajenas de `.gitignore` y `resources/views/home/public/service.blade.php`.
