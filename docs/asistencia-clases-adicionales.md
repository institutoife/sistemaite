# Asistencia y clases adicionales — revisión local

Implementación y pruebas realizadas en local, sin conexión a la base de datos de producción. Para publicar esta versión, seguir [la actualización por Git en BanaHosting](despliegue-asistencia-banahosting.md).

## Cómo probar

1. Iniciar Apache/MySQL y abrir `http://127.0.0.1:8000/home` con un usuario autorizado. Seleccionar una **Sucursal activa** concreta antes de crear registros.
2. Pulsar el icono de asistencia, turquesa o azul. Aparecen las inscripciones y matrículas activas en pestañas; si no existen, el modal informa y no permite crear clases.
3. En **Clases de hoy**, desplegar **Marcar entrada**, confirmar docente, materia, aula y horario. Se conserva el marcado rápido anterior. La salida se registra desde el mismo modal. Una segunda solicitud no crea otra asistencia.
4. En **Agregar clase / Adelantar clase**, elegir obligatoriamente un tipo: el formulario empieza en **Elija tipo de clase**. El buscador de originales filtra por fecha, materia o docente; al seleccionar uno se precargan docente, materia, aula y duración. La hora de inicio usa `America/La_Paz` y la finalización se calcula sumando la duración original. Explicar el motivo y guardar. Para programar una fecha futura, dejar desmarcada la entrada al guardar (se desmarca al elegir una fecha futura).
5. Recuperar una clase pasada con licencia, falta, falta notificada o ausencia; comprobar que el original conserva su estado. También puede recuperarse una clase de hoy cuyo horario ya terminó.
6. Adelantar una clase pendiente futura; comprobar que aparecen ambas fechas. El original conserva su programación, queda identificado como compensado y no vuelve a generar pendiente ni falta automática.
7. En **Clase extra**, elegir obligatoriamente la clase programada, pendiente y pagada que se desea consumir, igual que en **Adelanto**. Puede ser una clase de una fecha futura o de hoy cuyo horario aún no empezó. Comprobar docente, materia, aula y duración, y guardar. La original queda vinculada y no puede volver a utilizarse; no se crea otro cargo ni se descuentan sus horas dos veces. Si falta pago, se rechaza sin crear registros.
8. Intentar repetir un original, exceder su duración, cruzar horarios del estudiante, ubicar al docente en aulas o sucursales incompatibles, utilizar una inscripción inactiva o acceder sin permiso. Deben rechazarse. Las comprobaciones incluyen ambos circuitos y la ocupación del docente en otras sucursales, sin revelar sus datos.
9. Abrir **Programación e historial** y **Ver reporte de asistencia e historial**. Comprobar usuario, fecha/hora, clase original, fecha efectiva, docente, materia y aula. Los indicadores separan programadas, asistidas, faltas, licencias, recuperadas, adelantadas, extras y pendientes de recuperación.
10. Con permisos de edición de clases y programación, usar **Corregir**, manteniendo duración y vínculo. El motivo es obligatorio; se conservan valores anteriores y nuevos. Una adicional sin asistencia puede reprogramarse; si estaba en falta, queda pendiente en la fecha corregida. Si un adelanto no se asistió y su fecha original ya pasó, puede reprogramarse después como recuperación, con el cambio de tipo auditado. Una asistencia realizada no se traslada al futuro.
11. Registrar varios estudiantes con el mismo docente, aula y horario: debe permitirse, incluso entre apoyo y computación. Cada estudiante conserva su asistencia y sus horas; repetir la solicitud del mismo estudiante no duplica su asistencia.

## Ajustes del formulario Agregar clase

- El tipo de clase es obligatorio y empieza vacío en el modal de `/home`, `/programa/marcar/{id}` y `/programacioncom/marcar/{id}`.
- Una original de 09:00 a 10:30 precarga **90 minutos**. Al cambiar el inicio a **14:20**, la finalización pasa a **15:50**. Cambiar el inicio u otra original recalcula el final. Los datos de docente, materia y aula siguen editables con los permisos existentes; el servidor exige conservar la duración en recuperaciones, adelantos y extras. Los tres tipos requieren seleccionar una original.
- **Tema** es opcional en apoyo, reutilizando su relación existente con Materia. Select2 se carga desde los archivos locales de AdminLTE. Se busca al escribir, por materia y en páginas de 20 resultados; no se descarga el catálogo completo al abrir el formulario. Computación conserva su Asignatura y no crea otro catálogo de temas.
- El tema elegido se conserva también cuando se programa sin marcar entrada. Se precarga al registrar la asistencia o corregirla y aparece en el historial. Se conservan los temas de asistencias anteriores a esta mejora; solo se quitan si el usuario lo solicita expresamente.
- La simultaneidad del docente se considera compatible en la misma aula y sucursal, sin límite artificial de un estudiante. Las programaciones y asistencias individuales existentes comparten ese horario mediante sus referencias actuales; no se crean filas adicionales de `sesions` o `sesioncoms`. Se mantienen los bloqueos del estudiante, la ocupación del docente en otras ubicaciones, las comprobaciones de pago, la idempotencia y la auditoría.

Migración adicional: `database/migrations/2026_11_02_000002_add_topic_to_clases_adicionales.php`. Añade únicamente `tema_id`, opcional, a los metadatos de clases adicionales, relacionado con el catálogo existente. **Aplicada solo en MySQL local**, con huellas de datos antes/después iguales para las tablas históricas. No se ejecuta backfill ni se sobrescriben registros. La reversión protege los temas ya registrados.

Archivos ajustados: `ClassAttendanceService`, `ClassAttendanceView`, `ClaseAdicionalController`, `ClaseAdicional`, la nueva migración y ruta `asistencia.temas`, `public/vistas/clase/asistencia.js`, vistas `clase/asistencia/{add,fields,panel,history,report}`, y carga de Select2 en `persona/estudiantes` y las dos vistas `marcadoGeneral`. Pruebas en `tests/Feature/ClassAttendanceTest.php`.

Verificación actual: **52 pruebas locales y 428 aserciones aprobadas**, que incluyen las pruebas de inscripción existentes. Se comprobaron las páginas completas de marcado de apoyo/computación, la atención simultánea de seis estudiantes en ambos circuitos, los tres tipos adicionales, los conflictos de aulas/sucursales, los temas opcionales, su conservación histórica y su búsqueda paginada con permisos. Las pruebas de extras incluyen original obligatoria, duración, pago, consumo una sola vez, contadores y propagación de la habilitación por pagos en ambos circuitos. El JavaScript se probó en un navegador sin interfaz con datos sintéticos: escritorio y vistas de **390 y 320 px**, con selección de original para la extra, precarga, Select2, cálculo de horarios, ajustes manuales, validaciones y protección contra doble envío. No se utilizaron datos ni sesiones de producción.

La corrección del consumo de **Clase extra** reutiliza las columnas e índices de original ya existentes. **No necesita otra migración ni modifica registros anteriores**. Para probarla, recargar con **Ctrl+F5**, elegir Clase extra, seleccionar una programada pagada y guardar; comprobar el vínculo en el historial, el indicador Extras y que Programadas/horas contratadas no aumenten.

Las pantallas `/programa/marcar/{id}` y `/programacioncom/marcar/{id}` incluyen el mismo componente. Los programas impresos, el historial del portal familiar y las consultas de programación de Cartera identifican las relaciones.

**Programadas** cuenta las clases originales contratadas, sin sumar otra vez sus recuperaciones, adelantos o nuevas extras vinculadas. **Extras** identifica estas últimas sin aumentar las horas contratadas. Faltas y licencias conservan el estado histórico de las originales. Los intentos y correcciones se consultan en el historial. Se conserva el tratamiento de extras antiguas registradas sin original: no se reescriben ni se les inventa un vínculo.

## Conservación de datos y reglas comerciales

- Una recuperación o adelanto crea una nueva programación y utiliza las tablas de asistencia actuales (`clases` / `clasecoms`). No cambia fecha, estado, materia ni docente del original.
- El vínculo del original y la clave de solicitud tienen índices únicos. Transacciones y bloqueos del estudiante, inscripción, docente y programación evitan duplicados y cruces entre inscripciones/circuitos.
- Las recuperaciones, adelantos y nuevas extras reutilizan la habilitación por pago de la original: se excluyen de la distribución de pagos para no descontar dos veces la misma hora. Las extras antiguas sin vínculo mantienen su tratamiento previo, sin alterar datos históricos.
- No se fusionan ni borran clases anteriores. La programación con vínculos adicionales no puede eliminarse ni regenerarse masivamente, porque rompería el historial. Se utiliza la corrección auditada para ajustar las adicionales; el resto de las programaciones conserva sus acciones habituales.
- Las clases originales ya asistidas o compensadas se rechazan; no hay un botón para saltar esa protección. Las correcciones autorizadas conservan el vínculo y la trazabilidad.
- La confianza 2FA, usuarios, fotografías, Google Contacts y sus procesos automáticos no se modifican por esta mejora.

## Migración

`database/migrations/2026_11_02_000001_create_clases_adicionales.php`

Crea únicamente `clases_adicionales` y `clase_asistencia_eventos`, con referencias e índices. No altera las tablas antiguas, no reasigna sucursales ni sobrescribe registros. La reversión rechaza borrar estas tablas si contienen historial.

Aplicada en MySQL **local**, con verificación de entorno y comparación de huellas de las tablas históricas antes y después: sin cambios en sus datos. **No se ha ejecutado en producción**.

## Archivos principales

- Modelos: `ClaseAdicional`, `ClaseAsistenciaEvento` y el trait `Models/Concerns/HasAdditionalClasses`, utilizado por las dos programaciones.
- Servicios: `ClassAttendanceService` (validación, pagos, bloqueos y auditoría), `ClassAttendanceView` (datos compartidos e indicadores).
- Controladores: `ClaseAdicionalController`; integración en `ClaseController`, `ClasecomController`, `ProgramacionController`, `ProgramacioncomController`, `EstudianteController`, `ComputacionController`, `LicenciaController`, `PadreController` y `PersonaController`.
- Interfaz: `resources/views/clase/asistencia/`, modal existente, acciones de persona/programación, reportes de programación, portal familiar, Cartera y `public/vistas/clase/asistencia.js`.
- Rutas: creación y corrección por POST/PATCH y consulta de historial por GET, dentro del grupo autenticado existente.
- Pruebas: `tests/Feature/ClassAttendanceTest.php`, configuración aislada `phpunit.attendance.xml`.

## Inscripción para las pruebas locales

Se corrigió el guardado en `/inscripcione/crear/26`: con **Todas · Admin**, la inscripción requería una sucursal y ese error no aparecía en el formulario. Ahora se muestra **Sucursal**, utilizando el catálogo y las autorizaciones existentes, y un resumen de todos los errores. La sucursal elegida queda activa para configurar las clases. Los registros históricos no se reasignan.

1. Abrir `http://127.0.0.1:8000/inscripcione/crear/26` y recargar con **Ctrl+F5**.
2. Elegir Sucursal y modalidad. Verificar costo, horas y fecha, seleccionar motivo y escribir el objetivo (entre 10 y 255 caracteres, según la validación existente).
3. Pulsar **Guardar**: debe abrirse la configuración de clases. Si falta un dato, se muestra el error y se conservan las selecciones y los importes introducidos.
4. Configurar clases y registrar el pago mediante el flujo existente antes de probar recuperaciones, adelantos o extras pagadas.

También se reemplazó la consulta AJAX de costo/horas por los datos del catálogo ya cargado y se reutilizó el editor local en las pantallas de creación y edición normales. Guardería utiliza el mismo selector de sucursal y resumen de errores, conservando su cálculo de precios.

Archivos de esta corrección: `InscripcioneController`, `SedeAccess`, las vistas `inscripcione/{create,edit,form,sucursal}` y `inscripcione/guarderia/form`, `public/vistas/inscripcione/form.js`, `tests/Feature/InscriptionCreationTest.php` y `phpunit.attendance.xml`. **No requiere otra migración**.

Verificación de la corrección: **94 pruebas y 671 aserciones aprobadas** (35 de inscripción/asistencia y 59 de usuarios/2FA), comprobación de JavaScript con y sin editor, y renderizado/guardado con la Persona 26 en MySQL local dentro de transacciones revertidas. No se dejaron inscripciones de prueba ni se realizaron commits, push o cambios en producción.

## Pruebas automatizadas

Ejecutar secuencialmente en la carpeta del proyecto. Cada configuración utiliza SQLite `:memory:`, HTTP simulado y colas simuladas; no utiliza MySQL local ni producción.

```powershell
C:\xampp\php\php.exe vendor/phpunit/phpunit/phpunit -c phpunit.attendance.xml
C:\xampp\php\php.exe vendor/phpunit/phpunit/phpunit -c phpunit.user-access.xml
C:\xampp\php\php.exe vendor/phpunit/phpunit/phpunit -c phpunit.google-contacts.xml
```

Verificación adicional de Blade, sintaxis PHP/JavaScript y del modal en navegador a tamaños de escritorio y móvil. Las pruebas de concurrencia validan idempotencia y restricciones únicas; no sustituyen una prueba de carga con múltiples conexiones MySQL.

Verificación previa a publicar: **331 pruebas, 2.507 aserciones, sin errores** (52 de asistencia/inscripción, 59 de usuarios/2FA y 220 de CRM/Google Contacts). Modal comprobado en escritorio y móvil, incluidos pestañas, búsqueda, precarga, entrada rápida, doble clic y mensajes de validación. El script de actualización se comprobó con ejecución simulada satisfactoria y fallos de configuración, migración y verificación; solo habilita el sistema si todos los pasos terminan correctamente.
