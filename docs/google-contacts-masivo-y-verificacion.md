# Sincronización masiva y verificación de teléfonos

Entrega del 27 de octubre de 2026. Se instala sobre el sistema existente mediante Git y dos migraciones incrementales nuevas. Instalarla no inicia una exportación histórica.

## Importar teléfonos existentes

Como **Admin**, entrar a **Google Contactos → Sincronizar masivamente**. El diálogo muestra cantidades por Personas, Prospectos y consultas web no convertidas. Elegir el origen y un máximo de 100, 500, 1.000 o todos los existentes. Recomendación operativa: comenzar con 100 y comprobar sus resultados antes de importar el resto.

Por defecto se incluyen fichas sin sincronización previa, en estado inicial o pendiente. Actualizar fichas ya sincronizadas requiere marcar esa opción. Los teléfonos vacíos o compuestos solo por ceros quedan fuera; los inválidos se omiten al revisar cada turno. Los errores definitivos necesitan atención individual. Compartir teléfono no fusiona identidades distintas ni evita las comprobaciones de identidad existentes.

El botón registra un lote persistente y vuelve al panel. El Cron existente prepara y procesa pequeñas cantidades, aunque el usuario cierre la página o apague su PC. Los contadores muestran incluidos, sincronizados, pendientes, errores y omitidos; **Actualizar estado** vuelve a consultarlos. Un doble clic o dos administradores concurrentes conservan el mismo lote activo. Se guardan límites de ID por origen para no incorporar al lote las altas posteriores, que siguen su flujo automático.

**Detener siguientes lotes** frena la incorporación de más fichas históricas. Los trabajos ya encolados continúan y las altas y cambios diarios permanecen activos. **Pausar sincronización**, en cambio, conserva la pausa global anterior. Al reanudarla continúa el lote activo.

Se mantiene una cuenta central y un único Cron. `GOOGLE_CONTACTS_CRON_MAX_JOBS=3` conserva el valor anterior: hasta tres trabajos cada cinco minutos, compartidos con la actividad diaria. El límite de tiempo se comprueba entre trabajos. A ese ritmo, 1.000 contactos necesitan como mínimo unas 28 horas; las búsquedas en Google, los reintentos y las operaciones diarias pueden alargarlo. No aumentar la concurrencia: Google recomienda [mutaciones consecutivas sobre una misma cuenta](https://developers.google.com/people/v1/contacts). El máximo por turno puede ajustarse entre 1 y 50 después de medir los tiempos y confirmar los límites del alojamiento.

La sincronización preserva enlaces Google, fotos y fechas según las reglas existentes. No marca un teléfono como confirmado por el cliente. El lote puede terminar con fichas que requieren atención; «Finalizado» significa que ya no queda trabajo pendiente en ese lote.

## Consultar y confirmar con el cliente

**Admin** y **Secretaria** reciben el permiso `Verificar Telefonos`. El modal aparece al abrir la ficha de Persona, revisar sus opciones académicas o sus teléfonos y al obtener una única coincidencia en el buscador de estudiantes de Inicio. También se integra con las consultas de Persona por AJAX utilizadas en esas pantallas. El icono de teléfono con marca permite solicitar una revisión manual desde las acciones de Persona, incluso si existe una confirmación reciente.

El modal consulta primero a la persona y después a sus apoderados vinculados. Muestra su código y nombre para evitar confirmar el número de otro familiar. Permite:

- **Sigue con este número**: confirma el teléfono existente sin sincronizarlo otra vez si no cambió.
- **Guardar y confirmar**: registra un número boliviano válido, actualiza las referencias redundantes de los hijos si corresponde y solicita su sincronización Google después de guardar la transacción.
- **Ahora no** o cerrar: posterga el aviso para ese usuario durante 24 horas. No confirma un número ausente. Escape permite salir incluso sin conexión.

Se recomienda revisar cada **seis meses** como regla operativa del instituto. Es configurable mediante `PHONE_VERIFICATION_MONTHS=6` (entre 1 y 24) y la caché de configuración debe regenerarse después de cambiarlo. Una confirmación se comparte entre las PCs: otra operadora no recibe el mismo aviso dentro del plazo. Se guarda quién confirmó, cuándo y una huella del número; cambiarlo desde otra pantalla invalida automáticamente la confirmación anterior. Si otro usuario cambia el teléfono mientras el modal está abierto, se pide volver a consultar y se evita sobrescribirlo con información antigua.

Las fichas migradas parten sin confirmación. Si carecen de teléfono o el número es inválido, se solicita uno al revisarlas, sin impedir consultar el resto del sistema. La confirmación registra lo informado por el cliente; no envía mensajes ni comprueba la titularidad por SMS. Prospectos y consultas participan en la importación histórica; este modal corresponde a Personas y apoderados.

## Actualización en BanaHosting

Desde la terminal del alojamiento:

```sh
cd /home/vaninquz/ife.bo/sistemaite
git pull --ff-only origin main
PHP_BIN=/opt/cpanel/ea-php82/root/usr/bin/php sh scripts/hosting/deploy-google-contacts.sh
```

El script aplica únicamente las migraciones pendientes de esta integración, asigna permisos y regenera configuración/vistas. Las dos nuevas crean tablas de verificación, postergaciones y lotes; no reconstruyen `personas` ni alteran las fechas heredadas. Se conserva el Cron `*/5` existente y la conexión Google actual; no crear una segunda tarea.

Comprobar con un estudiante y un apoderado: confirmar, volver a consultar con otro usuario, cambiar un número y verificar su posterior estado Google. Probar una ficha sin teléfono. Después iniciar un lote de 100, esperar los turnos del Cron y revisar sus contadores y contactos. Esta entrega se ha probado localmente; el despliegue y el inicio del lote real se realizan en el servidor.

## Validación local

143 pruebas y 837 aserciones con SQLite en memoria y HTTP simulado. Cubren permisos, privacidad, seis meses, postergación por usuario, cambios concurrentes, actualización de apoderados y cola después del commit; además lotes de los tres orígenes, límites, continuidad, pausa, detención, capacidad compartida y conservación de enlaces.

`php scripts/testing/check-google-contacts-migrations.php` comprueba seis escenarios de migración en bases temporales MariaDB, fechas/enlaces conservados y progreso real del lote sin HTTP externo ni trabajos reales. No se ejecuta sobre la base del sistema. El modal se revisó adicionalmente en un navegador con datos y respuestas simuladas.
