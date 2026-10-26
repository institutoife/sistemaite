# Fase 2 — Arquitectura de contactos y comunicaciones IFE

Fecha: 24 de octubre de 2026.

Estado: plan técnico posterior a la auditoría. Este documento no implementa funcionalidades, no ejecuta migraciones y no activa envíos ni sincronizaciones.

Avance del 25 de octubre: el primer bloque de Google Contacts ya está implementado. Alcance, validaciones y puesta en marcha en [Primera entrega](google-contacts-primera-entrega.md); los demás bloques de este plan siguen pendientes.

Ampliación autorizada del 26 de octubre: la elegibilidad inicial propuesta en este plan fue reemplazada por todas las fichas de Persona (incluidos docentes y clientes potenciales), Prospectos y consultas web/internas. Se incorporaron altas, cambios y conservación del teléfono remoto cuando falta el local: [Orígenes y teléfonos incompletos](google-contacts-origenes-y-telefonos.md). El resto de funcionalidades futuras de comunicaciones sigue pendiente.

Segunda ampliación del 26 de octubre: se implementaron nombres/códigos, parentescos, notas de servicios, cumpleaños y fotografías propias en Google, sin envíos de comunicaciones. Preparación de actualización para la instalación existente en ife.bo en [Contactos completos](google-contacts-contactos-completos.md).

## 1. Decisiones

- Conservar Laravel, AdminLTE, Bootstrap, Blade, Livewire, DataTables y Spatie Permission existentes.
- Una persona local corresponde a un contacto Google, aunque sea apoderada de varios estudiantes o tenga también el papel de estudiante.
- Mantener `personas.resourseName` y `personas.etag`, incluidos sus nombres actuales. No crear otra tabla de contactos locales.
- Conservar los dos circuitos educativos: inscripciones académicas y matrículas de computación.
- Extender `mensajeables` para las comunicaciones con origen, destinatario, contenido y estado. Conservar `mensajes` para las plantillas, `felicitados` para la protección anual y `crm_seguimientos`/`userables` para la trazabilidad.
- Conectar una única cuenta central de Google y verificar su identidad al reconectarla. Una cuenta distinta no puede apropiarse automáticamente de los enlaces existentes.
- Preparar faltas y cobranzas para revisión humana. Abrir WhatsApp no confirma un envío.
- No borrar contactos Google por bajas, inactividad, retiro o desvinculación de un apoderado.

## 2. Archivos a reutilizar

| Archivos existentes | Uso |
| --- | --- |
| `app/Models/Persona.php`, `Estudiante.php`, `Computacion.php` | Identidad, código actualmente mostrado y relaciones personales. |
| `app/Models/Inscripcione.php`, `Matriculacion.php`, `Modalidad.php`, `Asignatura.php` | Servicios reales, vigencia y condiciones económicas. |
| `app/Models/Programacion.php`, `Programacioncom.php`, `Clase.php`, `Clasecom.php` | Origen de la asistencia y sus correcciones. |
| `app/Models/Pago.php` | Pagos reales mediante `pagable_type` y `pagable_id`. |
| `app/Models/Mensaje.php`, `Mensajeable.php`, `Felicitado.php`, `CrmSeguimiento.php` | Plantillas, comunicaciones e historial. |
| `app/Models/User.php`, `app/Providers/AuthServiceProvider.php` | Roles, permisos y usuarios existentes. |
| `app/Http/Controllers/VcardController.php` | Referencia del mapeo existente de datos y servicios; conservar la exportación vCard. No copiar sus fotos de sustitución a Google. |
| `resources/views/vendor/adminlte/`, `routes/breadcrumbs.php`, `public/custom/` | Layouts, navegación, componentes y estilos. |
| `config/queue.php`, tablas `jobs` y `failed_jobs` | Infraestructura de cola ya disponible. |

## 3. Archivos a modificar

| Archivo o grupo | Cambio concreto |
| --- | --- |
| `app/Http/Controllers/GContactController.php` | Conservar autorización y callback; delegar tokens y API al servicio; gestionar denegación, revocación y reconexión. |
| `app/Http/Controllers/PersonaController.php` | Sustituir llamadas Google dentro de requests por marcado pendiente. Separar preparación, apertura de WhatsApp y confirmación de envío; conservar entradas existentes. |
| `app/Http/Controllers/TelefonoController.php` | Guardar preferencias de N apoderados y marcar afectados después de crear, cambiar o eliminar el vínculo. Los cambios de pivote necesitan un enlace explícito con el servicio. |
| `app/Console/Commands/SyncGoogleContactsBatch.php` | Conservar el comando como entrada de encolado; retirar el uso operativo de tokens por argumento y las llamadas directas duplicadas a Google. |
| `app/Http/Controllers/PagoController.php`, `PagocomController.php` | Unificar consulta de saldos reales y revisar recordatorios tras crear, editar o eliminar pagos. |
| `app/Http/Controllers/EstudianteController.php` | Reutilizar las alertas del inicio; consultar cumpleaños, faltas y cobros mediante los servicios compartidos. No confundir “gestionado” con “enviado”. |
| `app/Http/Controllers/DashboardController.php` | Agregar indicadores enlazados y respetar permisos sobre cobranzas y contactos. |
| `app/Http/Controllers/ProgramacionController.php`, `ProgramacioncomController.php` | Conservar marcado y correcciones; adaptar actualizaciones masivas de faltas, que no disparan observers Eloquent. Cambiar mutaciones GET por POST/PATCH y actualizar sus consumidores. |
| `app/Http/Controllers/MensajeableController.php` | Conservar operaciones previas y dirigir las nuevas comunicaciones por el servicio de estados. |
| `app/Http/Controllers/ModalidadController.php`, `AsignaturaController.php` | Marcar contactos afectados si cambia el nombre de un servicio utilizado en sus notas. |
| `app/Models/Persona.php`, `Mensajeable.php`, `Felicitado.php`, `CrmSeguimiento.php` | Incorporar relaciones, casts y consultas necesarias sin cambiar el significado de los campos anteriores. |
| `app/Http/Requests/PersonaStoreRequest.php`, `PersonaUpdateRequest.php`, `PersonaApoderadaRequestStore.php`, `TelefonoUpdateRequest.php`, `GuardarApoderadoExistenteRequest.php` | Validar datos de contacto y preferencias sin sobrescribir campos ausentes en formularios antiguos. |
| `app/Providers/AppServiceProvider.php` | Registrar observers. |
| `app/Console/Kernel.php` | Registrar preparación diaria y despacho de pendientes. |
| `config/services.php`, `.env.example` | Documentar configuración OAuth existente; secretos solo en configuración segura. |
| `routes/web.php` | Proteger conexión Google y acciones con permisos, registrar rutas nuevas y retirar la ruta que devuelve el token completo. |
| `config/adminlte.php`, `routes/breadcrumbs.php` | Incorporar Centro de comunicaciones y Contactos en el menú actual. |
| `resources/views/persona/crear.blade.php`, `editar.blade.php`, `form.blade.php` | Estado comprensible de conexión y correo real cuando corresponda. |
| `resources/views/telefono/crear.blade.php`, `index.blade.php`, `form.blade.php` | Preferencias del apoderado, fecha de nacimiento y selección principal en los formularios utilizados. |
| `resources/views/estudiantes/cumpleaneros.blade.php`, `faltones.blade.php`, `resources/views/persona/deudores/` | Conservar listados, actualizar acciones y mostrar estados confirmados. |
| `resources/views/persona/estudiantes.blade.php`, `resources/views/dashboard.blade.php` | Extender las alertas existentes con indicadores comunes. |
| `resources/views/whatsapp/` | Reutilizar la edición de plantillas; incorporar variables permitidas y validación. |

Durante cada entrega se localizarán también los consumidores de las rutas modificadas para mantener compatibles los formularios y acciones existentes. El cambio previo del usuario en `resources/views/home/public/service.blade.php` queda fuera de este trabajo.

## 4. Archivos nuevos necesarios

Todas las rutas siguientes son propuestas, todavía no creadas.

| Archivo | Responsabilidad |
| --- | --- |
| `app/Models/GoogleContactAccount.php` | Autorización de la cuenta central, identidad remota y tokens cifrados y ocultos al serializar. |
| `app/Services/GoogleContactsService.php` | OAuth persistente, renovación, conexión, lectura, creación, actualización, fotografía y reconciliación segura. |
| `app/Services/TelefonoService.php` | Normalización no destructiva de teléfonos bolivianos e internacionales y generación de enlaces WhatsApp. |
| `app/Services/ComunicacionService.php` | Resolver destinatarios, aplicar plantillas y controlar preparación, confirmación, omisión, cancelación y corrección. |
| `app/Services/SaldoService.php` | Consulta única del costo, pagos, condonación, saldo y vencimiento para ambos tipos de contrato. |
| `app/Http/Controllers/GoogleContactsController.php` | Listado, filtros, detalle y solicitudes de sincronización; no realiza lotes de llamadas API. |
| `app/Http/Controllers/ComunicacionController.php` | Centro, historial, preparación de mensajes y acciones manuales autorizadas. |
| `app/Http/Requests/ActualizarComunicacionRequest.php` | Validación de mensaje, transición de estado y observación manual. |
| `database/seeders/ComunicacionesSeeder.php` | Crear únicamente permisos y plantillas faltantes de forma idempotente. No ejecutar el seeder general sobre los datos existentes. |
| `resources/views/contactos/index.blade.php`, `show.blade.php` | Estado de conexión, listado paginado, filtros y diagnóstico restringido. |
| `resources/views/comunicaciones/index.blade.php`, `listado.blade.php`, `show.blade.php` | Resumen e historial con revisión del mensaje. Cobros pendientes reutiliza además el listado financiero existente. |

## 5. Migraciones incrementales propuestas

No modificar migraciones históricas. Ejecutar primero una comprobación de datos en el entorno de destino. Si existen duplicados que impiden un índice, informar y detener ese paso sin borrar ni fusionar registros automáticamente.

1. `2026_10_24_000001_create_google_contact_accounts_table.php`: única tabla nueva. Cuenta central identificada de forma única, identificador Google, correo de la cuenta, identificador estable de instalación, tokens en columnas TEXT cifradas desde Laravel, expiración, necesidad de reconexión y usuario que la conectó. No guardar tokens en logs, HTML ni argumentos de comandos.
2. `2026_10_24_000002_extend_personas_google_contacts.php`: reutilizar los cuatro campos `gcontact_sync_*` existentes; agregar cuenta asociada, habilitación individual, último éxito, inicio de procesamiento, código de error y huella del contenido sincronizado. Agregar correo real nullable: no reutilizar automáticamente el correo generado para iniciar sesión. Índices para pendientes y vínculo cuenta/recurso. La conexión de una cuenta no inicia por sí sola una sincronización masiva.
3. `2026_10_24_000003_add_contact_preferences_to_persona_persona.php`: principal y preferencias para faltas, cobranzas y comunicaciones. Preferencias nullable permiten heredar del principal; no elegir al primer apoderado silenciosamente si hay ambigüedad. Índice único estudiante/apoderado después de revisar los datos. Un principal por estudiante se mantiene con transacción y bloqueo del estudiante.
4. `2026_10_24_000004_extend_mensajeables_for_communications.php`: tipo, estado, estudiante relacionado, tipo de destinatario, nombre/teléfono/mensaje utilizados, fecha del origen, fecha de preparación/envío/corrección, observación y clave de deduplicación única nullable. Reutilizar `mensaje_id`, `persona_id` y el origen polimórfico. Los registros anteriores conservan su significado y se muestran como historial previo, sin inventar confirmaciones de envío. Indexar tipo/estado/fecha y origen.
5. `2026_10_24_000005_add_person_year_unique_to_felicitados.php`: unicidad de persona/año, manteniendo todos los datos previos. No registrar la felicitación por abrir un modal; registrar al confirmar envío. La comunicación pendiente también queda protegida por su clave anual.
6. `2026_10_24_000006_link_crm_seguimientos_to_communications.php`: referencia nullable a `mensajeables` para vincular acciones al mensaje concreto; permitir actor nullable para distinguir tareas automáticas de acciones humanas. Conservar historial, permisos y relaciones actuales.

Las fotos y fechas de nacimiento de los apoderados usan los campos existentes de `Persona`. No crear columnas equivalentes por cada parentesco. No crear nuevas tablas de pagos, asistencia, cumpleaños o auditoría.

## 6. Jobs y comandos

| Archivo nuevo | Trabajo |
| --- | --- |
| `app/Jobs/SyncGoogleContact.php` | Sincronizar una persona por ID; leer datos actuales, controlar cuenta y exclusión mutua, actualizar enlace y estado. |
| `app/Jobs/QueueGoogleContacts.php` | Recorrer candidatos con `chunkById` y encolar contactos. No cargar toda la tabla. |
| `app/Jobs/PrepararComunicaciones.php` | Preparar cumpleaños o recordatorios financieros por lotes; nunca enviar mensajes a clientes. |
| `app/Jobs/RevisarComunicacionesOrigen.php` | Crear/revisar avisos de una programación o contrato tras cambios de asistencia o pagos. |
| `app/Console/Commands/PrepararComunicacionesCommand.php` | Entrada verificable `comunicaciones:preparar`, con tipo y fecha. |

El comando existente `gcontact:sync-batch` pasa a despachar Jobs. Los Jobs se publican después del commit; reciben identificadores y nunca tokens serializados.

Máximo inicial: cinco intentos automáticos para errores recuperables, con espera creciente. Revocación, permisos insuficientes, datos inválidos y agotamiento de intentos requieren intervención. El despachador periódico no debe convertir un error definitivo en reintentos infinitos. Renovar datos o reintentar manualmente habilita un nuevo ciclo controlado.

## 7. Eventos, listeners y cambios de origen

No se necesita inicialmente una capa adicional de Events/Listeners personalizados. Usar observers Eloquent y Jobs después del commit:

- `app/Observers/PersonaObserver.php`: cambios de nombre, teléfono, correo, cumpleaños o foto marcan sincronización pendiente; incluir apoderados afectados por el nombre del estudiante.
- `app/Observers/ProgramacionObserver.php` y `ProgramacioncomObserver.php`: revisar ausencia/corrección por origen exacto.
- `app/Observers/PagoObserver.php`: revisar cobranza ante creación, cambio, reasignación o eliminación de pago, incluyendo origen anterior y nuevo si cambia.
- `app/Observers/InscripcioneObserver.php` y `MatriculacionObserver.php`: revisar costo, condonación, vencimiento, vigencia y servicios asociados; marcar contactos afectados.

Las escrituras de pivotes y los `update` masivos requieren llamadas explícitas al servicio o al despachador después de la transacción. No depender de observers para operaciones que no los ejecutan. Los guardados de metadatos de sincronización no deben volver a encolar el mismo cambio.

## 8. Reglas Google e idempotencia

- Estudiante: `Lucas Mamani | IFE | 45645`, utilizando el código que actualmente muestra el sistema, sin inventar otra numeración.
- Apoderado: `Juan | Papá de Lucas Mamani | 45645`. Si representa a varios estudiantes, conservar un único contacto y construir un nombre compacto con sus vínculos; guardar el detalle completo en notas.
- Solo nombres y vínculos necesarios en el nombre visible; servicios y observaciones autorizadas en notas. Evitar exportar indiscriminadamente observaciones administrativas privadas.
- Cumpleaños en el campo correspondiente. Foto únicamente de la persona del contacto; excluir fotos predeterminadas y cualquier sustitución por la del estudiante.
- Elegibilidad inicial propuesta, reemplazada el 26 de octubre por instrucción del usuario: se incluyen todas las fichas de Persona, Prospectos y consultas web/internas, con un origen canónico al convertir una consulta en Persona.
- Conservar enlace remoto por persona y cuenta. Verificar recursos previos antes de asociarlos a la cuenta central; un recurso con etag faltante se consulta, no se recrea.
- Añadir al contacto un identificador estable de instalación/persona mediante `userDefined`. No usar exclusivamente el teléfono para identificar o fusionar personas.
- Serializar escrituras por cuenta y excluir sincronizaciones simultáneas de una persona. Recuperar metadatos actuales y el etag de la fuente CONTACT antes de actualizar.
- Una creación con timeout puede haber ocurrido en Google. Registrar resultado incierto y reconciliar mediante el identificador externo; no repetir automáticamente CREATE si no puede resolverse con certeza. La People API no permite prometer exactamente una creación mediante una transacción local.
- Guardar una huella del contenido enviado. Si los datos vuelven a cambiar mientras trabaja el Job, mantener el contacto pendiente para otra actualización.
- Cambiar de cuenta o perder un enlace requiere diagnóstico; no recrear todos los contactos ni borrarlos automáticamente.

## 9. Reglas de comunicaciones

Estados: pendiente, preparado, enviado, omitido, cancelado y error. Son propios de la comunicación; no reemplazan los estados académicos de `estados`.

Claves únicas:

- Cumpleaños: persona celebrada y año. Respetar también los registros anuales anteriores en `felicitados`.
- Falta: tipo/ID de programación y destinatario. Se admiten N destinatarios cuando sus preferencias lo indiquen.
- Cobranza: tipo/ID de inscripción o matrícula, vencimiento y destinatario. Recalcular saldo sin producir avisos nuevos por cada ejecución del Scheduler.

Destinatarios: respetar preferencias; si no están definidas, usar principal configurado. Cuando no hay destinatario inequívoco o teléfono válido, dejar el caso para resolver en el panel. No contactar indiscriminadamente a todos los apoderados.

Asistencia corregida: cancelar mensajes pendientes/preparados. Si se había confirmado envío, conservar el mensaje original y registrar la corrección.

Cobranza: calcular pagos con tipo e ID; incluir contratos sin pagos; consultar costo efectivo, condonación, vigencia, vencimiento y observaciones administrativas pertinentes. No inventar becas, convenios ni tolerancias que no estén modelados. Los convenios de catálogo no se interpretan automáticamente como descuentos personales. Revalidar al preparar y al abrir WhatsApp. Un pago completo cancela el pendiente; uno parcial obliga a actualizar y revisar el texto. Mantener mensajes ya enviados como historial.

WhatsApp: generar URL con teléfono normalizado y mensaje codificado una sola vez. Mostrar revisión y una acción separada para confirmar manualmente el envío. No afirmar entrega comprobada por abrir el enlace.

## 10. Rutas y permisos

Mantener las URLs OAuth existentes, protegidas con autenticación y permiso de configuración. Solo redirigir tras OAuth a rutas internas permitidas.

| Método/ruta propuesta | Acción | Permiso propuesto |
| --- | --- | --- |
| GET `/contactos` y `/contactos/{persona}` | Listado y detalle | `Listar Contactos` |
| POST `/contactos/sincronizar-todos` | Encolar lote seleccionado | `Sincronizar Todos Contactos` |
| POST `/contactos/{persona}/sincronizar` | Sincronizar, resincronizar o reintentar | `Sincronizar Contactos` |
| GET `/auth/google` y `/auth/google/callback` | Conectar/reconectar | `Configurar Google Contacts` |
| POST `/contactos/google/desconectar` | Desconectar sin borrar contactos | `Configurar Google Contacts` |
| GET `/comunicaciones` | Resumen según permisos | `Listar Comunicaciones` |
| GET `/comunicaciones/listado` y `/comunicaciones/{mensajeable}` | Filtros e historial | `Listar Comunicaciones` y permiso del tipo |
| POST `/comunicaciones/preparar` | Preparar desde un origen validado | `Gestionar Comunicaciones`; cobranza exige además `Gestionar Cobranzas` |
| PATCH `/comunicaciones/{mensajeable}` | Revisar, omitir, cancelar o confirmar envío | Permiso de la acción y del tipo |
| GET `/comunicaciones/{mensajeable}/whatsapp` | Revalidar y abrir texto revisado; no cambia a enviado | Permiso de gestión del tipo |

Permisos adicionales: `Confirmar Envio Comunicaciones` y `Ver Diagnostico Google`. Admin recibe configuración, diagnóstico y sincronización masiva. Secretaría puede trabajar comunicaciones/contactos según permisos explícitos. Padres, estudiantes y docentes no reciben acceso global por su papel.

Registrar rutas estáticas antes de parámetros dinámicos. Mantener CSRF en mutaciones y autorizar también los orígenes solicitados. Ocultar botones nunca sustituye comprobar permisos en backend. Separar el permiso de consultar una cobranza del de prepararla o confirmar su envío.

## 11. Scheduler y operación

- Diario a las 07:00, zona `America/La_Paz`: preparar cumpleaños de personas elegibles.
- Diario a las 07:10: revisar cobros y preparar recordatorios internos.
- Cada cinco minutos: despachar sincronizaciones pendientes elegibles, con límites y exclusión mutua; no hacer llamadas API en el proceso del Scheduler.
- Revisiones inmediatas por Jobs después de cambios de asistencia y pagos. Sin polling del navegador para detectar cambios de negocio.
- Usar `QUEUE_CONNECTION=database`, las tablas actuales y un worker dedicado a `google-contacts` con concurrencia inicial de uno. Otro worker atiende `comunicaciones`.
- Ejecutar `php artisan schedule:run` cada minuto mediante el mecanismo del servidor: Programador de tareas en Windows o cron en Linux. Comprobar logs y supervisión de workers.
- Conservar `GOOGLE_OAUTH_ID`, `GOOGLE_OAUTH_SECRET` y `GOOGLE_REDIRECT`. El refresh token se obtiene al conectar la cuenta y se guarda cifrado; no se introduce en el comando ni se muestra en pantalla. No utilizar `GOOGLE_CONTACT_TOKEN` como solución permanente.
- En Google Cloud: comprobar People API, consentimiento, usuarios permitidos y redirect URI. La configuración efectiva se verifica al probar OAuth; no se puede dar por validada leyendo `.env`.

## 12. Implementación y pruebas por entregas

1. Reparar Google: servicio, OAuth persistente, permisos, enlace existente y sincronización individual en Queue. Pruebas HTTP simuladas antes de cualquier prueba externa.
2. Añadir bloqueo, reconciliación de resultados inciertos, reintentos acotados, lote y panel de estado.
3. Completar preferencias de N apoderados, nombres visibles, notas, foto y cumpleaños.
4. Extender cumpleaños y faltas sobre tablas actuales, confirmación manual y correcciones.
5. Corregir saldos y conectar recordatorios internos con pagos reales.
6. Integrar Centro, dashboard y Scheduler; comprobar desktop, tablet y móvil.
7. Revisar permisos, datos sensibles, consultas, reintentos y compatibilidad con entradas existentes.

Archivos de prueba previstos: `tests/Unit/TelefonoServiceTest.php`, `tests/Unit/GoogleContactsServiceTest.php`, `tests/Feature/GoogleContactsTest.php`, `tests/Feature/ComunicacionesTest.php` y `tests/Feature/CobranzasTest.php`. Reparar la referencia inválida en `tests/Unit/PostGradoTest.php` sin esconder el fallo.

Las pruebas con datos necesitan una base aislada: el `phpunit.xml` actual no configura otra base. Usar un perfil de pruebas que prohíba el entorno de trabajo y probar con el mismo motor para verificar índices y bloqueos. No ejecutar `RefreshDatabase`, migraciones destructivas o el seeder general contra la base existente.

Criterios: crear/actualizar/repetir sin duplicar; manejar expiración/revocación/conflicto/timeout; conservar contactos inactivos; distinguir personas con teléfono compartido; usar foto propia; impedir saludos anuales repetidos; cancelar avisos corregidos; mantener historial enviado; comprobar pago parcial/completo y condonación; codificar WhatsApp; comprobar accesos denegados. Ninguna prueba debe enviar mensajes reales ni eliminar contactos Google.
