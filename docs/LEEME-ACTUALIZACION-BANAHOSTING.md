# Actualizar Google Contactos en ife.bo

Preparado el 26 de octubre de 2026 para la instalación existente en BanaHosting. El flujo elegido es **commit y push en local → pull en el servidor → migraciones y configuración**. Las operaciones en ife.bo aún no se han ejecutado desde esta sesión.

## Qué incluye

Cuenta Google central, cola compartida, pausa administrativa, funcionamiento por Cron, Personas de todos los papeles, Prospectos y consultas. Añade nombres con código IFE, parentescos, servicios registrados, cumpleaños y fotografía propia cuando existan. Se conservan los contactos sin teléfono y los enlaces anteriores. Los datos antiguos no se exportan masivamente por instalar esta entrega.

Los envíos y el centro de comunicaciones de cumpleaños, faltas y cobranzas pertenecen a otra entrega. Esta actualización no los activa.

Validación local: 122 pruebas, 634 aserciones; seis escenarios de migración y recuperación con fechas antiguas en MariaDB, dependencias y sintaxis comprobadas. Una resincronización real del registro 26 conservó el contacto existente. El ZIP anterior queda como alternativa opcional; no es necesario para actualizar por Git.

## 1. Publicar desde local

La rama de trabajo de esta entrega es `main` y el remoto configurado es `origin` (`institutoife/sistemaite`). Revisar y confirmar los archivos de la entrega; después publicar:

```sh
git push origin main
```

`.env`, tokens, archivos subidos y el ZIP generado quedan fuera del commit de esta integración. No hace falta subir una copia de la base de pruebas al servidor.

## 2. Obtener los cambios en el servidor

1. Desde cPanel, respaldar los archivos y la base de **ife.bo**. Mantener la instalación en su procedimiento habitual de mantenimiento mientras se reemplaza el código y migra el esquema.
2. Confirmar el directorio que contiene `artisan`, el PHP CLI que usa el sitio y el intervalo/tiempo permitido por Cron en esta cuenta. No se conocen todavía esas rutas ni límites particulares del plan.
3. Entrar en la carpeta real del repositorio del servidor, donde se encuentra `artisan`, y comprobar su rama y modificaciones locales:

```sh
git status --short
git branch --show-current
git pull --ff-only origin main
```

El ejemplo corresponde a la rama `main`. Si el servidor utiliza otra rama de despliegue, integrar esta entrega en esa rama antes del pull. Si Git informa cambios locales o divergencias, resolverlos conservando los cambios del servidor antes de continuar; no usar `reset --hard` ni sobrescribirlos automáticamente.

4. Conservar `.env`, `APP_KEY`, base de datos, `storage`, fotos y archivos subidos del servidor. El pull de esta entrega no sustituye esos datos ni instala dependencias. El cambio previo de diseño en `resources/views/home/public/service.blade.php` queda fuera del commit de la integración.

## 3. Configuración del servidor

Incorporar las variables de [ife-bo.env.example](../scripts/hosting/ife-bo.env.example) al `.env` **existente**; no reemplazar el archivo completo. Configurar el cliente OAuth del servidor sin pegar tokens en archivos ni comandos. Conservar las credenciales DB y la clave de cifrado actuales.

El código se probó con PHP **8.2.12** y Laravel **8.83.27**, sin actualizar dependencias. Usar el mismo `composer.lock` compatible con esta instalación. Cuando Composer esté disponible, el script ejecuta `composer check-platform-reqs --no-dev`. Si no está en PATH, puede utilizar el `vendor` existente únicamente después de verificar sus versiones, extensiones PHP y carga de todas las clases de esta entrega. Si las dependencias del servidor difieren, resolver esa diferencia antes de aplicar el código; no ejecutar un `composer update` general para instalar esta entrega.

El directorio público del dominio debe mantener la configuración Laravel existente. No publicar `.env`, `storage/logs`, `docs` ni la raíz privada del proyecto. Los procesos PHP web y CLI deben tener las extensiones y permisos indicados por `gcontact:check`; las fotos requieren GD y el disco público local existente.

## 4. Aplicar migraciones y preparar Laravel

Después del pull y de ajustar el `.env` del servidor, ejecutar desde el directorio real del proyecto con el PHP del sitio:

```sh
sh scripts/hosting/deploy-google-contacts.sh
```

El script busca `composer` en PATH o `composer.phar` en el proyecto. Si no encuentra ninguno, comprueba las dependencias existentes y continúa sin el ejecutable de Composer cuando son compatibles. Para comprobar únicamente esa alternativa, sin migrar ni arrancar Laravel:

```sh
php scripts/hosting/check-existing-vendor.php
```

Si el autoload del servidor fue generado en modo autoritativo y no reconoce las clases nuevas, o faltan paquetes, será necesario utilizar Composer para corregirlo. El script se detiene antes de las migraciones en ese caso. Referencia: [autoload autoritativo de Composer](https://getcomposer.org/doc/articles/autoloader-optimization.md).

Si BanaHosting requiere rutas específicas de los ejecutables:

```sh
PHP_BIN=/RUTA/REAL/AL/PHP COMPOSER_BIN=/RUTA/REAL/A/COMPOSER sh scripts/hosting/deploy-google-contacts.sh
```

**Sustituir las dos rutas antes de ejecutarlo.** `COMPOSER_BIN` también acepta la ruta a un archivo `.phar`, que se ejecutará con `PHP_BIN`. Si únicamente cambia PHP, basta con `PHP_BIN=/RUTA/REAL/AL/PHP sh scripts/hosting/deploy-google-contacts.sh`. No definir `COMPOSER_BIN` con una ruta ficticia. Si no se usa el script, la [guía técnica de despliegue](google-contacts-despliegue-ife-bo.md) contiene las migraciones y operaciones equivalentes.

El script comprueba dependencias, limpia la configuración previa y regenera el autoload cuando Composer está disponible; en la alternativa verificada conserva el autoload existente y ejecuta `package:discover`. Aplica las cinco migraciones nuevas de Google y las dos migraciones anteriores de columnas de enlace/cola si todavía estuvieran pendientes. Después ejecuta `GoogleContactsSeeder`, limpia rutas/vistas y reconstruye la caché de configuración. Laravel omite las migraciones ya registradas. No utiliza `route:cache`: el proyecto conserva rutas con closures. No ejecuta otras migraciones pendientes, el seeder general ni genera otra `APP_KEY`.

Las migraciones se ejecutan mediante `php artisan gcontact:migrate --force`. Este comando conserva las fechas heredadas que MySQL/MariaDB pueden rechazar al reconstruir tablas, como `1900-01-00`: ajusta únicamente la aceptación de fechas de su sesión durante el cambio de esquema, mantiene el modo estricto y restaura el modo SQL original al terminar, incluso si falla. No cambia fechas, enlaces Google, la configuración global de MySQL ni las migraciones históricas.

Si `2026_10_25_000002_extend_personas_google_contacts` falló con el error 1292, ejecutar de nuevo el pull y el script habitual. MySQL puede haber añadido columnas antes de fallar al crear la clave foránea. El comando comprueba lo existente, completa las columnas, clave e índices que faltan y registra esa migración solamente después de verificar su estructura. Si encuentra una definición incompatible, se detiene para revisión. No borrar columnas, modificar fechas masivamente ni ejecutar rollback para reintentar.

Si un paso falla, detener la activación del Cron y resolver el resultado indicado. El script no realiza una restauración automática ni declara terminado el mantenimiento; ese paso corresponde al procedimiento de despliegue del sitio.

## 5. Conectar Google y activar un único Cron

En el mismo proyecto de Google Cloud, habilitar People API y registrar exactamente:

```text
https://ife.bo/auth/google/callback
```

Revisar el estado de publicación del consentimiento y la autorización de la cuenta central para operación permanente. Desde AdminLTE de ife.bo, entrar como administrador y conectar/reconectar esa cuenta si hace falta. No usar una cuenta diferente por cada operador.

Ejecutar:

```sh
/RUTA/REAL/AL/PHP artisan gcontact:check --production
```

La salida debe resolver todos los **FALTA**. La primera actividad del proceso puede aparecer pendiente hasta que se ejecute Cron. El comando no modifica datos ni llama a Google; tampoco comprueba si la tarea está registrada en cPanel.

En **cPanel → Cron Jobs**, crear **una sola tarea**. Propuesta: `*/5 * * * *`, únicamente si el plan admite ese intervalo y una operación de hasta 150 segundos. Hacer coincidir `GOOGLE_CONTACTS_CRON_INTERVAL` con el intervalo real. Comando, sustituyendo las rutas:

```sh
/RUTA/REAL/AL/PHP /home/USUARIO/RUTA_DEL_PROYECTO/artisan gcontact:work --cron >> /home/USUARIO/RUTA_DEL_PROYECTO/storage/logs/google-contacts-cron.log 2>&1
```

Cada turno procesa hasta tres trabajos y sale si la cola está vacía. El límite de 50 segundos se comprueba entre trabajos: una operación iniciada puede durar más. No instalar un worker permanente adicional en este alojamiento. El bloqueo de la instalación impide que dos turnos administrados procesen a la vez.

## 6. Comprobación final en ife.bo

Con un registro autorizado, solicitar una sincronización desde el panel y verificar nombres/código, notas, teléfono, cumpleaños y foto propia disponibles. Repetir y comprobar que sigue siendo el mismo contacto. Probar una ficha sin teléfono y un apoderado sin foto; no deben borrarse sus datos remotos ni heredarse la imagen del estudiante.

Verificar la señal **Tarea programada al día** después del turno, permisos de otro operador y pausa/reanudación administrativa. Por último, comprobar que la tarea continúa con esta PC apagada. Cuando ife.bo pase a ser la instalación operativa, dejar pausada la copia local si conserva la misma cuenta Google.

Para revertir código, primero detener el Cron y usar el respaldo de archivos revisado; conservar metadatos y datos persistidos mientras se decide la recuperación. No ejecutar un rollback general de migraciones, borrar contactos ni vaciar tablas de colas compartidas.

Detalles funcionales: [Contactos completos](google-contacts-contactos-completos.md), [Orígenes y teléfonos](google-contacts-origenes-y-telefonos.md) y [Operación compartida](google-contacts-operacion-compartida.md).
