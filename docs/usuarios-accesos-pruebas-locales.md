# Administración de usuarios y accesos — revisión local

Entrega del 31 de octubre de 2026. Los cambios se implementaron en `C:\xampp\htdocs\sistemaite`. No se hicieron commits, push, despliegues, conexiones al servidor ni migraciones en producción.

Ampliación del 1/11/2026: [2FA y dispositivos de confianza](2fa-dispositivos-confianza-local.md), con su migración, comprobaciones y pruebas de uso.

## Empezar a probar

1. Abrir **http://127.0.0.1:8000/login** e ingresar con una cuenta administradora local existente. Sus credenciales se conservaron.
2. Si se solicita segundo factor, abrir la configuración, confirmar la contraseña actual y agregar la clave a una aplicación autenticadora **TOTP**, basada en tiempo. Confirmar los seis dígitos. Guardar los ocho códigos de recuperación antes de continuar: se muestran una sola vez. **Configurar 2FA no cambia la contraseña de ingreso**: en el siguiente inicio se usa el correo y la contraseña del sistema IFE; el código del autenticador se pide después.
3. Entrar en **Usuarios** desde el menú, o abrir **http://127.0.0.1:8000/users**.
4. Utilizar cuentas de prueba. Los cambios que se hagan desde esta dirección se guardan en la base **local**.

MySQL local y Laravel se dejaron iniciados para la revisión. Si se reinicia Windows, iniciar MySQL desde XAMPP y ejecutar dentro del proyecto:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

No hace falta iniciar Apache para usar ese puerto. No se iniciaron workers de Google, no se sincronizaron contactos ni se enviaron mensajes durante estas verificaciones.

## Comportamiento implementado

| Área | Resultado |
| --- | --- |
| Alta | Nombre, correo, roles existentes y Sucursal principal para el personal. Fotografía y vínculo con Persona existente opcionales según el rol. |
| Persona vinculada | Búsqueda por nombre, apellido o código; se impide crear una segunda cuenta para una Persona que ya tiene usuario. Docente, Padre y Estudiante requieren vínculos reales compatibles. |
| Credenciales | Contraseña temporal aleatoria, válida durante 24 horas. Pantalla privada de entrega e impresión, sin guardarla en auditorías o notificaciones. |
| Primer ingreso | Cambio obligatorio de contraseña temporal antes de operar. Las contraseñas personales no tienen renovación periódica obligatoria. |
| Cambio voluntario | Desde **Mi perfil → Cambiar contraseña**, verificando la contraseña actual. Mínimo de 12 caracteres, mayúsculas, minúsculas, números y símbolos. |
| Restablecimiento administrativo | Desde los controles de acceso del usuario, con motivo obligatorio. Genera una temporal nueva y revoca sesiones y tokens anteriores. No pide ni muestra la contraseña anterior. |
| Segundo factor | TOTP y ocho códigos de recuperación de un uso. Secretos cifrados y códigos de recuperación almacenados como hashes, dentro de un campo cifrado. Rechazo de códigos TOTP ya utilizados. |
| Obligación de segundo factor | Admin, Secretaria y permisos sensibles definidos en `config/user_access.php`, incluidos administración de usuarios, pagos y configuración de Google Contacts. Los demás pueden activarlo voluntariamente. |
| Sucursales | Una principal y varias adicionales. Se reutilizan IFE Central, IFE Villa e IFE Plan de `crm_sedes`; no se creó un catálogo paralelo. La interfaz utiliza **Sucursal** y los campos internos conservan `sede`. |
| Sucursal activa | Selector en la barra superior. Admin dispone de **Todas · Admin**; los demás usuarios solo pueden seleccionar asignaciones vigentes. |
| Restricciones | Se verifican desde Laravel en cada petición, en consultas, escrituras, relaciones, reportes, fotografías y API internas. Las rutas antiguas de fotos de personas en `storage` también pasan por autenticación, incluso si el archivo existe. Los permisos existentes siguen aplicándose. |
| Horarios | Días, varias franjas, turnos que pasan de medianoche y excepciones temporales. Zona `America/La_Paz`; las denegaciones tienen prioridad, incluso sin horario semanal. |
| Estados | Activo, suspendido y bloqueado. La suspensión con fecha termina al vencer; sin fecha exige reactivación administrativa. El bloqueo conserva la cuenta y su historia. |
| Baja laboral | Bloqueo inmediato y revocación de sesiones/tokens. Reasignación de prospectos activos desde los controles del usuario, con historial del actor y del cambio. |
| Auditoría | Alta, roles, asignaciones, horarios, estados, contraseñas, segundo factor, accesos y reasignación. Registros anteriores no se sobrescriben mediante los modelos de la aplicación. |
| Último administrador | Se impide eliminar o renombrar el rol Admin, bloquear o quitar el último administrador activo y dejarlo sujeto a una restricción horaria sin otro administrador disponible. |

Las acciones administrativas de acceso requieren **Admin**. Tener un permiso de edición de usuarios, por sí solo, no permite cambiar roles, sucursales, contraseñas o estados de otras cuentas.

El botón anterior de credenciales de la lista de estudiantes se conserva solo para Admin. Ahora pide confirmar el restablecimiento, utiliza POST con CSRF, genera temporales mediante el mismo servicio de seguridad y revoca las sesiones de ambas cuentas. Prepara el enlace de WhatsApp; el envío lo decide el administrador en esa aplicación. No permite usar ese atajo para cuentas del personal o cuentas con acceso administrativo. Editar los permisos de un rol invalida la caché de permisos y las sesiones de sus usuarios, dejando auditoría.

El restablecimiento de un segundo factor perdido requiere otro administrador autenticado, su contraseña y un motivo. Para recuperar la propia cuenta se utilizan los códigos de recuperación. No se incluyó una ruta pública para saltar el segundo factor.

## Decisiones sobre datos históricos

- **Admin tiene acceso general**; al escoger una Sucursal concreta puede trabajar con ese filtro. Para registrar una operación nueva debe escoger Sucursal. El historial de un prospecto ya existente puede heredar su Sucursal al reasignarlo desde **Todas · Admin**.
- El personal autorizado puede consultar registros antiguos **sin Sucursal**, respetando sus permisos. No se les asignó una ubicación automáticamente.
- Las cuentas antiguas del personal sin asignaciones deben configurarse desde **Usuarios → Editar / sucursales** antes de registrar operaciones nuevas.
- Las operaciones nuevas se atribuyen en `sede_registros`; las pertenencias de Personas se mantienen en `persona_sede`. No se añadieron sucursales arbitrarias a inscripciones, pagos, clases o matrículas antiguas.
- Trasladar un usuario cambia sus autorizaciones actuales, no la Sucursal ni el responsable histórico de las operaciones ya registradas.
- Padres y estudiantes consultan únicamente sus vínculos reales: titular, estudiantes vinculados e inscripciones correspondientes. No se les aplican horarios laborales ni asignaciones del personal por defecto. El portal conserva los docentes y aulas de sus clases.
- Se retiró el campo de contraseña en texto plano de notificaciones antiguas de credenciales, conservando el resto de la notificación. Si esa contraseña seguía vigente, se marcó cambio obligatorio y se invalidaron las sesiones, sin cambiar su hash.

La base local conservó **16 usuarios** y las tres sucursales existentes. Durante la migración se compararon los campos originales de identidad, contraseñas y fotografía de los usuarios y los conteos de personas, inscripciones, pagos, clases y matrículas: permanecieron iguales.

## Recorrido de prueba personal

| Prueba | Pasos y resultado esperado |
| --- | --- |
| Crear personal | Crear una cuenta usando una Persona docente o trabajadora existente, escoger rol, principal y adicional. Debe aparecer una temporal y no otra ficha personal. |
| Duplicados | Buscar una Persona con cuenta existente. Debe indicarlo; una solicitud manipulada tampoco puede duplicarla. |
| Primer ingreso | Usar una ventana privada con la cuenta recién creada. Debe pedir cambiar la temporal; si el rol es sensible, configurar segundo factor antes de operar. |
| Perfil | Cambiar contraseña con una actual incorrecta y luego con la correcta. Solo la segunda debe funcionar. El avatar superior debe seguir abriendo el menú de AdminLTE. |
| Sucursales | Con personal de Central y Villa, cambiar entre ambas. Quitar Villa desde Admin; la sesión anterior debe quedar revocada y no permitir volver a Villa. |
| Registros nuevos | Crear una operación en Central, cambiar a Villa y comprobar que no aparece allí; un Admin en Todas puede consultarla. Los antiguos sin Sucursal siguen accesibles según permisos. |
| Horario | Configurar dos franjas y una excepción. Probar dentro/fuera de horario y con una sesión ya abierta. Una denegación vigente debe impedir el acceso. |
| Suspender | Suspender con fecha de fin y comprobar el rechazo. Al vencer se vuelve a permitir el ingreso; también se puede reactivar desde Admin. |
| Bloquear | Abrir otra sesión con el usuario, bloquearlo desde Admin y volver a hacer una petición en esa sesión. Debe salir del sistema; la cuenta y su historial siguen existiendo. |
| Restablecer | Generar una nueva temporal, probar que la anterior no sirve y que se exige el cambio al ingresar. Si el titular tenía segundo factor, se sigue exigiendo. |
| Recuperación 2FA | En el siguiente inicio de sesión, usar un código de recuperación. Ese mismo código no debe aceptarse otra vez. Para reutilizar TOTP debe esperarse un código nuevo. |
| Reasignación CRM | Abrir accesos de un trabajador con prospectos activos. Reasignar a personal con CRM y acceso a esas sucursales. Deben conservarse creador, responsable inicial y autores anteriores. |
| Padre / estudiante | Ingresar con cuentas vinculadas. Consultar su portal; no deben poder abrir datos de otro estudiante o la administración de usuarios. |
| Protección Admin | Intentar bloquear al único Admin o restringir su horario sin otro Admin disponible. Debe rechazarse con explicación. |

Para probar sucursales con datos antiguos, recordar que **los registros sin Sucursal permanecen visibles por la decisión acordada**. La prueba de aislamiento debe incluir registros nuevos, que sí tienen atribución.

## Migración y dependencias

Migración nueva: [`2026_10_31_000001_add_user_access_controls.php`](../database/migrations/2026_10_31_000001_add_user_access_controls.php). **Ya aplicada solo a MySQL local**. Añade campos de seguridad a `users`, las tablas `sede_user`, `user_access_windows`, `user_access_exceptions`, `user_access_audits`, `sede_registros` y `persona_sede`, y realiza la limpieza de secretos históricos descrita arriba.

El rollback retira la estructura nueva; no recupera las contraseñas retiradas de las notificaciones. No ejecutar rollback para las pruebas de uso.

Dependencia añadida: `pragmarx/google2fa` 8.0.0, con `paragonie/constant_time_encoding` 2.8.2. Composer conservó las demás versiones bloqueadas. Los avisos de seguridad de dependencias previas informados por Composer requieren una revisión aparte; esta entrega no actualiza Laravel ni toda la pila de dependencias.

## Verificación automatizada

```powershell
php vendor/phpunit/phpunit/phpunit -c phpunit.user-access.xml
php vendor/phpunit/phpunit/phpunit -c phpunit.google-contacts.xml
```

Ambas configuraciones fuerzan SQLite en memoria y entorno `testing`. Los clientes HTTP externos y las colas se simulan; estas pruebas no utilizan MySQL ni Google.

- Accesos de usuarios: incluye las pruebas de usuarios, migraciones y dispositivos de confianza de `phpunit.user-access.xml`. Los resultados de la ampliación están en la guía de dispositivos. Se verifica configurar 2FA sin cambiar la contraseña, cambiarla desde el perfil, cerrar sesión y volver a entrar con la nueva contraseña y el segundo factor.
- Regresión existente: **220 pruebas y 1.628 aserciones** de contactos, fotografías, Personas, verificación de teléfonos y CRM.
- Navegador: vistas reales de alta, controles, lista, perfil y seguridad con datos sintéticos; búsqueda, filas de horario, menú y anchos de 320, 390 y 768 px, además de escritorio de 1440 px. La respuesta de búsqueda se simuló en esa revisión visual; los permisos y el inicio de sesión se verificaron desde Laravel.
- HTTP real en el servidor local: una fotografía física temporal devolvió **401** sin sesión, tanto por `/foto/estudiantes/…` como por `/storage/estudiantes/…`; el archivo de prueba se retiró al terminar. La regla equivalente para Apache está preparada en `public/.htaccess`; no se ha aplicado ni probado en producción.
- Comprobación de sintaxis PHP, compilación de Blade y revisión del diff.

El inventario detallado está en [`usuarios-accesos-archivos.md`](usuarios-accesos-archivos.md).

**Estado de entrega:** preparado para revisión y pruebas personales en local. Cualquier commit, push, migración o despliegue en producción queda pendiente de autorización expresa posterior.
