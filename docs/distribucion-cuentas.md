# Distribución de ChatGPT y Claude

Cada servicio tiene una sola cuenta seleccionada. El administrador configura la capacidad al crear o editar cada cuenta. Las cuentas existentes conservan su capacidad y sus accesos; la migración no elige una cuenta automáticamente.

## Operación

1. Abrir una cuenta con credenciales completas, activa y pagada o en gracia.
2. Usar **Seleccionar para distribución** para cambiar la cuenta actual conservando todos los accesos y cupos ocupados. También permite volver a una cuenta que había quedado en reserva.
3. Cuando la ocupación alcanza o supera la capacidad, la cuenta pasa automáticamente a **Vendida**, con una etiqueta verde. El estado se actualiza al registrar la última entrega, sin recargar, y se conserva al seleccionar otra cuenta o cambiar su plan. Los vendedores no pueden superar el límite; el administrador puede registrar ventas adicionales solamente en la cuenta seleccionada y habilitada. Una venta de dos dispositivos ChatGPT consume dos cupos. Si un ajuste libera cupos, aumenta la capacidad o se confirma un nuevo ciclo, el estado se recalcula según la nueva ocupación. Los avisos de plan Gratis, inactividad y vigencia se muestran por separado y conservan sus bloqueos.
4. Para retirar accesos anteriores y reutilizar una cuenta, cerrar sus sesiones fuera del sistema, desplegar **Iniciar nuevo ciclo** y confirmar el cierre en todos los dispositivos. El ciclo reinicia la ocupación, selecciona la cuenta y conserva los historiales de ventas, pagos y reinicios.

Registrar un pago o cambiar la selección no reinicia la ocupación. Un formulario repetido de ciclo devuelve el resultado original sin liberar ventas nuevas.

Cada formulario de ciclo corresponde al ciclo que estaba abierto al cargar la cuenta. Si otro administrador o pestaña inicia un ciclo, los formularios anteriores se rechazan y requieren recargar. Activar y desactivar envían el estado solicitado; repetir una desactivación mantiene la cuenta desactivada.

Las suscripciones antiguas vinculadas a ChatGPT o Claude se administran desde el módulo compartido. Las rutas generales de edición, fechas, renovación, contacto y eliminación redirigen a su cuenta; sus modelos también bloquean estas escrituras para impedir que se reactiven cupos fuera de las reglas de distribución.

## Entregas al cliente

En el menú de cada servicio, **Cuenta en distribución** muestra la cuenta seleccionada, su plan y los cupos ocupados y libres. Reemplaza la pantalla de edición de textos y también está disponible para los operadores.

**Copiar entrega** registra la venta y copia el mensaje para el cliente. ChatGPT ofrece botones de uno o dos dispositivos, que ocupan uno o dos cupos respectivamente; Claude ofrece un dispositivo, que ocupa un cupo. Se usan los textos existentes y un mensaje predeterminado cuando están vacíos.

La última entrega registrada queda disponible para **Volver a copiar**, sin registrar otra venta. Recargar la página o repetir una solicitud conserva la ocupación. Si el navegador impide copiar, el mensaje queda disponible para copiarlo manualmente y los cupos ya registrados no se duplican.

El historial conserva todas las ventas. Solo prepara credenciales para entregas vigentes que no hayan sido liberadas y cuya cuenta esté activa y pagada o en gracia. Una venta vencida o de un ciclo anterior muestra el motivo y su botón queda deshabilitado, incluso al abrir su enlace directo. Una entrega vigente puede volver a copiarse aunque su cuenta haya quedado en reserva.

Las rutas antiguas `/chatgpt/plantillas` y `/claude/plantillas` redirigen a la cuenta en distribución. La edición de textos ya no tiene una pantalla ni una ruta de guardado.

## Plan y vigencia

ChatGPT conserva un mes pagado y un mes de gracia; Claude conserva un mes pagado. Al terminar la vigencia, el estado calculado pasa a **Gratis** y bloquea las ventas para todos los roles. El administrador también puede marcar Gratis antes del vencimiento si la cuenta pierde el plan.

**Restaurar plan vigente** elimina esa marca manual únicamente si las fechas siguen vigentes. Después del vencimiento se requiere un nuevo pago. Los cupos se mantienen incluso cuando la cuenta está Gratis o inactiva.

La vigencia por fechas se muestra por separado de la marca manual Gratis. Por ejemplo, una cuenta pagada el 11/09/2026, con fin pagado el 11/10/2026 y fin de gracia el 11/11/2026, está en gracia el 15/10/2026 y conserva 27 días. Si está marcada Gratis, se informa ese bloqueo específico y se permite restaurar el plan mientras las fechas sigan vigentes. El mensaje de vigencia finalizada aparece cuando terminan tanto el mes pagado como la gracia.

En ChatGPT, **Revisar vigencia existente** está disponible una sola vez para cuentas pendientes sin pagos registrados. Permite completar la fecha original del pago o del inicio de gracia de una cuenta importada. Una cuenta ya revisada o con pagos debe usar **Registrar pago** para renovar su vigencia.

## Cierres y próximos pagos de ChatGPT

En el panel y en ChatGPT, **Ver próximas fechas** abre `/chatgpt/vencimientos`. Hay dos listas independientes, ordenadas desde la fecha más cercana: fin del mes pagado y fin del mes de gracia. Al terminar el mes pagado corresponde cerrar sesiones; al terminar la gracia, cerrar sesiones y registrar el pago. Las cuentas cuya gracia ya finalizó se muestran por separado, con enlace al pago.

El administrador configura entre 0 y 60 días de anticipación para cada aviso. Ambos empiezan en tres días y la configuración se comparte entre usuarios y dispositivos. Cero significa mostrar el aviso el mismo día. Las listas conservan también las fechas posteriores al plazo de aviso para planificar; las fechas cercanas se destacan y se cuentan en los resúmenes. Las cuentas inactivas y las fechas pendientes o incompletas quedan fuera de estas listas y se informan con su cantidad.

El cierre se realiza en ChatGPT y se confirma mediante **Iniciar nuevo ciclo**. Los enlaces de los avisos abren la cuenta; consultar un aviso no registra pagos, reinicia ocupación ni cambia la cuenta en distribución. Registrar un pago actualiza las fechas siguiendo las reglas existentes.

La tabla `chatgpt_avisos_config` conserva los dos plazos. Forma parte de `run_chatgpt_schema_migrations` y del comando de instalación indicado debajo. La migración es idempotente y conserva los valores configurados.

## Instalación y verificación

La migración compartida crea `cuentas_distribucion`, `cuentas_ciclos` y la marca `plan_gratis`. Se incluye en las migraciones de ambos servicios. Para instalaciones sin migraciones automáticas:

```sh
php deploy/migrate_chatgpt.php
```

Las pruebas de integración crean bases temporales con nombres restringidos y las eliminan al terminar:

```sh
php tests/chatgpt.php
php tests/claude.php
php tests/chatgpt_existentes.php
php tests/chatgpt_textos.php
php tests/chatgpt_avisos.php
php tests/run.php
```

`tests/claude.php` incluye los casos de distribución, entregas y ajustes de ocupación de ambos servicios, permisos y las regresiones de `tests/hallazgos_chatgpt.php`. La selección, las ventas y los nuevos ciclos bloquean primero la fila de su servicio para mantener una selección única y respetar el último cupo en operaciones concurrentes.
