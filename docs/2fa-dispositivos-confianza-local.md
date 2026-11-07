# 2FA y dispositivos de confianza — revisión local

Implementación del 1 de noviembre de 2026. Trabajo exclusivo en local, sin commit, push, despliegue, consultas a producción ni modificaciones de sus credenciales o configuración.

Ampliación del 7 de noviembre: [corrección de confianza por cuenta en equipos compartidos](2fa-equipos-compartidos-2026-11-07.md). Se separan las cookies de cada usuario y se conservan, cuando son válidas, las anteriores sin extender sus vencimientos. No requiere una nueva migración.

## Resultado de la revisión previa

El sistema utiliza Laravel SessionGuard, Spatie y TOTP de seis dígitos mediante `pragmarx/google2fa`. Los secretos TOTP se cifran con Laravel; los códigos de recuperación se guardan como hashes dentro de un campo cifrado. La verificación bloquea la reutilización del mismo paso TOTP y consume los códigos de recuperación una sola vez.

**El mismo correo en local y producción no vincula las cuentas.** El correo es el identificador y la etiqueta; el código depende del secreto TOTP de cada registro. Las bases independientes deben conservar secretos independientes. Copiar un secreto entre bases sí haría coincidir los códigos, aunque sus etiquetas fueran diferentes. Esta entrega no copió secretos, usuarios ni bases.

En local se verificó, sin mostrar valores sensibles:

- Entorno local, URL de aplicación y conexión MySQL de bucle local.
- Clave de aplicación presente con longitud válida; se conservó para poder seguir descifrando los datos existentes.
- Sesiones de servidor mediante archivos bajo `storage/framework/sessions`, fuera del directorio público.
- Cookies de sesión host-only, HttpOnly y SameSite=Lax. En HTTP local `Secure` no se activa; fuera de local/testing se exige Secure por defecto.
- 16 usuarios y una cuenta con 2FA configurado. La nueva migración dejó intactos todos sus valores y los conteos de las 124 tablas existentes.

**No se inspeccionó producción.** Por tanto, no se afirma haber comparado sus claves, secretos o cookies con los locales. La verificación de que ambos APP_KEY son distintos queda para el responsable del servidor cuando autorice esa revisión. No se rotó ninguna clave existente ni se reinició su 2FA.

## Funcionamiento

1. Un nuevo inicio de sesión siempre requiere correo y contraseña. «Recordar mi correo», visible en el login, guarda únicamente el email durante 30 días mediante una cookie cifrada, HttpOnly y propia del entorno. Desmarcarlo al ingresar elimina ese recuerdo. No mantiene la autenticación ni guarda contraseñas; también se ignoran cookies antiguas del acceso persistente de Laravel. El login conserva los iconos de correo y mostrar/ocultar contraseña, con ambos campos a 15 px.
2. Si el navegador no presenta un dispositivo válido, se solicita el código TOTP o uno de recuperación.
3. **Después de verificarlo**, aparece «Confiar en este dispositivo durante 30 días», desmarcado inicialmente. El usuario puede indicar un nombre, por ejemplo «PC recepción Central».
4. Si acepta, el servidor emite un token aleatorio de 256 bits y guarda únicamente su hash HMAC, junto con un selector aleatorio, usuario, entorno, versión de acceso y familia del navegador/sistema. No usa la IP como prueba de identidad.
5. El navegador recibe una cookie propia de la cuenta, cifrada por Laravel, HttpOnly, SameSite=Strict y host-only. En HTTPS utiliza Secure; fuera de local/testing también lleva prefijo `__Host-`, sin Domain y con Path=/. Cambiar a otra cuenta conserva la confianza de cada una por separado.
6. En futuros ingresos se verifica primero la contraseña. El token válido permite omitir solamente el código 2FA. Se rota al utilizarlo y mantiene la **fecha original de vencimiento**, sin prórroga automática.
7. Borrar cookies, usar otro navegador, llegar al vencimiento o revocar el dispositivo vuelve a exigir el código. Una actualización normal de versión del mismo navegador no invalida por sí sola la confianza.

La confianza no mantiene una sesión iniciada indefinidamente. La cookie de sesión es distinta, termina con la sesión del navegador y conserva el tiempo de inactividad de Laravel. Un navegador que restaura sesiones puede recuperar un ingreso aún vigente; cerrar sesión explícitamente siempre exige contraseña en el siguiente ingreso.

La opción de registrar confianza solo se admite con verificación real reciente, durante cinco minutos. Un campo enviado por el cliente, una cookie alterada o un token de otro usuario o entorno no sustituyen esa verificación. Las acciones utilizan POST/DELETE con CSRF y límites de solicitudes.

Un token válido de confianza sigue siendo una credencial: debe utilizarse en equipos de confianza bajo control de sus usuarios. En una PC compartida, cada persona usa su propia cuenta y cierra sesión al terminar su turno. La protección no convierte un navegador comprometido en un equipo seguro.

## Mi perfil → Seguridad · Dispositivos y sesiones

- Lista solo los dispositivos del titular: nombre, fecha de alta, última actividad, vencimiento y estado activo/vencido/revocado.
- La última actividad se actualiza como máximo una vez cada cinco minutos durante una sesión iniciada mediante confianza.
- Puede revocar un dispositivo individual. Las sesiones que dependían de esa confianza la vuelven a comprobar en cada petición y solicitan nuevamente 2FA cuando se revoca o vence.
- Puede revocar toda la confianza, confirmando su contraseña actual.
- Puede cerrar las demás sesiones, confirmando su contraseña actual. Se utiliza la versión de acceso ya existente, compatible con sesiones en archivos: los otros ingresos quedan invalidados en su siguiente petición. Esta acción también revoca todos los dispositivos, conservando el ingreso desde el que se ejecuta.
- Revocar confianza no borra filas: conserva el historial con su fecha de revocación.

Los cambios y restablecimientos de contraseña, bloqueos/suspensiones, cambios administrativos de acceso, desactivación y restablecimiento de 2FA revocan los dispositivos. No se muestra ni almacena el token original en auditorías. Las operaciones quedan registradas en la auditoría de accesos existente.

## Separación de entornos

Los nombres de cookies de sesión y confianza incluyen el entorno y un identificador de la URL canónica configurada. Las cookies no tienen Domain compartido. Los tokens están vinculados al entorno, URL, clave de aplicación, usuario y versión de acceso.

Las nuevas configuraciones TOTP se identifican como **IFE LOCAL** o **IFE PRODUCCIÓN**. Ese nombre es una etiqueta; no modifica la generación del código. Para la entrada ya configurada en su teléfono puede editar manualmente el nombre a «IFE LOCAL» sin reiniciar ni volver a copiar el secreto. Esta entrega conservó el autenticador que ya funcionaba.

Para una futura publicación, después de autorización:

- Conservar las credenciales y el APP_KEY propios de producción. Deben ser independientes de los locales. **No ejecutar `key:generate` indiscriminadamente en un sistema con datos cifrados**.
- No copiar `.env`, sesiones, bases de datos, secretos TOTP, códigos de recuperación, filas de dispositivos ni cachés de configuración del equipo local.
- Utilizar `APP_ENV=production`, la URL canónica `https://ife.bo` y HTTPS correctamente configurado. El directorio público del alojamiento debe apuntar a `public`; `storage/framework` y `.env` no deben ser accesibles por HTTP.
- Si se usa un almacenamiento de sesiones compartido, asignarle conexión/prefijo independientes. La configuración comprobada en este equipo utiliza archivos locales.
- Generar las cachés de configuración en el entorno correcto del servidor. El cambio de nombres de cookies requerirá un nuevo ingreso de los usuarios cuando se publique; no cambia sus contraseñas ni secretos.
- Crear una copia de seguridad y revisar las migraciones pendientes. La migración de accesos del 31/10/2026, si aún no está aplicada allí, es una dependencia separada; su limpieza de contraseñas expuestas en notificaciones está documentada en la entrega anterior. No se ejecutó en producción en esta entrega.

Estas son instrucciones para revisión futura; no se ejecutaron acciones en el servidor.

## Migración nueva

[`2026_11_01_000001_create_user_trusted_devices.php`](../database/migrations/2026_11_01_000001_create_user_trusted_devices.php) crea únicamente `user_trusted_devices`, inicialmente vacía. No actualiza, elimina ni sobrescribe usuarios, personas, clientes, inscripciones, pagos o secretos TOTP. Repetir `up()` con la tabla existente no elimina sus datos.

**Ya se aplicó exclusivamente en MySQL local.** Se compararon los conteos de las 124 tablas previas y una huella de todos los valores de los usuarios antes/después: permanecieron intactos. El rollback elimina la tabla nueva y, con ella, la confianza registrada; no ejecutarlo durante las pruebas de uso.

## Cómo probar personalmente

| Caso | Pasos | Resultado esperado |
| --- | --- | --- |
| Dispositivo nuevo | Cerrar sesión; abrir una ventana privada; ingresar correo y contraseña | Pide el código del autenticador. |
| Guardar confianza | Verificar un código nuevo, marcar la opción de 30 días y continuar | Aparece en Mi perfil → Seguridad. |
| Volver desde el mismo navegador | Cerrar sesión e ingresar otra vez con contraseña | Omite el código si la confianza sigue vigente. |
| No guardar confianza | Verificar el código, dejar la casilla desmarcada y continuar; cerrar sesión e ingresar de nuevo | Vuelve a pedir el código. |
| Otro navegador | Abrir Firefox/Edge u otro perfil separado | Solicita contraseña y código. No compartir cookies entre perfiles. |
| Borrar cookies | Eliminar los datos del sitio local y volver a ingresar | Solicita el código. El registro anterior puede seguir listado hasta que venza o se revoque. |
| Revocar uno | Desde otro ingreso, revocar el dispositivo de prueba | No vuelve a omitir 2FA. Una sesión que dependía de ese token también pierde esa confianza en su siguiente petición. |
| Revocar todos | Confirmar contraseña en el panel de dispositivos | Ningún dispositivo conserva confianza. |
| Cerrar otras sesiones | Mantener dos ingresos distintos y ejecutar la acción desde uno | El otro sale del sistema en su siguiente petición; el actual permanece abierto. |
| Cambio de contraseña | Cambiarla desde Mi perfil y volver a ingresar | La nueva contraseña funciona y la confianza anterior está revocada. |
| Caducidad | Revisar la fecha de vencimiento; el caso de más de 30 días se simula en las pruebas automáticas | No se modifica el reloj real del equipo ni se esperan 30 días. |

## Validación y archivos

```powershell
php vendor/phpunit/phpunit/phpunit -c phpunit.user-access.xml
php vendor/phpunit/phpunit/phpunit -c phpunit.google-contacts.xml
```

Las pruebas fuerzan SQLite en memoria y simulan colas/HTTP externos. Las referencias a `https://ife.bo` dentro de pruebas son objetos Request sintéticos: **no realizan conexiones a ese dominio**.

La validación inicial completó **58 pruebas de accesos y dispositivos, con 431 aserciones**, y **220 pruebas de los módulos existentes, con 1628 aserciones**. Tras ampliar el login con «Recordar mi correo», la suite de accesos volvió a pasar con **59 pruebas y 451 aserciones**, incluida la persistencia y el borrado del correo sin autenticar ni guardar la contraseña. Ejecutar las dos suites una después de la otra: algunas pruebas comparten el directorio temporal de fotografías simuladas y no están preparadas para ejecutarse simultáneamente.

Se verificaron dispositivos nuevos, consentimiento explícito, cookies protegidas, contraseña obligatoria, tokens alterados, usuario/entorno incorrectos, rotación y repetición de tokens, vencimiento, revocación, CSRF, cierre de otras sesiones, cambios de IP y actualización del navegador. Las vistas reales de acceso, consentimiento y dispositivos se comprobaron con datos sintéticos a 320, 390, 768 y 1440 px.

Archivos principales de esta ampliación:

- [TrustedDevices.php](../app/Services/TrustedDevices.php), [UserTrustedDevice.php](../app/Models/UserTrustedDevice.php) y la migración nueva.
- [AccountSecurityController.php](../app/Http/Controllers/AccountSecurityController.php), [RecordUserLogin.php](../app/Listeners/RecordUserLogin.php), [EnforceUserAccess.php](../app/Http/Middleware/EnforceUserAccess.php), [EncryptCookies.php](../app/Http/Middleware/EncryptCookies.php), [TrimStrings.php](../app/Http/Middleware/TrimStrings.php) y [LoginController.php](../app/Http/Controllers/Auth/LoginController.php).
- [AccountSecurity.php](../app/Services/AccountSecurity.php), [UserSecurityObserver.php](../app/Observers/UserSecurityObserver.php), [UserTwoFactor.php](../app/Services/UserTwoFactor.php), [AppServiceProvider.php](../app/Providers/AppServiceProvider.php), [session.php](../config/session.php) y [web.php](../routes/web.php).
- Vistas [trust](../resources/views/user/security/trust.blade.php), [devices](../resources/views/user/security/devices.blade.php), [two-factor](../resources/views/user/security/two-factor.blade.php), [recovery](../resources/views/user/security/recovery.blade.php), [perfil](../resources/views/user/show.blade.php) y [login](../resources/views/auth/login.blade.php).
- [TrustedDevicesTest.php](../tests/Feature/TrustedDevicesTest.php) y [phpunit.user-access.xml](../phpunit.user-access.xml).

Criterios consultados: [MFA de OWASP](https://cheatsheetseries.owasp.org/cheatsheets/Multifactor_Authentication_Cheat_Sheet.html), [atributos de cookies de MDN](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Set-Cookie) y [SessionGuard de Laravel 8](https://api.laravel.com/docs/8.x/Illuminate/Auth/SessionGuard.html).
