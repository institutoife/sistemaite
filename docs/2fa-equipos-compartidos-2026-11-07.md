# 2FA: confianza independiente para turnos en una misma PC

## Causa confirmada en la implementación

El usuario informó que cada turno ingresa con su propia cuenta en el mismo navegador. El nombre anterior de la cookie de confianza dependía únicamente del entorno y URL de la aplicación. Por eso las cuentas compartían una sola cookie: el inicio de otra cuenta podía borrarla al no reconocerla como propia y, al registrar confianza, reemplazaba el token anterior.

Se reprodujeron ambos fallos con solicitudes HTTP reales de Laravel y un navegador simulado que conserva las cookies cifradas entre ingresos: alternar dos cuentas con confianza y entrar con una segunda cuenta que no tiene confianza. Las mismas pruebas fallan con el servicio anterior y pasan con la corrección. No se inspeccionaron cookies, credenciales ni registros de producción.

## Corrección local

- La confianza se guarda en una cookie distinta para cada cuenta, navegador y entorno. El nombre usa un identificador HMAC; no contiene el correo ni el código del usuario en texto plano.
- Cada cuenta verifica su propio segundo factor antes de aceptar la confianza. Una cuenta no puede aprovechar el token de otra, aunque lo coloque bajo el nombre de cookie correspondiente.
- Cerrar sesión, iniciar otra cuenta o rechazar la confianza de esa cuenta no borra la confianza de los demás usuarios.
- Revocar un dispositivo o toda la confianza, cambiar la contraseña o cerrar las demás sesiones sigue afectando únicamente al titular. Las sesiones dependientes de una confianza revocada o vencida vuelven a exigir 2FA.
- Se conserva la contraseña obligatoria en cada nuevo inicio, tokens aleatorios guardados mediante hash, cookies cifradas HttpOnly/host-only/SameSite=Strict, Secure y prefijo `__Host-` en producción, rotación al utilizar el token y vencimiento fijo de 30 días. Un ingreso no prorroga el plazo.
- Se conserva el aislamiento de local y producción. No se cambiaron claves de aplicación, contraseñas, secretos TOTP, configuración del servidor ni filas reales de usuarios/dispositivos.

## Compatibilidad con la cookie anterior

Al ingresar su titular con contraseña, una cookie antigua válida se verifica y migra al nuevo nombre de esa cuenta, rotando su token y conservando el vencimiento original y el mismo registro. Otra cuenta no puede consumirla, borrarla ni usarla para saltarse su propio 2FA.

Una cookie ya borrada o reemplazada no puede recuperarse desde el hash de la base: ese usuario deberá verificar 2FA una vez y volver a marcar la opción de 30 días. Los tokens antiguos revocados, vencidos, alterados o ya utilizados no se reactivan. Si llega una cookie nueva inválida, no se intenta recuperar confianza por la cookie anterior.

## Cómo probar en local

1. En el mismo navegador, ingresar con una cuenta A, verificar su 2FA y marcar «Confiar en este dispositivo durante 30 días»; pulsar Continuar y cerrar sesión.
2. Ingresar con una cuenta B, verificar su propio 2FA y registrar confianza; cerrar sesión.
3. Volver a A con su contraseña: debe entrar sin repetir el código. Cerrar sesión y repetir con B: también debe conservar su confianza.
4. Desde Mi perfil → Seguridad de A, revocar su dispositivo. A deberá verificar 2FA en un ingreso posterior; B conservará su confianza.
5. Repetir con B sin aceptar confianza: B seguirá necesitando 2FA en futuros ingresos y A conservará la suya.

Las pruebas automáticas simulan el paso de varios días y el vencimiento sin modificar el reloj real de Windows. No es necesario esperar al siguiente turno para comprobar el cambio de cuenta.

## Archivos de esta corrección

- `app/Services/TrustedDevices.php`: nombre por cuenta, compatibilidad con cookies antiguas y eliminación limitada al titular.
- `app/Http/Controllers/AccountSecurityController.php` y `app/Http/Middleware/EnforceUserAccess.php`: pasan explícitamente el usuario cuya confianza debe eliminarse.
- `resources/views/user/security/trust.blade.php` y `devices.blade.php`: explican el comportamiento por cuenta y el cierre de sesión al finalizar el turno.
- `tests/Feature/TrustedDevicesTest.php`: navegador simulado con cookies reales de Laravel y pruebas de alternancia, migración, vencimiento, revocación, contraseña y manipulación entre cuentas.
- Esta guía y actualización de `2fa-dispositivos-confianza-local.md`.

No se requieren nuevas tablas, migraciones, paquetes ni tareas programadas. La corrección está únicamente en local; no se realizaron commit, push, despliegues ni operaciones en producción. Los cambios anteriores de asistencia y los archivos ajenos a esta tarea se conservaron.

## Verificación

```powershell
C:\xampp\php\php.exe vendor/phpunit/phpunit/phpunit -c phpunit.user-access.xml
C:\xampp\php\php.exe vendor/phpunit/phpunit/phpunit -c phpunit.attendance.xml
C:\xampp\php\php.exe vendor/phpunit/phpunit/phpunit -c phpunit.google-contacts.xml
```

Ejecutar las suites secuencialmente. Todas usan SQLite en memoria y solicitudes externas simuladas; no acceden a la base local real ni al servidor. Las referencias a `ife.bo` en pruebas son objetos Request sintéticos para comprobar las restricciones de HTTPS y de entorno.

Verificación completada: **375 pruebas y 2.890 aserciones**, sin errores: 72 pruebas de usuarios/2FA (676 aserciones, incluidas 32 de dispositivos), 83 de asistencia (586) y 220 de CRM/Google Contacts (1.628). Se añadieron 13 pruebas de regresión para el navegador compartido y la compatibilidad. La comprobación PHP de los archivos modificados y `git diff --check` también pasó.
