# CRM IFE: actualización y operación

La [mejora del 30 de octubre de 2026](crm-ife-mejoras-2026-10-30.md) actualiza responsables, servicios múltiples, sucursales, estadísticas por usuario, históricos sin responsable y eliminación de etapas. Sus reglas reemplazan las indicaciones anteriores sobre selección de responsable y programación manual de la hora de la gestión. El comando de actualización sigue siendo el mismo.

## Actualizar ife.bo en BanaHosting

Esta entrega actualiza el sistema existente. Primero conserve un respaldo de la base y los archivos del servidor. Desde su terminal habitual:

```sh
cd /home/vaninquz/ife.bo/sistemaite
git pull --ff-only origin main
PHP_BIN=/opt/cpanel/ea-php82/root/usr/bin/php sh scripts/hosting/deploy-crm.sh
```

El script comprueba PHP y las dependencias instaladas, reutiliza la preparación Google, ejecuta únicamente la migración nueva del CRM, agrega catálogos/permisos e incorpora los Potenciales existentes. Limpia vistas, regenera configuración y pide reiniciar los workers. Si Composer no está disponible, usa la comprobación de `vendor` existente. No instala paquetes nuevos. Un autoload autoritativo que no pueda cargar clases nuevas se detiene antes de migrar y debe regenerarse con Composer.

`crm:prepare --force` puede repetirse: la migración queda registrada, los catálogos conservan sus modificaciones y no se duplican las oportunidades vinculadas a Persona. El modo temporal de compatibilidad permite ampliar tablas que contienen fechas antiguas inválidas y restaura `SQL_MODE` al terminar; no corrige ni sustituye esas fechas. No ejecute un `migrate` general para esta actualización.

La preparación no solicita sincronizaciones ni envía mensajes. Los registros pendientes de Google se siguen procesando mediante el Cron existente cada cinco minutos; no agregue otro worker. Las PCs de los usuarios solo necesitan acceder a ife.bo.

## Acceso y prueba inicial

Entre como Admin o Secretaria y abra **CRM** (`/crm/ife`). Ambos roles pueden consultar, crear, editar, registrar actividades y convertir. **Configuración** queda reservada a Admin. Los demás roles necesitan asignación explícita de los permisos correspondientes.

1. Cree un prospecto solo con su teléfono. Si hay coincidencias, revise las fichas y confirme expresamente que corresponde conservar una oportunidad independiente.
2. Complete nombre, interés, estudiante, nivel, sede, origen, responsable, nota y próxima fecha/hora cuando los conozca. Los catálogos de servicio, origen y grado reutilizan Intereses, Cómo nos conoció y Grados del sistema.
3. Cambie de etapa arrastrando o mediante «Cambiar etapa». Abra otra pestaña, modifique la misma ficha e intente guardar desde la anterior: el sistema debe pedir actualizar, conservando el primer cambio.
4. Registre una llamada o una visita y programe la próxima gestión. Compruebe los indicadores y el historial.
5. Prepare WhatsApp; revise el texto y abra el enlace si desea enviarlo. Después del envío real, registre la actividad y marque su confirmación. Preparar o abrir el enlace no registra un envío.
6. Compruebe la baja con motivo y su reactivación. Si se solicitó no recibir mensajes, la restricción permanece hasta registrar expresamente una autorización nueva.
7. Para probar Google, solicite la sincronización desde su panel habitual y espere el Cron. Un prospecto sin nombre aparece como `Prospecto IFE | teléfono`; con nombre, como `Nombre | Prospecto IFE`. Al completar el nombre se actualiza el enlace existente. La prueba no necesita crear otro cliente OAuth ni otra cuenta central.

## Un tablero para ambos circuitos

Los Prospectos telefónicos conservan sus IDs, enlaces Google y observaciones. Los Potenciales que ya eran Personas se incorporan como oportunidades vinculadas a su ficha personal, conservando el historial previo y su preferencia de contacto. No se crea una segunda identidad en Google para esas oportunidades. Los nuevos Potenciales registrados desde Persona ingresan al tablero después de confirmar su transacción.

Los cambios de nombre o teléfono se realizan en la Persona vinculada y actualizan su oportunidad. El resto de la gestión comercial se realiza en el CRM. Los informes históricos de Potenciales siguen disponibles como consultas del circuito anterior; los cambios de etapa del CRM no cambian los estados académicos compartidos del sistema.

Sedes iniciales: **IFE Central, IFE Villa, IFE Plan**. Admin puede editar y desactivar sedes y administrar nombres, colores, orden, activación y etapa inicial. Las etapas inactivas conservan sus fichas. Para desactivar la inicial, primero seleccione otra. Una etapa con oportunidades normales no puede transformarse en etapa de conversión.

El tablero muestra hasta 50 fichas por columna; **Ver todas** abre la lista paginada con los mismos filtros. Las fichas conservan su posición cuando falla el guardado. Dos usuarios que editan a la vez reciben un aviso de conflicto en el segundo guardado.

## Actividades, bajas e historial

Las nuevas actividades reutilizan Observaciones y su auditoría de usuarios; una tabla complementaria guarda tipo, fecha/hora de la gestión, responsable, resultado y próxima acción. El historial se ordena por la fecha de la gestión y conserva también la fecha de registro. Las ediciones conservan la observación anterior. Las observaciones y gestiones anteriores de la Persona vinculada se muestran en su sección de historial, con acceso a la ficha original.

La baja guarda motivo, fecha, usuario y nota, cancela la próxima gestión y mantiene el historial. Reactivar no borra la baja registrada en la cronología. «Solicitó no recibir mensajes» restringe llamadas y preparación de mensajes comerciales y mantiene la preferencia también en Persona. Una autorización nueva debe registrarse expresamente al reactivar. Las visitas y observaciones internas siguen disponibles. No se envían mensajes automáticos.

## Convertir usando la inscripción existente

Las actividades tipadas del CRM no pueden editarse ni borrarse desde las rutas genéricas de Observaciones. Para corregir una gestión, registre una actividad nueva que explique la corrección. Las notas antiguas sin metadatos CRM conservan su comportamiento existente.

Desde el detalle o al llevar la ficha a una etapa de conversión del tablero, identifique por separado al **contacto** y al **estudiante**. Busque personas existentes por nombre, teléfono o código; abra su ficha o el proceso de inscripción mediante los enlaces del selector. Si el contacto todavía no tiene Persona, use el formulario existente desde el acceso del CRM: lleva el origen y vincula la nueva Persona a esta oportunidad antes de despachar Google. Si falta un estudiante distinto, regístrelo mediante el proceso habitual.

Complete la inscripción académica o matrícula de computación habitual. Luego elíjala en la conversión o indique su código y confirme los vínculos. Si ya existe el pago, se conserva: esta operación no registra otro. El backend comprueba que esa inscripción pertenece al estudiante elegido. Si el contacto es un apoderado distinto, se conserva o registra el parentesco explícito. El CRM no genera contratos alternativos. Consulte la [guía de confirmación desde el tablero](crm-conversion-desde-tablero.md).

La conversión guarda los IDs de Persona, estudiante e inscripción/matrícula, conserva la información comercial y reutiliza el contacto Google a través del worker central. Si existen dos recursos Google diferentes, se bloquea la conversión para revisar los vínculos, sin fusionar contactos. Un contacto Google antiguo con nombre real y sin marcador que acredite su pertenencia a IFE requiere revisión: no se sobrescribe ni se adopta únicamente por compartir teléfono. Una ficha con conversión registrada conserva ese hecho aunque se dé de baja posteriormente; no se reactiva como una nueva conversión.

## Indicadores y fechas

Los filtros de indicadores usan sede, responsable, servicio, origen y período. Las altas del período y sus agrupaciones forman la **cohorte**. La tasa es el porcentaje de esa cohorte convertido hasta el final del período. Las conversiones del período se cuentan por su fecha de conversión, aunque su alta sea anterior; el promedio mide días desde el alta CRM hasta esas conversiones y excluye intervalos inválidos. Los motivos de baja se cuentan por eventos del período, incluso si después se reactivó la oportunidad.

Activos, bajas actuales y seguimientos representan la cartera actual según los filtros de catálogo; no reconstruyen una foto histórica al cierre del período. El tablero/lista también permiten filtros por estado, etapa y agenda. Las alertas internas muestran actividades vencidas hasta ahora, asignadas al usuario actual o sin responsable.

Para Potenciales antiguos, la fecha de alta CRM es la de su incorporación al tablero; las fechas originales permanecen en Persona y su historial. Las fechas antiguas de próxima visita sin hora se incorporan a las **00:00** del día registrado: revíselas para asignar una hora útil. Fechas antiguas inválidas quedan sin próxima actividad. El responsable se recupera de la última programación histórica cuando existe; los demás datos faltantes se completan en la ficha. No se inventan sede, estudiante o nivel.

## Comprobaciones realizadas

Validaci?n final: **177 pruebas y 1149 aserciones**, todas correctas.

La suite aislada cubre Google Contacts, fotos, verificación de teléfonos, altas, coincidencias familiares, permisos, etapas, conflictos, actividades, bajas, autorización de contacto, incorporación de Potenciales, conversión, conservación de enlaces y analítica. Las pruebas bloquean HTTP externo y usan SQLite en memoria; la integración MariaDB usa bases temporales y conserva fechas/enlaces heredados. La revisión en Chrome usa fichas sintéticas y comprueba arrastre, selector móvil, devolución ante conflicto y preparación de WhatsApp, sin utilizar la base de producción.
