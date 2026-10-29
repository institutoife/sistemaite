# Fotografías ausentes y diagnóstico invalid_photo

Actualización del 29 de octubre de 2026. El usuario informó que la fotografía del registro 8266 también aparece rota al consultar su ficha en ife.bo. Eso apunta a un archivo ausente, inaccesible o ubicado en otra carpeta del servidor; el error original se producía al leer el archivo local antes de llamar a Google. Esta sesión no accede al servidor ni recupera los archivos originales.

## Comportamiento corregido

El lector busca el nombre y la ruta exactos guardados en `Persona.foto`, primero en el disco público configurado y después en las carpetas públicas `storage` y `foto` del proyecto. Es compatible con instalaciones donde `public/storage` es una carpeta real y con prefijos locales antiguos como `storage/estudiantes/archivo.jpg`. Conserva la prioridad del archivo canónico si existen dos ubicaciones y no busca coincidencias solamente por nombre de archivo.

Solo acepta archivos dentro de esos directorios de imágenes. Rechaza URLs, rutas fuera de ellos, caracteres nulos y enlaces que escapen del directorio. No descarga fotos ni toma imágenes de otros familiares. Mantiene la validación JPG/PNG/WebP, 5 MB y 20 megapíxeles, con salida JPEG de hasta 720 píxeles por lado.

Si el archivo falta, está dañado, no puede leerse o no puede decodificarse, se sincronizan nombre, teléfono y los demás datos válidos. La ficha muestra **Sincronizado · revisar foto**, explica la causa y mantiene `invalid_photo` como diagnóstico de la imagen. Una instalación sin GD muestra un aviso equivalente con `photo_extension`. Se conserva la foto que ya exista en Google y su huella local; no se borra ni se sustituye por una imagen predeterminada.

Los lotes masivos contabilizan estas fichas como sincronizadas y muestran por separado cuántas fotos requieren revisión, sin descontarlas dos veces del total pendiente. Los errores de Google al subir una imagen válida conservan sus reintentos y el enlace permanente anteriores.

Al volver a guardar una foto válida y solicitar su sincronización, se procesa sobre el mismo contacto. El aviso desaparece después de una sincronización correcta. Si el archivo original ya no está en el servidor, hay que restaurarlo desde un respaldo o volver a subirlo desde la ficha; esta corrección no puede reconstruirlo.

## Desplegar

No hay migraciones ni dependencias nuevas. Desde la terminal de BanaHosting:

```sh
cd /home/vaninquz/ife.bo/sistemaite
git pull --ff-only origin main
PHP_BIN=/opt/cpanel/ea-php82/root/usr/bin/php sh scripts/hosting/deploy-google-contacts.sh
```

Se conserva el Cron existente. Después, solicitar sincronización de 8266 desde el panel para comprobar **Sincronizado** o **Sincronizado · revisar foto**, según se encuentre su archivo. Confirmar el resultado en Google Contactos.

## Comprobar una fotografía sin modificar datos

```sh
ea-php82 artisan gcontact:photo-check 8266
```

Este comando indica si el archivo puede localizarse y prepararse, la ubicación pública utilizada cuando existe y la disponibilidad de GD/restricción de lectura. Distingue archivo ausente, ruta no permitida, archivo ilegible, tamaño excesivo e imagen inválida. No muestra credenciales, nombres ni teléfonos; no modifica registros, encola trabajos ni llama a Google. Un archivo ausente o inválido devuelve código de salida 1 aunque los demás datos del contacto ahora puedan sincronizarse.

## Recuperar fichas que quedaron bloqueadas antes de esta corrección

Vista previa de hasta 100 fichas con estado de error y diagnóstico `invalid_photo` o `photo_extension`:

```sh
ea-php82 artisan gcontact:sync-batch --only-photo-errors --batch=100 --dry-run
```

Para solicitar su procesamiento con el Cron existente:

```sh
ea-php82 artisan gcontact:sync-batch --only-photo-errors --batch=100
```

El comando conserva los enlaces Google y excluye errores distintos, como fechas de nacimiento inválidas. Respeta la pausa administrativa. Si la foto continúa ausente, sus otros datos se sincronizarán y quedará el aviso visible. Los siguientes grupos se solicitan repitiendo el comando; los registros ya encolados o procesados salen de esta selección. `--from-id` permite continuar desde el ID indicado por la salida si hace falta. No ejecuta llamadas a Google dentro de la terminal ni crea otro worker.

Las fichas ya sincronizadas con aviso se revisan desde el panel y se resincronizan después de recuperar o sustituir la foto. La importación masiva normal mantiene sus criterios anteriores; no reintenta automáticamente todos los errores históricos.

## Validación

151 pruebas y 940 aserciones con SQLite en memoria y HTTP simulado. Se reprodujeron fotos guardadas solo en `public/storage`, prefijos antiguos, archivos ausentes/dañados, conservación de foto y enlace remotos, recuperación posterior sobre el mismo contacto, avisos en lotes, diagnóstico sin escrituras y recuperación selectiva con límites/pausa. La comprobación adicional de seis escenarios MariaDB validó también los avisos de foto y el total pendiente sin modificar la base de la aplicación ni ejecutar HTTP externo. El resultado de 8266 en el servidor debe comprobarse después del despliegue.
