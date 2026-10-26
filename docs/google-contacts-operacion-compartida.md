# Operación compartida de Google Contactos

Fecha: 25 de octubre de 2026.

Actualización del 26 de octubre: el destino confirmado es `ife.bo` en BanaHosting. Se preparó un modo por tareas programadas para cPanel; instrucciones y estado del despliegue en [ife.bo / BanaHosting](google-contacts-despliegue-ife-bo.md). La configuración Windows descrita aquí corresponde exclusivamente a la copia local.

## Uso desde el sistema

Todos los usuarios de esta instalación comparten una cuenta central, una cola y un proceso. No deben conectar una cuenta Google distinta por cada usuario del sistema.

- **Administrador:** conectar/reconectar Google, consultar resultados, solicitar contactos y usar **Pausar sincronización** / **Reanudar sincronización**.
- **Secretaria:** consultar y solicitar/reintentar contactos. El seeder agrega exclusivamente `Listar Contactos` y `Sincronizar Contactos` al rol existente, sin retirar permisos previos ni crear usuarios.
- **Otros roles:** mantienen sus permisos. La asignación explícita de permisos permite autorizar operadores adicionales; los controles de pausa global comprueban además el rol Admin en el servidor.

El panel muestra autorización Google, pausa global, señal reciente del proceso y contadores generales de pendientes, procesando, sincronizados y casos que requieren atención. Los filtros del listado no cambian esos totales. **Actualizar estado** consulta nuevamente el panel; no dispara sincronizaciones.

Ampliación del 26 de octubre: se incluyen todas las Personas, Prospectos y consultas web/internas, con pestañas por origen y filtro de registros sin teléfono. Las altas y cambios usan la misma cola central; los datos anteriores no se encolan en masa. Detalles de conversión y preservación de teléfonos en [Orígenes y teléfonos incompletos](google-contacts-origenes-y-telefonos.md).

Pausar conserva los tokens y los contactos. Los formularios siguen funcionando y los cambios quedan pendientes. Una operación que ya estaba en curso puede terminar; no se inicia la siguiente. La pausa queda persistida en la cuenta central y se conserva al reiniciar el proceso y al reconectar OAuth. La acción se registra con el ID del administrador.

Reanudar encola hasta 100 pendientes iniciales, sin reiniciar los errores definitivos. El proceso permanente revisa nuevamente los pendientes cada cinco minutos; el modo Cron los revisa al comenzar cada turno. Reanudar no inicia un proceso apagado: el supervisor o la tarea programada del servidor se encarga de ejecutarlo. La señal del proceso indica actividad del servidor, no garantiza disponibilidad de Internet ni permiso vigente de Google.

## Proceso del servidor

Comando para esta entrega:

```powershell
php artisan gcontact:work
```

Este comando usa el worker estándar de Laravel con la conexión `google_contacts`, cola `google-contacts`, cinco intentos y timeout de 150 segundos. Un bloqueo del sistema operativo evita dos instancias administradas en la misma instalación. El bloqueo por cuenta y los Jobs únicos conservan la protección frente a otros workers y clics repetidos. No ejecutar paralelamente el antiguo comando `queue:work google_contacts`.

La señal se actualiza entre trabajos y permanece válida hasta 180 segundos, para cubrir la duración de una operación. El proceso administrado no reserva trabajos mientras la cuenta esté pausada o desconectada. Si un worker antiguo ya reservó un trabajo al pausar, el Job lo deja pendiente sin error para recuperarlo al reanudar.

En hosting con Cron usar `php artisan gcontact:work --cron`: termina al vaciar la cola o alcanzar los límites entre trabajos. El panel diferencia una ejecución programada correcta de un proceso permanente en línea. No mezclar ambos modos en una misma instalación. La frecuencia y las rutas deben verificarse en el alojamiento.

## Windows local

La tarea **IFE-GoogleContacts** inicia el supervisor al iniciar sesión en Windows con el usuario que la instaló. Usa privilegios limitados, evita instancias simultáneas y permite tres reintentos del supervisor, separados por un minuto, si este falla. El supervisor reinicia PHP diez segundos después de una salida, incluida la parada de MySQL o un reinicio de cola. No depende de mantener un navegador abierto ni de quién inicia sesión dentro de IFE.

Instalación reproducible desde el proyecto:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\windows\Install-GoogleContactsTask.ps1
```

Los scripts no leen `.env` ni reciben tokens. El inicio manual del mismo supervisor es:

```powershell
powershell.exe -NoProfile -WindowStyle Hidden -ExecutionPolicy Bypass -File .\scripts\windows\Start-GoogleContacts.ps1
```

El instalador admite `-PhpPath` para otra ubicación de PHP. Se instala únicamente en la computadora que aloja esta aplicación.

Esta configuración inicia al entrar en la sesión de Windows, no antes de que una persona inicie sesión ni después de cerrar esa sesión. Apache o el servidor Laravel, MySQL e Internet deben estar disponibles; la tarea no cambia el arranque de esos servicios. Para un servidor que deba funcionar sin una sesión de Windows abierta, desplegar el mismo comando mediante un servicio del servidor bajo una cuenta autorizada.

Para una instalación distinta, configurar el proceso solo en el servidor que comparte la base y el almacenamiento de la aplicación. Si hay varios servidores, se requiere coordinar la exclusión del proceso y usar locks/caché compartidos; esta instalación utiliza un único servidor Windows.

## Migración y comprobación

Se aplicó únicamente `2026_10_25_000003_add_google_contacts_pause_controls.php`, que agrega fecha y administrador de pausa, y se ejecutó `GoogleContactsSeeder`. No cambia los registros académicos ni financieros.

Posteriormente se aplicó `2026_10_26_000001_extend_leads_google_contacts.php` para los metadatos de Prospectos y consultas, y se reinició la cola administrada para cargar el código nuevo.

La ampliación de [Contactos completos](google-contacts-contactos-completos.md) agregó la quinta migración de huella fotográfica, aplicada localmente. Los nombres/códigos, vínculos, servicios, cumpleaños y fotos propias ahora se actualizan por la misma cola.

```powershell
php artisan migrate --path=database/migrations/2026_10_25_000003_add_google_contacts_pause_controls.php
php artisan migrate --path=database/migrations/2026_10_26_000001_extend_leads_google_contacts.php
php artisan migrate --path=database/migrations/2026_10_26_000002_add_google_contact_photo_hash.php
php artisan db:seed --class=GoogleContactsSeeder
php vendor/phpunit/phpunit/phpunit -c phpunit.google-contacts.xml
```

Validación aislada actual: **112 pruebas, 605 aserciones correctas**. Incluye permisos de varios usuarios, pausa/reanudación, conservación de trabajos e intentos, finalización de la operación en curso, contadores globales, caducidad de la señal, ejecución real de Jobs simulados mediante el comando y exclusión de un segundo proceso. También se comprueban los turnos de Cron, los tres orígenes, conversiones, notas, cumpleaños, fotografías propias y comprobación de despliegue sin cambios en los datos. Las pruebas usan SQLite en memoria y bloquean solicitudes HTTP no simuladas.

Validación local: migración aplicada en MariaDB, permisos de Secretaria comprobados, tarea activa con usuario Windows `hgf`, privilegios limitados y una sola instancia PHP. Se comprobó la recuperación tras `queue:restart` y una actualización real del registro 26 desde el proceso de la tarea, conservando su identificador Google. La cola quedó vacía. No se reinició Windows ni se cerró su sesión durante esta validación.

Los procesos publican su señal sin IDs de sistema ni secretos en el panel. Los mensajes de error HTTP siguen pasando por la clasificación segura existente. No se agregan envíos automáticos de WhatsApp ni sincronización masiva de personas que no estuvieran pendientes.

Referencias: [colas de Laravel 8](https://laravel.com/docs/8.x/queues), [configuración de tareas de Windows](https://learn.microsoft.com/en-us/powershell/module/scheduledtasks/new-scheduledtasksettingsset).
