# Google Contacts — Primera entrega

Fecha: 25 de octubre de 2026.

Ampliaciones del 26 de octubre: [Orígenes y teléfonos incompletos](google-contacts-origenes-y-telefonos.md) incorpora docentes, otros papeles, clientes potenciales, Prospectos y consultas web/internas. [Contactos completos](google-contacts-contactos-completos.md) incorpora nombres/códigos, notas, cumpleaños y fotos propias. El alcance y los resultados de pruebas descritos como primera entrega a continuación son históricos; para desplegar el código actual se requieren las cinco migraciones indicadas abajo.

## Implementado

- Recuperada la integración existente con Socialite y People API. Las URLs OAuth anteriores se conservan y ahora requieren autenticación y permiso.
- Cuenta central con identidad fija; reconectar otra cuenta no reemplaza silenciosamente los enlaces. Tokens cifrados con `APP_KEY`, ocultos en serialización y fuera de las sesiones del navegador.
- Renovación del access token mediante refresh token, detección de autorización revocada, desconexión local y reconexión.
- Panel AdminLTE en `/contactos/google`, con búsqueda, filtros, relaciones familiares, última sincronización, errores comprensibles y diagnóstico restringido.
- Sincronización individual de **nombres y teléfonos**. Conserva `resourseName` y `etag`, consulta metadatos actuales antes de actualizar y respeta otros campos de Google.
- Los cambios de datos marcan pendiente aunque no haya una sesión Google abierta. Los Jobs reciben IDs, nunca tokens.
- Cola dedicada `google_contacts`, siempre basada en la tabla `jobs`, aunque otros módulos sigan con `QUEUE_CONNECTION=sync`.
- Exclusión mutua por cuenta, Jobs únicos por persona, máximo de cinco intentos y preservación de cambios realizados mientras se sincroniza.
- Intención de creación persistida antes de llamar a Google; recuperación mediante identificador estable y detención ante resultados inciertos. Los contactos antiguos coincidentes sin enlace quedan para revisión; no se fusionan por teléfono.
- Comando anterior convertido a encolado por bloques. Scheduler preparado para despachar pendientes cada cinco minutos cuando la cuenta esté conectada. Los errores definitivos no se reencolan automáticamente.
- Solicitudes manuales registradas mediante `userables`; conexión y resultados registrados con IDs de actor y origen, sin secretos.
- Retirada la ruta que devolvía el token completo. Retirado el argumento `--token` del comando.

Esta entrega no implementa todavía fotos, cumpleaños como campo Google, notas de servicios ni el nombre compuesto con parentesco/código. Tampoco cambia las comunicaciones de cumpleaños, faltas o cobranzas. Son los siguientes bloques de la arquitectura acordada.

## Estado del entorno local

Las siguientes migraciones se aplicaron sobre MariaDB, conservando los registros educativos y financieros:

- `2026_10_25_000001_create_google_contact_accounts_table`
- `2026_10_25_000002_extend_personas_google_contacts`

Se ejecutó únicamente `GoogleContactsSeeder`, que agrega permisos al rol Admin sin reiniciar roles ni ejecutar el seeder general. MariaDB de XAMPP fue iniciado para aplicar estos cambios. Posteriormente se conectó la cuenta central autorizada para realizar la prueba real de sincronización.

Prueba real completada el 25 de octubre de 2026: los registros 26 y 20 quedaron sincronizados, con lectura posterior de People API que confirmó nombres, teléfonos e identificadores IFE. Una resincronización adicional del registro 26 conservó el mismo recurso Google. Posteriormente se incorporaron pausa global, contadores, señal del proceso y arranque automático del proceso al iniciar sesión mediante la tarea de Windows `IFE-GoogleContacts`: ver [Operación compartida](google-contacts-operacion-compartida.md).

## Puesta en marcha

1. Entrar al sistema con un Administrador y abrir **Google Contactos** en el menú.
2. Comprobar `GOOGLE_OAUTH_ID`, `GOOGLE_OAUTH_SECRET` y `GOOGLE_REDIRECT` en el servidor. La URL de retorno debe coincidir exactamente con la registrada en Google Cloud; incluir `/sistemaite` si la instalación utiliza ese subdirectorio.
3. En Google Cloud, habilitar People API y comprobar consentimiento OAuth, usuarios permitidos y estado de publicación. Para procesos desatendidos se requiere acceso offline. Una aplicación externa en pruebas puede tener autorizaciones de corta duración: revisar la configuración antes de usarla de forma permanente.
4. Pulsar **Conectar Google**, autorizar contactos y verificar que se utiliza la cuenta central correcta. La conexión no inicia una sincronización masiva.
5. Ejecutar un worker dedicado desde el directorio del proyecto:

```powershell
php artisan gcontact:work
```

6. Solicitar un contacto desde el panel y actualizar el listado para comprobar el resultado. Primero comprobar un contacto de prueba autorizado antes de procesar un lote real.
7. El proceso `gcontact:work` revisa los pendientes cada cinco minutos. No necesita una segunda tarea del Scheduler para esta integración. Se conserva la entrada de `schedule:run` para instalaciones con el Scheduler general configurado; los Jobs únicos evitan duplicar trabajos.

El worker debe mantenerse supervisado; en Windows local se instaló posteriormente la tarea de inicio de sesión descrita en [Operación compartida](google-contacts-operacion-compartida.md). En varios servidores, los workers necesitan un almacenamiento de locks compartido. En una instalación Windows, los timeouts de cada llamada HTTP también limitan el tiempo de trabajo, porque la terminación de procesos por señales depende del entorno.

No cambiar `APP_KEY` sin un procedimiento de recifrado: protege los tokens persistidos. No agregar access tokens ni refresh tokens a `.env`, argumentos o capturas de pantalla. La desconexión del panel detiene el acceso de IFE y conserva contactos; la revocación de permisos de la aplicación también puede hacerse desde la cuenta de Google.

## Comandos conservados

```powershell
# Encolar como máximo 100 contactos elegibles, sin llamadas API dentro del comando.
php artisan gcontact:sync-batch --batch=100

# Encolar solo pendientes; no reiniciar errores definitivos.
php artisan gcontact:sync-batch --only-pending --batch=100
```

`--from-id` sigue disponible. Un apoderado desvinculado o estudiante inactivo no provoca ninguna eliminación en Google. Los casos con `uncertain_create`, contactos coincidentes antiguos, recursos inexistentes o metadatos inconsistentes requieren revisión del enlace; reintentar no autoriza una creación a ciegas.

## Despliegue en otro entorno

Aplicar exclusivamente estas migraciones incrementales después de respaldar la base según el procedimiento del servidor:

```powershell
php artisan migrate --path=database/migrations/2026_10_25_000001_create_google_contact_accounts_table.php --path=database/migrations/2026_10_25_000002_extend_personas_google_contacts.php --path=database/migrations/2026_10_25_000003_add_google_contacts_pause_controls.php --path=database/migrations/2026_10_26_000001_extend_leads_google_contacts.php --path=database/migrations/2026_10_26_000002_add_google_contact_photo_hash.php
php artisan db:seed --class=GoogleContactsSeeder
```

Si el entorno utiliza configuración o rutas cacheadas, regenerarlas con su procedimiento de despliegue habitual y reiniciar los workers que ya estaban ejecutándose. No ejecutar `migrate:fresh`, el seeder general ni reemplazar migraciones históricas.

## Validación y límites

Ejecutar:

```powershell
php vendor/phpunit/phpunit/phpunit -c phpunit.google-contacts.xml
```

El perfil fuerza SQLite en memoria, utiliza respuestas HTTP simuladas y bloquea las solicitudes no simuladas. Las pruebas verifican OAuth, permisos, cifrado, renovación, revocación, alta/actualización, diez resincronizaciones, datos cambiados durante el Job, múltiples apoderados con teléfono compartido, números inválidos/ausentes, límites, timeouts, respuestas incompletas, contacto Google inexistente, contactos antiguos sin enlace y paso efectivo por la cola tras el commit. También se corrigió la prueba de relación de grados que fallaba antes de estos cambios.

Resultado de esta entrega: **46 pruebas y 182 aserciones correctas**. También se verificaron rollback sin bloqueos huérfanos, recuperación de los pendientes de la integración anterior, errores de red/TLS y recorrido de 22 páginas de contactos sin crear duplicados; el análisis sintáctico PHP pasó en los archivos comprobados.

Corrección posterior durante la prueba real: se distinguen fallos de red/proxy, certificados TLS y timeouts; no se registran los mensajes originales que puedan contener URLs sensibles. Los workers iniciados desde un entorno de automatización restringido pueden heredar un proxy bloqueado. Deben ejecutarse en el entorno normal autorizado del servidor, manteniendo la verificación TLS activa. También se amplió la comprobación paginada para cuentas centrales con más de 20.000 contactos, sin crear contactos antes de terminar de recorrer las páginas.

Las migraciones se comprobaron además contra MariaDB local. El renderizado de la vista AdminLTE pasó con datos ficticios. La herramienta de revisión visual se detuvo al no poder verificar la URL del navegador: no se completó la inspección visual en escritorio, tablet o móvil. La prueba real de consentimiento y API con la cuenta central autorizada quedó completada para los registros 26 y 20, incluyendo alta y actualización posterior del 26 sin cambiar su recurso. No se enviaron mensajes ni se eliminaron contactos.

Referencias oficiales: [OAuth para aplicaciones web](https://developers.google.com/identity/protocols/oauth2/web-server), [actualización de contactos y etags](https://developers.google.com/people/api/rest/v1/people/updateContact), [listado de contactos](https://developers.google.com/people/api/rest/v1/people.connections/list).
