# Confirmar conversiones desde el tablero CRM

Corrección del 31 de octubre de 2026 para la instalación existente de ife.bo. No agrega migraciones ni modifica pagos o inscripciones.

## Motivo y comportamiento

La etapa de conversión estaba excluida del arrastre y del selector móvil. Registrar una inscripción o un pago por el circuito habitual no confirmaba por sí mismo la oportunidad del CRM: seguía activa y sin evento de conversión. Además, el filtro predeterminado del tablero ocultaba las fichas convertidas.

Ahora, arrastrar una ficha activa a una etapa de conversión, elegir esa etapa en el selector o pulsar **Convertir en cliente** abre la confirmación. La ficha permanece en su columna hasta que el servidor acepta la operación. Cancelar o recibir un error conserva su estado.

1. Seleccione el contacto existente: el estudiante o su apoderado, según corresponda a la oportunidad.
2. Elija una inscripción académica o matrícula de computación ya registrada. Se muestran las del contacto y sus estudiantes vinculados. Si falta el estudiante, búsquelo expresamente; también puede ingresar el código conocido de inscripción.
3. Revise el contacto, el estudiante, el proceso y el código. Si el contacto es un apoderado todavía no vinculado, indique el parentesco.
4. Marque la confirmación y pulse **Confirmar conversión**.

No vuelva a registrar al cliente, la inscripción ni el pago. El sistema comprueba que la inscripción pertenece al estudiante seleccionado y conserva las protecciones de identidad de Google. Compartir teléfono no vincula personas automáticamente. Solo usuarios con **Convertir Prospectos** y acceso a la ficha pueden consultar el formulario, sus opciones y confirmar. La indicación **Pago registrado** requiere además **Listar Pagos** y nunca expone importes.

La operación guarda fecha, autor y responsable comercial del momento, cancela el seguimiento pendiente, conserva el historial y utiliza la etapa de conversión elegida. Reenviar la misma confirmación no duplica el evento ni el trabajo de Google. Una ficha ya convertida no puede registrarse otra vez con una inscripción diferente.

El tablero abre por defecto **En gestión y convertidos**, con responsable asignado. La lista mantiene su predeterminado de activos. Los filtros **Solo activos en gestión**, **Convertidos** y **Bajas** siguen disponibles. Después de confirmar se conservan los filtros de catálogo y se retiran los de etapa y agenda que podrían ocultar la ficha convertida.

Los indicadores cuentan la conversión por su fecha de confirmación en el CRM. **Conversiones registradas** corresponde al usuario que la confirmó; la atribución comercial corresponde al responsable en ese momento. Revise período, usuario, sucursal y servicio si consulta un informe filtrado. Pagar por el circuito habitual sin confirmar el vínculo CRM no agrega una conversión al indicador.

## Actualización

Después del respaldo habitual, en la terminal de BanaHosting:

```sh
cd /home/vaninquz/ife.bo/sistemaite
git pull --ff-only origin main
PHP_BIN=/opt/cpanel/ea-php82/root/usr/bin/php sh scripts/hosting/deploy-crm.sh
```

Las migraciones anteriores ya aplicadas se omiten. Se conserva el Cron de Google existente. Recargue el navegador después de actualizar.

## Verificación

Suite completa: **195 pruebas y 1377 aserciones**, todas correctas. Incluye pruebas aisladas de la vinculación de una inscripción pagada, lectura sin escrituras, parentescos, permisos, separación de pagos por tipo de inscripción, historial, indicadores, doble envío, versiones y rechazo de inscripciones incorrectas. La revisión en Chrome comprueba arrastre, cancelación, selector móvil, selección de inscripción, error sin mover la ficha y conservación de filtros tras confirmar. No se usan datos ni pagos de producción.
