# Google Contactos: orígenes y teléfonos incompletos

Fecha: 26 de octubre de 2026.

Esta entrega amplía las altas y actualizaciones a todas las fichas de Persona, Prospectos y consultas web/internas. El usuario confirmó expresamente incluir los dos últimos orígenes. Se reutilizan sus tablas, formularios y permisos; no se crea otra ficha de Persona para representar un prospecto.

Ampliación posterior del mismo día: [Contactos completos](google-contacts-contactos-completos.md) incorpora nombres/códigos, parentescos, notas, cumpleaños y fotografías. El alcance y las 90 pruebas que se describen aquí corresponden al bloque de orígenes; la entrega actual utiliza además la quinta migración fotográfica y la [guía de actualización](LEEME-ACTUALIZACION-BANAHOSTING.md).

## Entradas auditadas

| Entrada del sistema | Escritura existente | Sincronización |
| --- | --- | --- |
| Persona completa: estudiantes, computación, docentes, administrativos, proveedores y clientes de servicios | `PersonaController::store/update` | Observer de Persona; los papeles no restringen la elegibilidad. |
| Alta y edición desde Docentes | `DocenteController::store/update` | Se sigue la Persona relacionada. |
| Clientes potenciales del CRM y alta rápida | `guardarRapidingo/actualizarRapidingo` | Incluidos aunque todavía no tengan fila en Estudiante ni Computación. |
| Contacto independiente | `guardarSoloContacto/actualizarSoloContacto` | Incluido como Persona. |
| Apoderado nuevo | `storeContacto` | Una Persona compartida, aunque represente a varios estudiantes. |
| Apoderado existente, formulario y AJAX; edición y desvinculación | `TelefonoController` | Cambios personales observados y vínculos marcados explícitamente; desvincular no elimina el contacto Google. |
| Agregar un papel a una Persona existente | Creación de sus relaciones de papel | Marca la Persona pendiente. |
| Prospectos con teléfono | `ProspectoController::store/update` | Observer de Prospecto; contacto con teléfono, sin inventar un nombre. |
| Consultas públicas, internas y sus cambios de nombre/teléfono | `ComentarioController` | Observer de Comentario mientras no esté vinculado a Persona. |
| Convertir una consulta en estudiante | `estudiantizarComentario` prepara el formulario; `PersonaController::store` guarda y vincula | La Persona pasa a ser el origen del contacto; recupera el enlace de la consulta. |

Las fotos y fechas pueden marcar cambios mediante el observer existente; la exportación actual sigue siendo de nombres y teléfonos. La exportación de fotos, cumpleaños y notas pertenece a otra entrega.

## Datos migrados sin teléfono

- `NULL`, vacío, espacios y valores compuestos solo por ceros se consideran teléfono ausente. No se agrega un número ficticio.
- Una Persona con nombre puede sincronizarse sin teléfono. Si ya existe su contacto Google, se omite `phoneNumbers` tanto del contenido de actualización como de su máscara: el número remoto se conserva.
- Al registrar después un teléfono válido, se actualiza el mismo recurso Google. Un formulario de edición que no envía el campo no borra el número local; enviar el campo vacío permite dejarlo vacío localmente y conserva el remoto.
- Un valor presente pero inválido queda en **Requiere atención**; no se trata silenciosamente como dato faltante.
- Los formularios de Persona, contactos, apoderados y edición de Docentes permiten teléfono opcional. Se conservan sus límites de longitud. Prospectos continúa requiriéndolo porque es su único dato de identidad; las consultas web conservan sus reglas previas de entrada.
- Antes de crear, un contacto antiguo coincidente sin identificador IFE se deja para revisión. Con nombre local y sin teléfono, se contempla que Google sí conserve un número perdido durante la migración. Los prospectos sin nombre también se detienen ante un teléfono coincidente; no se fusionan personas por teléfono.

La falta de un teléfono local no autoriza a recuperar automáticamente un número desde Google, fusionar fichas ni borrar números remotos.

## Conversión y cola compartida

Los trabajos y marcadores diferencian `persona`, `prospecto` y `comentario`; un mismo ID numérico en tablas distintas no se confunde. Se conservan los Jobs de Persona serializados antes de esta ampliación.

La conversión se guarda en una transacción y los trabajos se publican después del commit. Bajo el bloqueo de la cuenta, la Persona recupera el recurso o la intención de creación de sus consultas vinculadas. Se mantienen sus marcadores anteriores y se incorpora el de Persona. Esto también cubre una creación de consulta que termina después de vincularla al estudiante.

Una creación incierta sin resultado confirmado no se repite a ciegas. Si la Persona y una consulta tienen enlaces distintos, se detiene por conflicto sin adoptar parcialmente uno de ellos. No se borran ni fusionan esos contactos automáticamente.

El proceso administrado recupera pendientes de los tres orígenes, respetando pausa global, límite de lote y errores definitivos. Una consulta convertida deja de aparecer como origen independiente.

## Uso y datos anteriores

En **Google Contactos** están las pestañas **Personas**, **Prospectos** y **Consultas web**, además del filtro **Solo registros sin teléfono**. Los contadores superiores suman los tres orígenes y no dependen del filtro. Las solicitudes manuales requieren `Sincronizar Contactos`; pausa/reanudación sigue siendo global y exclusiva del administrador.

Crear o cambiar un nombre/teléfono en los formularios existentes marca el registro pendiente. Los datos antiguos no se encolan masivamente al desplegar: pueden revisarse y solicitarse desde el panel. La migración deja los prospectos y consultas anteriores en `idle`, sin pendientes nuevos.

Para un lote deliberado, el comando existente admite un origen explícito. Sin `--source` mantiene Persona por compatibilidad:

```sh
php artisan gcontact:sync-batch --source=persona --batch=100
php artisan gcontact:sync-batch --source=prospecto --batch=100
php artisan gcontact:sync-batch --source=comentario --batch=100
```

Agregar `--only-pending` recupera únicamente estados pendientes/idle, sin reiniciar errores definitivos. `--from-id` permite continuar un lote. Estos comandos encolan; el proceso del servidor realiza las llamadas Google.

## Correcciones y migración

Se corrigieron la ruta PATCH de edición de Prospectos para actualizar la ficha indicada, el retorno prematuro de `/comentario/guardar` que no persistía y un middleware inexistente de `/comentario/crear`. Esta última ruta mantiene su validación reCAPTCHA; las respuestas públicas devuelven campos de negocio y excluyen metadatos Google nuevos. `/comentario/guardar` requiere POST y permanece dentro del grupo autenticado existente.

Migración incremental aplicada en MariaDB local:

```sh
php artisan migrate --path=database/migrations/2026_10_26_000001_extend_leads_google_contacts.php
php artisan queue:restart
```

Agrega únicamente metadatos e índices de sincronización a `prospectos` y `comentarios`. Requiere las tres migraciones Google anteriores. Las instrucciones completas para `ife.bo` están en [Despliegue en BanaHosting](google-contacts-despliegue-ife-bo.md); esta entrega todavía no está instalada en ese alojamiento.

Validación: **90 pruebas y 491 aserciones correctas**, con SQLite en memoria, cola de base de datos aislada y HTTP simulado. Incluyen altas y actualizaciones, identidad por origen, ausencia de teléfono, preservación del remoto, coincidencias antiguas, conversión durante una llamada, resultado incierto, permisos, CAPTCHA rechazado, rollback, pausas y Cron. No se exportaron registros ficticios a la cuenta Google real.
