# Fotografías de personas, estudiantes, docentes y usuarios

Actualización del 31 de octubre de 2026 para ife.bo. Usa los campos `foto` y las relaciones existentes; no agrega migraciones ni modifica masivamente las rutas guardadas.

## Carga uniforme

Las listas, fichas de Persona y docente, perfiles de usuario, estudiantes asignados, clases, asistencia, cartera y reportes utilizan el mismo acceso a las fotografías. Se sustituyen las concatenaciones directas de `/storage/`, que no resolvían de forma uniforme los prefijos antiguos ni los nombres escapados en las respuestas de las tablas.

Se reutiliza la búsqueda del archivo exacto disponible en el disco público, `public/storage` o el directorio heredado `public/foto`, incluidos sus prefijos conocidos. Se conservan también las URLs HTTP/HTTPS de fotografías importadas: las carga el navegador y el servidor no las descarga. No se buscan archivos por nombre en otras carpetas ni se toma la foto de otro familiar. Las listas de Personas y docentes reciben también una URL calculada, conservando el valor original de `foto`. La lista de usuarios usa una consulta protegida con el permiso del módulo y carga las Personas vinculadas sin consultas por cada fila.

El avatar de una cuenta con imagen predeterminada o archivo ausente utiliza la fotografía disponible de su Persona vinculada. Las nuevas modificaciones de la fotografía de Persona actualizan también la referencia de sus cuentas vinculadas. En los reportes se diferencia la imagen del estudiante de la del usuario que registró la operación.

El acceso común a archivos locales verifica autenticación, rutas permitidas y un formato de imagen válido. Una fotografía inexistente o inválida muestra una imagen predeterminada compartida y no abre un visor vacío. Esto no recupera un archivo eliminado: debe restaurarse desde un respaldo o subirse de nuevo. No se necesita recrear el enlace `storage` para las imágenes que el sistema puede leer desde los directorios existentes.

## Ampliación y zoom

Haga clic en una fotografía disponible, incluida la del usuario conectado. El visor permite ampliar y reducir con **+ / −**, rueda del ratón y gesto de dos dedos; arrastrar para desplazarse; **Ajustar** para volver a encajar la imagen y **Cerrar** o **Escape** para regresar. También admite teclado y devuelve el foco a la fotografía al cerrar.

La carga y los eventos funcionan en filas nuevas o redibujadas por las tablas y en los detalles que se abren dinámicamente. El visor común evita que se abra a la vez la ampliación anterior de las miniaturas.

## Nuevas fotos

Las cargas de Persona, apoderado, docente y usuario se validan como imágenes JPG, PNG, GIF o WebP de hasta 5 MB y 4000 × 4000 píxeles. Se guardan como JPG con un máximo de 1600 píxeles por lado, conservando la proporción, la orientación disponible y sin agrandar originales pequeños. Los archivos anteriores se conservan porque todavía pueden estar referenciados por otra ficha o usuario.

La cámara captura la resolución disponible manteniendo la proporción y ofrece subir un archivo cuando no está disponible. Guardar sin elegir una foto nueva conserva la existente. Se puede tomar una foto para una Persona que todavía no tiene cuenta de usuario.

Las fotografías antiguas ya reducidas conservan su resolución original. El límite de 5 MB de subida a Google sigue vigente; una fotografía antigua mayor puede mostrarse en el sistema aunque no sea apta para enviar a Google.

## Actualización en BanaHosting

Después del respaldo habitual de archivos y base de datos:

```sh
cd /home/vaninquz/ife.bo/sistemaite
git pull --ff-only origin main
PHP_BIN=/opt/cpanel/ea-php82/root/usr/bin/php sh scripts/hosting/deploy-crm.sh
```

Se utiliza el despliegue existente, que comprueba dependencias y limpia las vistas. Las migraciones anteriores aplicadas se omiten. No se reemplazan las carpetas de fotografías del servidor por las de la copia local. Recargue el navegador con **Ctrl + F5**.

Compruebe la Persona **#5300** en la lista y en su ficha: ambas deben mostrar el mismo archivo. Pruebe una foto de docente, el avatar del usuario conectado, una foto desde una tabla filtrada y el zoom en móvil. Si un archivo sigue ausente, el diagnóstico de solo lectura `ea-php82 artisan gcontact:photo-check 5300` comprueba su disponibilidad para Google sin modificar datos ni hacer solicitudes externas.

## Verificación

Suite completa: **217 pruebas y 1584 aserciones**, todas correctas, y compilación de todas las vistas Blade. En el servidor se confirmó mediante el diagnóstico de solo lectura que la fotografía #5300 está disponible en `public/storage`; la actualización utiliza ese directorio sin pedir que se suba de nuevo.

Las pruebas aisladas cubren rutas antiguas, directorios físicos sin enlace `storage`, el caso de una Persona #5300 sintética, URLs de listas, autenticación y permisos, archivos inválidos, avatar de Persona, conservación de archivos y referencias, cámara sin cuenta de usuario, imágenes proporcionadas y separación de autores en reportes. La revisión en Chrome usa fotografías sintéticas y comprueba las tablas dinámicas, nombres escapados, zoom, arrastre, teclado, foco, gesto de dos dedos, recuperación de imágenes y cámara simulada. No accede a producción ni activa una cámara real.
