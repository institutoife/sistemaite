# Google Contactos en ife.bo / BanaHosting

Fecha: 26 de octubre de 2026.

Estado: código preparado y probado localmente. No se ha desplegado esta entrega ni creado tareas en la cuenta de BanaHosting. El usuario confirmó `ife.bo` como destino, BanaHosting como proveedor y el plan **Bana Professional Deluxe Unlimited SSD**. Faltan las rutas reales del proyecto y PHP CLI, y confirmar los límites de Cron de esta cuenta.

El usuario confirmó que el mismo sistema ya está instalado en ife.bo y eligió actualizar por **push local y pull en el servidor**. Empezar por [LEEME de actualización por Git](LEEME-ACTUALIZACION-BANAHOSTING.md). El ZIP es una alternativa opcional. Se completaron nombres/códigos, parentescos, servicios, cumpleaños y fotografías propias: [Contactos completos](google-contacts-contactos-completos.md).

La oferta pública actual de la familia Professional anuncia cPanel, PHP, MySQL y programación de tareas Cron. La denominación publicada actualmente difiere de la indicada por el usuario: no se atribuyen a su contrato los recursos ni límites de la oferta nueva. No se encontró publicado un intervalo mínimo ni un tiempo máximo de ejecución de Cron verificables para este contrato.

## Funcionamiento previsto

Todas las PCs acceden a `https://ife.bo`. El servidor usa su base de datos central y una única cuenta de Google. Un administrador puede pausar/reanudar la sincronización desde el panel; los demás operadores autorizados solicitan contactos. No se instala nada en las PCs de los usuarios.

La cola incluye todas las fichas de Persona, Prospectos y consultas web/internas. Los formularios marcan altas y cambios; los datos migrados sin teléfono no borran números remotos. Las consultas convertidas conservan su enlace como Persona. Ver [Orígenes y teléfonos incompletos](google-contacts-origenes-y-telefonos.md).

Para una cuenta cPanel con tareas programadas, usar:

```sh
php artisan gcontact:work --cron
```

Cada ejecución procesa como máximo tres trabajos, sale si la cola está vacía y comprueba un límite de 50 segundos entre trabajos. Una operación ya iniciada puede durar más: conserva sus límites HTTP y el timeout del Job de 150 segundos. No se interrumpe un contacto a la mitad para cumplir los 50 segundos. El intervalo y el tiempo permitido por BanaHosting deben admitir la duración de una operación.

El siguiente turno continúa con los pendientes. Los errores definitivos conservan la revisión manual. El bloqueo del sistema operativo impide dos ejecuciones administradas simultáneas en esta instalación; los Jobs únicos y el bloqueo de la cuenta Google también se mantienen. Si se pausa, la tarea termina sin reservar ni agotar intentos de los pendientes.

El panel muestra **Tarea programada al día** tras una ejecución correcta, aunque ya haya terminado el proceso. El indicador pierde vigencia al superar el intervalo configurado más 180 segundos de margen. Una tarea correcta no garantiza que todos los contactos se hayan sincronizado: consultar además sus estados y errores.

## Preparación del despliegue

1. Identificar el directorio real que contiene `artisan` y el binario PHP CLI de la cuenta, con una versión y extensiones compatibles con este proyecto. Confirmar que cPanel permite Cron Jobs y el intervalo mínimo del plan. La documentación pública de BanaHosting anuncia cPanel y Terminal SSH en hosting; no confirma los límites particulares de esta cuenta.
2. Respaldar la base y los archivos del servidor con el procedimiento del alojamiento. Desde su repositorio, comprobar la rama y cambios locales y ejecutar `git pull --ff-only origin main` cuando corresponda a esa rama de despliegue. Conservar la base real, su `.env`, `APP_KEY` y los enlaces Google existentes. No sustituirlos por los datos de pruebas de esta PC.
3. Aplicar únicamente las migraciones incrementales de esta integración que no estén aplicadas y ejecutar su seeder:

```sh
php artisan migrate --force --path=database/migrations/2026_10_25_000001_create_google_contact_accounts_table.php --path=database/migrations/2026_10_25_000002_extend_personas_google_contacts.php --path=database/migrations/2026_10_25_000003_add_google_contacts_pause_controls.php --path=database/migrations/2026_10_26_000001_extend_leads_google_contacts.php --path=database/migrations/2026_10_26_000002_add_google_contact_photo_hash.php
php artisan db:seed --class=GoogleContactsSeeder --force
```

No ejecutar `migrate:fresh`, el seeder general ni generar otra `APP_KEY`. La cola Google ya utiliza `jobs` mediante su conexión dedicada; no hace falta cambiar la conexión global de cola de otros módulos.

Si el servidor aún no aplicó las columnas previas de enlace Google y cola de Persona, el script de despliegue incluye también `2024_03_17_153820_add_column_to_table.php` y `2026_04_15_000007_add_gcontact_sync_queue_fields_to_personas_table.php`, antes de las cinco nuevas. Ejecutar el script después del pull permite aplicar solo este conjunto; Laravel omite las ya registradas.

4. Configurar estas variables en el entorno **del servidor**, sin cambiar la configuración local de pruebas:

```dotenv
APP_URL=https://ife.bo
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE_COOKIE=true
CACHE_DRIVER=file
GOOGLE_REDIRECT=https://ife.bo/auth/google/callback
GOOGLE_CONTACTS_PROCESS_MODE=cron
GOOGLE_CONTACTS_CRON_INTERVAL=5
```

Cinco minutos es el intervalo propuesto, pendiente de comprobar que el plan lo permite. El valor `GOOGLE_CONTACTS_CRON_INTERVAL` debe coincidir con el intervalo real configurado; controla el indicador del panel, no crea la tarea. Mantener las credenciales Google mediante configuración segura del servidor. El modo de producción y `APP_DEBUG=false` deben seguir el despliegue habitual del sitio.

5. En el cliente OAuth de Google Cloud, registrar exactamente `https://ife.bo/auth/google/callback` como URI de redirección autorizado. Si se adopta otro dominio canónico o un subdirectorio, ajustar tanto el servidor como Google Cloud para que coincidan. People API y la autorización de la cuenta central deben estar habilitadas. Revisar el estado de publicación del consentimiento para el uso permanente.
6. Si el servidor utiliza caché de configuración o rutas, regenerarla con su procedimiento habitual. Entrar como administrador en `ife.bo`, conectar la misma cuenta central prevista y comprobar que el panel la reconoce. La conexión no encola a todas las personas por sí sola.

La actualización incluye un script opcional que ejecuta estos pasos y `gcontact:check --production --allow-disconnected`, sin iniciar un worker ni registrar el Cron. Si encuentra Composer, comprueba la plataforma y regenera el autoload. En caso contrario valida versiones del `vendor` existente, extensiones y carga de clases antes de continuar con ese autoload y `package:discover`. Conserva rutas sin cachear porque el proyecto utiliza closures. Usar la [guía de actualización](LEEME-ACTUALIZACION-BANAHOSTING.md) para los comandos completos y ejecutar después `gcontact:check --production` con la cuenta conectada. Las dependencias locales pasaron `composer check-platform-reqs --no-dev` con PHP 8.2.12; verificar también con el PHP real de BanaHosting.

## Tarea de cPanel

Crear una sola entrada para esta cola en **cPanel → Cron Jobs**. Ejemplo para un plan que permita el intervalo propuesto:

| Campo | Valor |
| --- | --- |
| Minuto | `*/5` |
| Hora | `*` |
| Día del mes | `*` |
| Mes | `*` |
| Día de la semana | `*` |

Plantilla del comando; reemplazar las rutas antes de guardarlo:

```sh
/RUTA/AL/PHP /home/USUARIO/RUTA_DEL_PROYECTO/artisan gcontact:work --cron >> /home/USUARIO/RUTA_DEL_PROYECTO/storage/logs/google-contacts-cron.log 2>&1
```

No son rutas verificadas de esta cuenta. Usar el PHP CLI que corresponda al sitio y comprobar permisos de escritura en `storage` y `bootstrap/cache`. No colocar claves ni tokens en el comando ni llamar por HTTP a una ruta sin autenticación para procesar la cola.

No activar a la vez otro worker o una segunda tarea para la misma instalación. Este comando ya recupera pendientes antes de procesar. El Scheduler general se puede configurar aparte cuando sus otras funciones lo requieran; no es necesario para esta cola.

## Verificación en el servidor

Solicitar un contacto de prueba autorizado desde `ife.bo`, esperar al turno de Cron y comprobar su estado y datos en Google. Solicitar otra sincronización y confirmar que conserva el mismo contacto. Probar pausa/reanudación con dos usuarios autorizados y confirmar que solo el administrador dispone de los controles globales.

Comprobar después que la tarea sigue ejecutándose con esta PC apagada. Si la copia local conserva la misma cuenta y enlaces que producción, dejarla pausada cuando deje de utilizarse como instalación operativa, para que las pruebas no sigan actualizando los contactos reales.

Pruebas locales del código: **118 pruebas, 617 aserciones correctas** en SQLite en memoria y con HTTP simulado. Incluyen límites por turno, continuación sin duplicados, cola vacía, pausa, caducidad del indicador, exclusión de ejecuciones simultáneas, los tres orígenes, conversiones, datos sin teléfono, fotos/cumpleaños, notas conservadas y comprobación de despliegue sin escrituras. No sustituyen la comprobación del Cron real de BanaHosting.

Referencias: [hosting de BanaHosting](https://www.banahosting.com/es/alojamiento-web/), [Cron Jobs de cPanel](https://docs.cpanel.net/cpanel/advanced/cron-jobs/), [colas de Laravel 8](https://laravel.com/docs/8.x/queues), [OAuth de Google para aplicaciones web](https://developers.google.com/identity/protocols/oauth2/web-server).
