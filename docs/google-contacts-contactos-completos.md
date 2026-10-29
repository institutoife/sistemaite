# Contactos completos y preparación de BanaHosting

Fecha: 26 de octubre de 2026. El usuario confirmó que el mismo sistema ya existe en ife.bo; se prepara una actualización, todavía sin acceso ni cambios en el alojamiento.

Método de actualización elegido posteriormente: commit/push local y pull en el servidor, seguidos del script de migraciones y preparación. El ZIP queda como alternativa; la [guía principal](LEEME-ACTUALIZACION-BANAHOSTING.md) utiliza el flujo Git.

## Identidad y datos

- Se conservan nombre y apellidos estructurados. Un sufijo visible incorpora el código de Persona usado por IFE: `Lucas Mamani | IFE | 45645` es un ejemplo de formato, no un registro exportado para probar.
- Un apoderado utiliza su propio nombre y el parentesco real de `persona_persona`, por ejemplo `Juan Quispe | Papá de Lucas Mamani | 45645`. Para varios estudiantes se resumen hasta dos vínculos y la cantidad restante; todos se detallan en las notas. Si además es estudiante, conserva su código propio.
- Las notas incluyen código, vínculos y servicios realmente registrados en inscripciones académicas y matrículas de computación. Se consultan con eager loading. Los servicios no van en el nombre; tampoco se exportan saldos, costos ni observaciones administrativas privadas.
- El bloque delimitado de IFE se reemplaza al actualizar, conservando las notas ajenas al bloque que ya existían en Google. Se mantiene su formato de texto/HTML y se escapan los datos incorporados a HTML.
- Las fechas válidas de la Persona se envían como `birthdays.date`, incluyendo estudiantes y apoderados. Una fecha ausente no borra la remota; una fecha inválida queda para revisión. Los correos ficticios generados para iniciar sesión no se exportan.

Los formularios existentes de alta y edición de apoderados ahora permiten cumpleaños y fotografía propios, opcionales. No toman los valores del estudiante. Omitir estos campos en consumidores anteriores conserva los datos existentes.

## Fotografías y reintentos

Se usa exclusivamente el archivo local indicado por `Persona.foto`. Se excluyen imágenes predeterminadas, URLs externas y rutas fuera de los directorios públicos de imágenes permitidos. Nunca se busca una fotografía en los hijos ni se usa el sustituto de vCard. La corrección del 29 de octubre admite también las carpetas públicas históricas del alojamiento; un archivo inexistente o inválido deja un aviso **Sincronizado · revisar foto**, permitiendo sincronizar los demás datos y conservando la foto remota. Ver [diagnóstico y recuperación de fotografías](google-contacts-fotografias.md).

Para exportar, se comprueba formato JPG/PNG/WebP, hasta 5 MB y 20 megapíxeles; se genera un JPEG de hasta 720 píxeles por lado con GD. La imagen subida desde el formulario del apoderado se limita además a 4000 × 4000 píxeles. No se incorporan los metadatos EXIF de la imagen original.

La huella de la imagen exportada evita volver a subirla si no cambió. El enlace permanente del contacto se guarda antes de actualizar su foto. Si falla esa operación, el Job mantiene el enlace y reintenta el mismo recurso, sin otro CREATE. La respuesta de fotografía actualiza el etag de la fuente CONTACT. Una imagen ausente no solicita borrar la foto remota.

La reconciliación paginada descarga solo campos de identidad; lee las notas completas del contacto encontrado antes de actualizarlo. Así no se descargan las notas de toda la cuenta central en cada búsqueda.

## Cambios relacionados

El nombre de un estudiante marca también sus apoderados. Las altas, reasignaciones, cambios de servicio y eliminaciones de inscripciones/matrículas marcan los contactos afectados. Renombrar una modalidad o asignatura recupera sus personas por bloques. Las llamadas a Google siguen exclusivamente en la cola, después del commit.

Las rutas existentes de vínculos familiares siguen marcando al apoderado tras adjuntar, editar o desvincular. Desvincularlo actualiza su contexto, sin eliminar el contacto Google. Los metadatos se escriben sin disparar de nuevo estos observers.

## Esquema y despliegue

La quinta migración `2026_10_26_000002_add_google_contact_photo_hash.php` agrega una única columna nullable a Persona. No duplica fotos, fechas, parentescos ni tablas de contactos. Se aplicó en MariaDB local y se reinició la cola administrada. No se inició una exportación masiva.

`php artisan gcontact:check` comprueba extensiones, permisos, cifrado, OAuth configurado, esquema, cola y cuenta central sin modificar datos ni llamar a Google. `--production` exige configuración de ife.bo, HTTPS, Cron, caché persistente y MySQL/MariaDB. `--allow-disconnected` sirve únicamente para preparar la primera conexión; no sustituye la comprobación final.

El script `scripts/hosting/deploy-google-contacts.sh` aplica solo las migraciones de esta integración y su seeder, conserva el entorno del servidor y reconstruye autoload/configuración. No registra el Cron por su cuenta. El paquete usa una lista explícita, un manifiesto de huellas SHA256 y excluye secretos, base de datos, archivos privados y datos de prueba del entorno local.

Guía para la actualización: [LEEME BanaHosting](LEEME-ACTUALIZACION-BANAHOSTING.md). Las pruebas aisladas de esta entrega se ejecutan con `php vendor/phpunit/phpunit/phpunit -c phpunit.google-contacts.xml`; el resultado final acompaña la entrega y la guía de despliegue.

Resultado final: **118 pruebas y 617 aserciones correctas**. Se validaron sintaxis PHP y del script POSIX, dependencias locales con Composer y huellas de cada archivo del ZIP. Además se resincronizó por la cola real el registro 26 previamente autorizado, conservando el mismo recurso Google y quedando sin errores. Esa ficha tiene cumpleaños y no dispone de fotografía propia; el envío de fotos se verificó con archivos y HTTP simulados.

Durante la revisión se retiró una clave de CAPTCHA incrustada en una regla legada sin consumidores encontrados y en un comentario antiguo. La regla reutiliza ahora `recaptchav3.secret` (configurada mediante `RECAPTCHAV3_SECRET`), mantiene su umbral de puntuación y rechaza fallos de red sin exponer detalles. La validación del formulario público sigue usando el paquete de reCAPTCHA existente; conservar su configuración del servidor.

No se implementan en este bloque envíos ni preparación automática de comunicaciones de cumpleaños, faltas o cobranzas. Tampoco se declara validado el Cron real del alojamiento ni la visualización final del nombre/foto en cada teléfono; eso debe comprobarse tras desplegar con una ficha autorizada.

Referencias: [campos de Person](https://developers.google.com/people/api/rest/v1/people), [actualización de contactos](https://developers.google.com/people/api/rest/v1/people/updateContact), [actualización de fotografías](https://developers.google.com/people/api/rest/v1/people/updateContactPhoto), [Cron Jobs de cPanel](https://docs.cpanel.net/cpanel/advanced/cron-jobs/).
