# Mejoras incrementales del CRM IFE — 30 de octubre de 2026

## Revisión previa

Se revisaron Prospecto, etapas, Observaciones y sus metadatos, permisos, formularios, conversión, estadísticas, integración Persona/Google y despliegue. La versión anterior guardaba un único `interest_id`, permitía elegir responsable al crear y cambiaba el responsable comercial al programar una actividad. Las estadísticas no separaban creador, autor y responsable de la conversión. El tablero ya tenía columnas reales y control de concurrencia: se amplía ese componente.

## Responsables e historial

El alta asigna al usuario autenticado como responsable y creador; no hay selector de responsable en creación. Se conservan por separado `creado_por`, `responsable_inicial_id`, el `responsable_id` actual, `convertido_por` y `responsable_conversion_id`. Registrar actividades o cambiar etapas no cambia el responsable. Cualquier usuario con los permisos de consulta y edición puede gestionar una ficha asignada, aunque pertenezca a otro operador.

Las nuevas actividades toman usuario, fecha y hora del servidor. No aceptan una identidad o fecha alternativa enviada por el navegador. Conservan autor, responsable comercial, sucursal y servicios vigentes al realizar la acción. El historial anterior mantiene sus fechas y datos originales. Las reasignaciones requieren permiso, motivo y versión vigente; registran el responsable anterior y el nuevo, sin reemplazar la creación ni las conversiones anteriores. Para corregir una actividad se agrega otra, conservando la auditoría.

Permisos iniciales: Admin administra etapas, consulta históricos sin responsable, reasigna y ve estadísticas generales. Secretaria conserva las operaciones comerciales y ve **Mis estadísticas**. Se pueden otorgar expresamente `Reasignar Prospectos` o `Ver Estadisticas CRM` a otros usuarios. Los permisos individuales existentes también funcionan para usuarios con otros nombres de rol.

## Servicios, sucursal y curso

Los servicios usan checkboxes y la relación `interest_prospecto`, reutilizando `interests`. El campo antiguo `interest_id` se conserva como referencia compatible al primer servicio seleccionado. Se copian una vez los servicios existentes, incluidos los intereses de Persona de los Potenciales antiguos. Repetir el despliegue no restaura servicios que luego hayan sido quitados desde el CRM.

La interfaz muestra **Sucursal**. La base conserva `crm_sede_id`, `crm_sedes` y la relación `sede`. El curso/nivel permite buscar entre los Grados existentes y seleccionar un ID validado; no crea otro catálogo.

## Históricos sin responsable

El tablero operativo y la lista muestran por defecto fichas activas con responsable asignado. Los históricos sin responsable no se borran ni se asignan automáticamente y quedan fuera de indicadores de tareas y alertas pendientes. Admin puede elegir **Asignación administrativa → Históricos sin responsable**, abrir la ficha y recuperarla mediante **Reasignar responsable comercial**, dejando un motivo. Esto no inventa un creador ni un responsable original desconocidos.

No se deduce la propiedad comercial a partir del usuario que atendió una gestión antigua. Cuando existe una auditoría inequívoca de alta o conversión se recupera su autor; los demás datos anteriores sin evidencia aparecen como **Sin registro histórico**. Las fichas existentes ya asignadas conservan esa asignación.

## Tablero y etapas

Se mantienen columnas de ancho fijo, barra horizontal visible, controles anterior/siguiente y desplazamiento al acercar una tarjeta al borde durante el arrastre. El selector móvil sigue disponible. Se conserva la posición horizontal por pestaña y combinación de filtros; los filtros continúan en la URL. Los conflictos de versión devuelven la tarjeta a su posición anterior.

Admin puede crear, editar y **eliminar del tablero** una etapa. La eliminación la archiva con `deleted_at`, conservando su identidad histórica. Si tiene fichas, debe seleccionarse un destino activo compatible; se trasladan con auditoría y mantienen sus responsables. Se protege la etapa inicial y la última etapa activa de conversión hasta configurar otra. Repetir la preparación no recrea las etapas archivadas.

## Estadísticas y seguimiento

**Indicadores** distingue nuevos registrados por creador; actividades y cambios de etapa por autor; activos, pendientes y vencidos por responsable actual; conversiones registradas por autor; conversiones por responsable comercial de ese momento; y fichas dadas de baja por el autor de la baja. Se muestran acciones de cambio y fichas distintas movidas por separado. Los movimientos repetidos no crean prospectos ni ventas adicionales.

La tasa por responsable usa una cohorte de fichas distintas recibidas en el período mediante alta o reasignación antes de la conversión. Cuenta conversiones confirmadas a cargo de ese responsable hasta el cierre del período. Recibir nuevamente la misma ficha no duplica esa cohorte. Las conversiones de fichas recibidas antes se muestran en el conteo de conversiones del período y no se fuerzan en la tasa de una cohorte nueva. Una reasignación posterior a la venta no cambia la atribución ni agrega una recepción comercial.

Las actividades históricas se filtran por fecha, usuario, sucursal y servicio guardados en el evento. Las cifras actuales de cartera usan la asignación, sucursal y servicios actuales. Si falta una instantánea histórica antigua no se sustituye con el responsable actual. Las conversiones requieren el proceso existente de inscripción o matrícula confirmada; arrastrar una tarjeta no concreta una venta.

Las tarjetas, la lista y el detalle muestran días sin seguimiento. Se toma el último contacto registrado —llamada, intento no contestado, WhatsApp confirmado, información, visita o cotización— o el tiempo desde el alta si aún no hubo contacto. Una observación interna, programación o cambio de etapa no reinicia ese contador. Fechas heredadas inválidas se muestran sin atribuir una antigüedad inventada.

## Actualización en BanaHosting

Después del respaldo habitual:

```sh
cd /home/vaninquz/ife.bo/sistemaite
git pull --ff-only origin main
PHP_BIN=/opt/cpanel/ea-php82/root/usr/bin/php sh scripts/hosting/deploy-crm.sh
```

El mismo script aplica las migraciones CRM del 29 y 30 de octubre que falten y ejecuta la preparación idempotente. La nueva migración agrega campos, índices y la relación de servicios; conserva los datos anteriores y utiliza la compatibilidad de fechas heredadas existente. No cambia credenciales, enlaces Google ni la tarea Cron y no exporta contactos masivamente ni envía mensajes.

Prueba sugerida: crear un prospecto con dos servicios; registrar una llamada desde otro usuario y comprobar que conserva responsable; reasignarlo expresamente y revisar el historial; consultar Mis estadísticas; recuperar un histórico sin responsable; y eliminar una etapa de prueba eligiendo destino. La conversión continúa usando una inscripción o matrícula real.

## Validación

Suite completa: **188 pruebas y 1291 aserciones**, todas correctas. Comprobación adicional de 43 pruebas tras proteger la ventana entre publicación y migración; seis escenarios MariaDB con fechas/enlaces heredados, servicios múltiples, estadísticas y archivado; revisión en Chrome de siete columnas, desplazamiento, posición persistida, arrastre, selector móvil, conflicto, checkboxes y búsqueda de curso. La copia local quedó preparada sin crear trabajos Google ni llamadas externas.
