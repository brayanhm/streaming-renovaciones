# Distribución de ChatGPT y Claude

Cada servicio tiene una sola cuenta seleccionada. El administrador configura la capacidad al crear o editar cada cuenta. Las cuentas existentes conservan su capacidad y sus accesos; la migración no elige una cuenta automáticamente.

## Orden predeterminado de los listados

Los índices de ChatGPT y Claude muestran primero la cuenta seleccionada, incluso si ya alcanzó la referencia. Después aparecen las reservas activas, con vigencia y credenciales válidas y ocupación inferior a la referencia, ordenadas por vencimiento final más cercano (fin de gracia en ChatGPT y fin pagado en Claude), nombre e ID. Estas cuentas llevan las indicaciones **Siguiente para distribución**, **En cola · 2**, etc.

A continuación se muestran las cuentas completas habilitadas, las bloqueadas activas y finalmente las inactivas. Dentro de cada grupo se conserva el orden por vencimiento, nombre e ID, dejando las fechas desconocidas al final. Buscar conserva estas prioridades; los resultados filtrados muestran **Lista para distribuir**, sin renumerar ni presentar su primera coincidencia como la siguiente global. El orden solo orienta al administrador: no cambia la selección, los cupos ni el historial.

## Selección desde el listado y pago de cuentas Gratis

El administrador puede pulsar **Distribuir** en una cuenta preparada directamente desde el índice de ChatGPT o Claude. La selección vuelve al mismo listado y conserva la búsqueda. Reemplaza la cuenta actual sin liberar cupos ni borrar ventas, pagos o fechas. Seleccionar una cuenta pagada o en gracia no registra otro gasto.

Las cuentas **Gratis**, tanto por vencimiento como por marca manual, ofrecen **Pagar y distribuir** cuando están activas y tienen credenciales válidas. Al desplegarlo se solicita el costo real en Bs, obligatorio y mayor que cero. **Registrar pago y distribuir** guarda el gasto y comienza la vigencia en la fecha de hoy del servidor, en hora de Bolivia: un mes pagado y un mes de gracia para ChatGPT; un mes pagado para Claude. No se reutiliza automáticamente el costo anterior ni se acepta otra fecha enviada por el formulario. Esta acción también sirve para una cuenta Gratis que continúe seleccionada.

El pago y la selección se ejecutan en una sola transacción. Cualquier error revierte ambas cosas. Repetir una operación o recibir dos solicitudes simultáneas no duplica el gasto; si la cuenta ya fue pagada desde otra pestaña, se exige recargar el listado. Un reintento antiguo tampoco vuelve a seleccionar una cuenta reemplazada. Los permisos de administrador y el usuario activo se comprueban en el servidor; el listado usa un token compartido de selección para que una lista larga no invalide sus primeras filas.

Esta acción conserva la ocupación y el ciclo actual. La fecha de pago pasa a ser hoy, pero el inicio de distribución ya conocido del ciclo se mantiene; si estaba sin registrar, se registra el evento de selección nuevo. Reiniciar cupos y abrir otro ciclo sigue requiriendo **Iniciar nuevo ciclo** y confirmar el cierre de sesiones. Las cuentas inactivas, sin credenciales o con vigencia pendiente sin marca Gratis deben completarse antes de distribuir.

`tests/distribucion_seleccion.php` y `tests/distribucion_pago.php`, incluidos en `tests/claude.php`, cubren el índice, los permisos, los costos, las fechas, la reversión ante fallos y la concurrencia de pagos para ambos servicios.

## Operación

1. Abrir una cuenta con credenciales completas, activa y pagada o en gracia.
2. Usar **Seleccionar para distribución** para cambiar la cuenta actual conservando todos los accesos y cupos ocupados. También permite volver a una cuenta que había quedado en reserva.
3. Cuando la ocupación alcanza o supera la capacidad de referencia (20 dispositivos en ChatGPT y 5 cupos en Claude, configurables), se registra el primer llenado, pero la cuenta seleccionada sigue **En distribución**. Administradores y vendedores pueden seguir entregando en ella hasta que el administrador seleccione otra. El cambio reemplaza la selección de forma atómica: la anterior conserva sus cupos e historial y bloquea nuevas ventas, incluidos los formularios que quedaron abiertos. Si la anterior está llena, se muestra **Vendida**; de lo contrario queda en reserva. Una venta de dos dispositivos ChatGPT consume dos cupos. Los avisos de plan Gratis, inactividad, credenciales y vigencia conservan sus bloqueos para todos los roles. Crear, pagar o activar una cuenta no cambia automáticamente la selección.
4. Para retirar accesos anteriores y reutilizar una cuenta, cerrar sus sesiones fuera del sistema, desplegar **Iniciar nuevo ciclo** y confirmar el cierre en todos los dispositivos. El ciclo reinicia la ocupación, selecciona la cuenta y conserva los historiales de ventas, pagos y reinicios.

Registrar un pago o cambiar la selección no reinicia la ocupación. Un formulario repetido de ciclo devuelve el resultado original sin liberar ventas nuevas.

## Fechas e historial de distribución

Cada cuenta conserva el inicio de distribución y el primer llenado de cada ciclo, con fecha y hora de Bolivia. El inicio se registra al seleccionar la cuenta para distribución o al confirmar **Iniciar nuevo ciclo**, que también la selecciona. Cambiar temporalmente a otra cuenta y volver conserva el inicio original.

El primer llenado se registra cuando la ocupación pasa de estar por debajo de su capacidad a alcanzarla o superarla. Se contemplan las ventas, los ajustes de ocupación y las reducciones de capacidad. También se guardan la capacidad y la ocupación de ese momento. Liberar cupos, aumentar la capacidad o volver a llenar no reemplaza esa primera fecha. Un nuevo pago, marcar Gratis o cambiar la actividad tampoco reinicia el registro.

Las fechas del ciclo actual se muestran en el listado, el detalle y la pantalla de distribución. Registrar la última entrega actualiza la fecha de llenado sin recargar. El detalle incluye **Historial de distribución**, visible también para operadores, con los ciclos registrados. Iniciar un nuevo ciclo conserva las fechas anteriores y deja pendiente el llenado del nuevo.

La tabla `cuentas_distribucion_historial` se crea con la migración compartida y no reconstruye eventos anteriores. Las cuentas existentes muestran **Sin registrar** cuando no se conoce una fecha. Una selección repetida de la cuenta que ya estaba seleccionada no inventa su inicio. Si una cuenta existente registra una transición nueva a llena, se guarda ese evento y su inicio anterior puede seguir sin registrar. Los ciclos antiguos sin eventos nuevos conservan su historial de reinicios, pero no reciben fechas de distribución supuestas.

`tests/distribucion_fechas.php`, incluido en `tests/claude.php`, verifica estas reglas para ambos servicios, la migración repetida, las respuestas de entrega y las tres pantallas.

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

`tests/claude.php` incluye los casos de distribución, entregas y ajustes de ocupación de ambos servicios, permisos y las regresiones de `tests/hallazgos_chatgpt.php`. La selección, las ventas y los nuevos ciclos bloquean primero la fila de su servicio para mantener una selección única y contabilizar todas las entregas concurrentes, incluso al superar la referencia.
