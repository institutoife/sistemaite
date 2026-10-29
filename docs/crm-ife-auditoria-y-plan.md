# Auditoría y plan del CRM IFE

Auditoría del 29 de octubre de 2026, realizada antes de modificar el código funcional. Se revisaron modelos, migraciones y esquema local, controladores, formularios, rutas, permisos, Inicio, inscripciones y sincronización Google. No se accedió a la base de producción.

## Diagnóstico

1. `prospectos` conserva teléfono, `estado_id` y metadatos de Google. No tiene nombre ni ficha comercial. El alta añade una Observación con usuario; la edición no comparte una ficha comercial completa. La lista hace joins obligatorios con Observaciones/usuarios, por lo que puede ocultar registros sin observaciones o repetirlos por usuario. El alta contiene títulos copiados de Provincia y la eliminación borra físicamente el Prospecto.
2. Los Potenciales actuales son Personas con Intereses, origen `como_id`, estado en `habilitado`, fecha `vuelvefecha` y permiso `votos`. Son distintos de los Prospectos telefónicos. El modelo `Potencial` no constituye la identidad utilizada en ese circuito. Los vínculos de apoderados se conservan en `persona_persona`.
3. `CrmController` filtra Personas por estados existentes. Su vista contiene un prototipo de arrastre sin persistencia de las tarjetas reales. Inicio ya registra gestiones, postergaciones y cambios de estado en `crm_seguimientos`, exclusivamente para Persona. Ese historial debe conservarse.
4. `estados` es compartido por asistencia, clases, contratos y otros módulos. No es seguro transformar ese catálogo en las nuevas etapas comerciales. Intereses (`interests`) y Orígenes (`comos`) sí tienen administración existente y deben reutilizarse. No se encontró un catálogo de sedes en el esquema local: `aulas` representa salones, no sedes.
5. Observaciones es polimórfico, auditable mediante `userables` y ya está vinculado a Prospecto y Persona. Puede conservar la cronología comercial; los campos tipados de actividad requieren una ampliación complementaria, evitando copiar observaciones antiguas.
6. Google sincroniza los tres orígenes mediante una cola común, identificadores por origen, revisión, enlace persistente y protección ante creaciones inciertas. El payload ignora el nombre de Prospecto y omite `names`; Google puede mostrar el teléfono como identificación. La consulta convertida a Persona ya conserva su enlace; Prospecto todavía no tiene esa conversión. Hay que extender ese mismo mecanismo.
7. La normalización boliviana está centralizada en `TelefonoService`. El formulario solo exige unicidad textual en Prospectos y no advierte coincidencias con Personas ni formatos equivalentes. Un teléfono compartido no acredita una identidad común.
8. Las inscripciones académicas y matrículas de computación ya existen con permisos y reglas propias. Cambiar una tarjeta a «Convertido» no demuestra una inscripción. Debe vincularse el contacto correcto, el estudiante y una inscripción real, sin crear contratos alternativos.
9. WhatsApp utiliza enlaces `wa.me` con texto preparado; abrirlos no acredita envío. La baja de comunicaciones de Personas usa `votos`. El CRM debe respetar esa preferencia y registrar una confirmación humana separada de la preparación del mensaje.

## Plan antes de modificar el código

1. Corregir nombres Google: `Nombre | Prospecto IFE` o `Prospecto IFE | teléfono`. Conservar enlaces y marcadores; preservar nombres reales cuando el dato local sea desconocido y bloquear alteraciones de contactos cuya pertenencia a IFE no pueda comprobarse.
2. Ampliar la ficha existente de Prospecto con datos opcionales, reutilizando Intereses, Orígenes, Grados y usuarios. Añadir solo los catálogos inexistentes, etapas y sedes. Compartir campos entre alta, edición y consulta. Advertir coincidencias normalizadas y exigir revisión explícita, sin fusiones automáticas.
3. Reemplazar el prototipo por un tablero único en AdminLTE, con persistencia, control de concurrencia, devolución de tarjeta ante fallos y alternativa móvil. Conservar los accesos y el historial anteriores. El alcance conjunto de Potenciales está consultado al usuario antes de decidir su incorporación.
4. Reutilizar Observaciones para la cronología y añadir metadatos tipados de actividades; mostrar también el historial previo de Persona cuando esté vinculada. Incorporar responsables, próximas acciones e indicadores internos. Preparar WhatsApp con confirmación de gestión independiente.
5. Implementar baja/reactivación auditadas, preferencia de no contactar y conversión contra los procesos académicos existentes. La vinculación explícita decide quién es el contacto y quién el estudiante. Conservar Google bajo el bloqueo de cuenta existente.
6. Añadir analítica con filtros. Separar la cohorte de registros creados de las conversiones ocurridas en el período; calcular la tasa de la cohorte y definir el promedio de conversión.
7. Probar formularios, nombres, deduplicación, permisos, transacciones, etapas, concurrencia, historial, baja, consentimiento, conversión, estadísticas y navegador móvil. Usar migraciones incrementales y el modo de compatibilidad de fechas ya probado; conservar los datos y contactos del servidor.

No se instalará Filament ni se sustituirá AdminLTE. Se mantienen los colores IFE y las dependencias actuales. Los cambios previos del usuario en el diseño de la página pública quedan fuera de esta entrega.
