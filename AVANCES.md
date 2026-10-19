# Avances de implementación — Ghost Store

Fecha de registro: **14 de octubre de 2026**. Zona horaria del sistema: `America/La_Paz`.

## Actualización del 19 de octubre de 2026: menú automático

### Ajustes de navegación móvil

Se implementaron los cinco hallazgos de `docs/revision-movil.md`: zonas táctiles de 44 × 44 px, encabezado compacto con accesos identificados para lectores de pantalla, tarjetas para historiales y Auditoría, modales completos con poca altura y distribución con las dos opciones de entrega visibles en la primera pantalla a 360 × 740. Las tablas cargadas en modales se inicializan de forma reutilizable e idempotente. En escritorio conservan todas sus columnas.

El índice de ChatGPT muestra su primera cuenta alrededor de 684 px desde el inicio, frente a 1115 px antes del ajuste, con los mismos datos ficticios. Se verificaron nueve secciones en teléfono y tableta, detalles, menú, guardado con filtros y entrega/copia. Las 21 pruebas HTTP de modales pasaron y se verificó sintaxis PHP/JavaScript. Esta ampliación no requiere migraciones.

El menú de escritorio comienza compacto y se expande al entrar con el cursor o navegar con Tab. Se contrae al salir tras una demora de 240 ms; Escape devuelve el foco al contenido. La apertura automática se superpone al contenido para conservar la posición de tablas y filtros. **Fijar menú** reserva el ancho completo y conserva la preferencia al recargar; **Desfijar menú** vuelve al modo automático. En móvil se conserva el panel con botón de apertura y cierre.

Se verificaron cursor, teclado, cierre, fijación y recarga en el navegador con datos ficticios. Las 21 pruebas HTTP de modales volvieron a pasar. El cambio no requiere migraciones.

## Actualización del 19 de octubre de 2026: formularios en modales

### Ampliación de los siete flujos propuestos

Se incorporaron renovación desde el panel con importe y vencimiento calculados, edición de fecha y reactivación sin ingreso, historial de renovaciones, ajuste independiente de cupos, contactos pendientes con **Guardar y siguiente**, historial de distribución desde ambos índices y perfil desde el encabezado. Los historiales se abren en un modal amplio con **Cerrar**. El perfil separa usuario y contraseña y cambia de operación dentro del mismo modal. El ajuste de cupos conserva el control de administrador y la confirmación.

La renovación comparte sus cálculos de modalidades, importes personalizados y calendario con el modelo. Un token por formulario y la comprobación dentro de la transacción rechazan reenvíos y cambios de vencimiento o precio posteriores a la vista previa. Corregir fechas sigue sin crear movimientos financieros. La cola de contactos recarga el listado filtrado y abre el siguiente pendiente tras guardar; se verificó también el último registro.

Validación de esta ampliación: **145 pruebas aprobadas**, sin fallos (`modals.php`: 21, incluyendo 13 casos adicionales; `claude.php`: 95; `chatgpt.php`: 20; `ui_ux.php`: 9). Se comprobaron en navegador los siete flujos, el importe y fecha al cambiar de período, confirmación de cupos, conservación de filtros, historiales de consulta y avance entre contactos. Todas las escrituras de prueba se hicieron en bases efímeras con datos ficticios; no se requieren cambios de esquema para esta ampliación.

Crear y editar clientes, suscripciones, plataformas, planes, usuarios y cuentas se realiza desde un modal, conservando la búsqueda, los filtros, la página y la posición de origen al guardar. ChatGPT y Claude tienen **Editar** directamente en el índice. Asignar vigencia desde clientes, registrar pagos, pagar y distribuir, revisar vigencias e iniciar nuevos ciclos también usan modales. La edición conjunta de costos y precios permite elegir la plataforma dentro del mismo modal.

Los formularios largos con una sola operación conservan **Guardar** y **Cancelar** visibles en el pie. Los errores del servidor permanecen junto a los campos sin descartar los datos. Las confirmaciones de ajustes funcionan dentro del modal; se bloquean envíos repetidos mientras se guarda. Las páginas directas siguen disponibles y este cambio no requiere migración ni despliegue.

Se probaron creación, edición, validación, cancelación, asignación de vigencias, pagos y confirmación de ocupación en el navegador usando una base efímera con datos ficticios. La suite `tests/modals.php` comprueba ocho flujos HTTP, incluidos validación, persistencia, CSRF, carga de formularios y sesión expirada. También se ejecutaron las suites de Claude (95), ChatGPT (20) y UI (9), sin fallos.

## Actualización del 19 de octubre de 2026: fechas de distribución

### Seleccionar desde el índice y pagar cuentas Gratis

El administrador puede seleccionar una cuenta desde la fila del listado con **Distribuir**, conservando la búsqueda al regresar. Las cuentas Gratis activas y con credenciales ofrecen **Pagar y distribuir**, que solicita el costo real en Bs y registra el pago de hoy en Bolivia junto con la selección. ChatGPT obtiene un mes pagado y un mes de gracia; Claude, un mes pagado. Las cuentas vigentes se seleccionan sin otro gasto. Se conservan la ocupación, el ciclo y su inicio ya conocido; un inicio desconocido se registra al efectuar la nueva selección.

Pago y selección forman una sola transacción: si falla una parte se revierte todo. Los reintentos no duplican gastos ni vuelven a seleccionar una cuenta reemplazada, y dos solicitudes simultáneas solo registran un pago de activación. Los formularios Gratis antiguos se rechazan si la cuenta ya fue pagada. El listado usa un token de selección compartido y mantiene la comprobación del administrador en el servidor.

Validación de este ajuste: **137 pruebas aprobadas**, sin fallos (`claude.php`: 95; `chatgpt.php`: 20; `chatgpt_existentes.php`: 22). Se verificaron costo real, fecha de hoy, conservación de cupos, reversión ante fallos, concurrencia y permisos. En el navegador se revisaron el botón directo para cuentas vigentes y el formulario desplegable para cuentas Gratis con datos ficticios. No se registraron pagos ni selecciones de prueba en cuentas reales; no se requiere una migración adicional para este ajuste.

### Orden predeterminado de próximas cuentas

Los índices de ChatGPT y Claude muestran primero la cuenta actual y luego las reservas activas, vigentes, con credenciales válidas y ocupación inferior a la referencia. Se ordenan por vencimiento final más cercano (incluida la gracia en ChatGPT), nombre e ID, con las indicaciones **Siguiente para distribución** y **En cola**. Las completas, bloqueadas e inactivas aparecen después. Buscar conserva estas prioridades y muestra disponibilidad sin renumerar la cola global. El listado no cambia automáticamente la selección.

Validación de este ajuste: **101 pruebas aprobadas**, sin fallos (`claude.php`: 81; `chatgpt.php`: 20), incluyendo cuatro casos nuevos de orden, disponibilidad, identificación de la siguiente cuenta y búsquedas. Se verificó la presentación en el navegador con cuentas ficticias.

### Distribución continua hasta seleccionar la siguiente cuenta

La capacidad se utiliza como referencia: al llegar a 20 dispositivos ChatGPT o 5 cupos Claude (o al valor configurado), la cuenta seleccionada continúa **En distribución** y todos los vendedores pueden seguir entregando. La primera fecha de llenado se conserva. Solo al seleccionar otra cuenta se retira la anterior de distribución; la anterior mantiene sus ventas e historial y ya no acepta nuevas ventas desde formularios abiertos. Hay una sola selección por servicio y crear, pagar o activar cuentas no la cambia automáticamente.

Validación de este ajuste: **97 pruebas aprobadas**, sin fallos (`claude.php`: 77; `chatgpt.php`: 20), incluyendo vendedores por encima de la referencia, cambio inmediato de cuenta, formularios anteriores, conteos concurrentes y conservación del primer llenado. En el navegador se verificó una cuenta ficticia ChatGPT con **22 / 20** dispositivos y los botones de entrega habilitados para un operador.

ChatGPT y Claude registran el inicio al seleccionar una cuenta y el primer llenado de cada ciclo, incluyendo ventas, ajustes de ocupación y reducciones de capacidad. Volver a seleccionar, pagar o reabrir cupos conserva las fechas; iniciar un nuevo ciclo abre otro registro y conserva el anterior. Se muestran en el listado, el detalle y la distribución, con el historial por ciclo dentro del detalle. Las entregas actualizan la fecha de llenado sin recargar.

La tabla `cuentas_distribucion_historial` se creó en la base local sin reconstruir fechas anteriores ni generar ventas. Las cuentas existentes mantienen **Sin registrar**. Para Hostinger se requiere aplicar la migración incremental habitual; esta actualización se realizó localmente.

Validación: **132 pruebas aprobadas**, sin fallos (`claude.php`: 75; `chatgpt.php`: 20; `chatgpt_existentes.php`: 22; `run.php`: 15). La suite de Claude incluye las nuevas pruebas compartidas de fechas, respuestas de entrega y renderizado. Se revisaron el detalle con dos ciclos, el listado y la distribución en el navegador con datos ficticios de bases temporales, sin ventas de prueba en cuentas reales.

## Estado de esta etapa

Se implementaron apartados independientes para **ChatGPT** y **Claude**, reemplazando el apartado general de IA. Los cambios están disponibles en el proyecto local y las migraciones se aplicaron a la base local. Esta etapa no incluye un despliegue en Hostinger ni un commit de los cambios.

La carga inicial de ChatGPT quedó preparada para que el administrador confirme las fechas y complete las credenciales antes de vender. Los textos de entrega de ChatGPT ya están incorporados; los textos de Claude y de renovación siguen pendientes de contenido.

## Reglas confirmadas e implementadas

| Regla | ChatGPT | Claude |
| --- | --- | --- |
| Cantidad recomendada por cuenta principal | 20 dispositivos | 5 cupos |
| Ventas por encima de la recomendación | Permitidas, con advertencia y contador exacto | Permitidas, con advertencia y contador exacto |
| Modalidades y precio | 1 dispositivo: Bs 30; 2 dispositivos juntos: Bs 55 | 1 cupo: Bs 85 |
| Vigencia después de cada pago | Un mes calendario pagado y luego un mes calendario de gracia | Un mes calendario, sin gracia |
| Vencimiento de una venta | Fin del mes pagado si se vende en ese período; fin de gracia si se vende durante la gracia | Vencimiento de la cuenta principal |
| Costo de la cuenta principal | USD 20 nominales; monto real cobrado en Bs ingresado manualmente | Monto real cobrado en Bs ingresado manualmente |
| Registro de clientes en estas ventas | No se solicita; se registra la venta y sus dispositivos | No se solicita; se registra la venta y su cupo |
| Liberación de ocupación | Manual, por el administrador | Manual, por el administrador |

La gracia de ChatGPT es un beneficio interno del negocio y se vende al cliente como un mes normal. Los meses se calculan por calendario, ajustando al último día del mes cuando corresponde. La fecha límite marca el comienzo del día en que termina el período.

Un nuevo pago inicia la vigencia desde la fecha que registra el administrador y conserva la ocupación. El administrador puede pagar antes de terminar la gracia o al finalizarla. El vencimiento tampoco libera dispositivos o cupos automáticamente. La liberación conserva el historial de ventas y pagos.

## Funciones terminadas

- Navegación independiente a `/chatgpt` y `/claude`, con sus propias cuentas, ventas, pagos y plantillas. Las rutas anteriores de IA redirigen a ChatGPT.
- Alta y edición de cuentas principales, activación/desactivación, registro de pagos y costo real en bolivianos.
- Contadores de ocupación, cantidad restante hasta la recomendación y exceso sobre ella. Una venta de dos dispositivos ocupa dos cupos, aunque sea una sola venta.
- Venta anónima con fecha, importe, cantidad y vencimiento guardados en el historial. Las asignaciones anteriores se conservan y cuentan hasta la liberación manual.
- Historial independiente de pagos y ventas por servicio, más registro de las liberaciones manuales.
- Textos de entrega configurables y botón de renovación por cuenta. Copiar no aumenta la ocupación ni envía mensajes; el envío y la búsqueda por correo en WhatsApp son manuales.
- Copia del texto al registrar una venta. Si el navegador impide la copia, se muestra el contenido para copiarlo manualmente; también se puede volver a copiar desde el historial.
- Importación de cuentas existentes de ChatGPT mediante correos, uno por línea, con validación y deduplicación. Las cuentas ya registradas se conservan; las nuevas quedan pendientes de revisión.
- Revisión de vigencia existente mediante **fecha del pago** o **inicio del mes de gracia**, sin crear un pago contable ni modificar la ocupación.
- Restricción de nuevas ventas a cuentas activas, con vigencia confirmada y credenciales completas.

Los permisos separan las tareas: el administrador administra cuentas, importa correos, revisa fechas, registra pagos, libera cupos y configura textos; el operador registra ventas y copia textos. Las operaciones comprueban el usuario y su rol, usan protección CSRF y tokens asociados a la cuenta y a la acción. Ventas y pagos se procesan con transacciones y controles para evitar duplicados al repetir la misma operación.

Las contraseñas de cuentas se guardan cifradas con `APP_KEY`. La clave del entorno local debe conservarse al trasladar la base a otro servidor. Este documento no contiene correos, contraseñas ni valores de esa clave.

## Carga inicial de cuentas ChatGPT

Se cotejaron los correos con la captura completa proporcionada por el administrador y se registraron **14 cuentas principales**: 13 nuevas y una que ya existía. Se corrigió el correo que estaba cortado en la primera captura, conservando la misma cuenta y su historial.

Al cerrar esta carga, las 14 cuentas estaban marcadas para revisión manual de vigencia. Se conservaron los datos anteriores de la cuenta existente como referencia. La carga no generó pagos, ventas ni clientes, y no se asignaron fechas o costos supuestos.

Cuando se confirma el inicio de gracia sin conocer el pago original, la fecha de ese pago queda sin confirmar. El sistema calcula el fin de gracia a partir de la fecha conocida. Cuando se confirma la fecha del pago, calcula el mes pagado y el mes de gracia. Ambas revisiones conservan la ocupación y el historial; registrar un pago nuevo es una acción distinta.

## Procedimiento para el administrador

1. Abrir **ChatGPT → Cuentas ChatGPT** y seleccionar una cuenta precargada.
2. En **Editar cuenta**, completar o verificar las credenciales. Dejar el campo de nueva contraseña vacío conserva la contraseña guardada.
3. En **Revisar vigencia existente**, elegir la fecha que se conoce: pago o inicio de gracia. Introducir la fecha comprobada y guardar la revisión.
4. Registrar ventas de uno o dos dispositivos cuando la cuenta tenga vigencia y credenciales completas. El contador se actualiza y se prepara el texto de entrega.
5. En cada pago posterior, usar **Registrar pago** con la fecha real y el monto cobrado en Bs. El sistema inicia la nueva vigencia y conserva los dispositivos ocupados.
6. Usar **Liberar todos los cupos** cuando el administrador haya retirado los accesos anteriores. La liberación es una decisión manual y conserva los registros históricos.

En Claude se registra la venta de un cupo a Bs 85 y cada pago da un mes de vigencia sin gracia. Los cupos también se liberan manualmente.

## Estado de los mensajes

| Mensaje | Estado |
| --- | --- |
| Entrega ChatGPT, 1 dispositivo | Incorporado con el texto proporcionado |
| Entrega ChatGPT, 2 dispositivos | Incorporado, con concordancia para dos dispositivos |
| Renovación ChatGPT | Botón y configuración disponibles; contenido pendiente |
| Entrega Claude | Configuración disponible; contenido pendiente |
| Renovación Claude | Botón y configuración disponibles; contenido pendiente |

El texto de ChatGPT sustituye el correo y la contraseña por los datos de la cuenta seleccionada, y ajusta “1 dispositivo” o “2 dispositivos”. Las instrucciones proporcionadas se conservan como contenido editable. Las migraciones agregan las plantillas iniciales ausentes y respetan las que el administrador haya personalizado, incluso si las dejó vacías.

Las variables disponibles y las reglas de cada plantilla están descritas en [README.md](README.md#apartados-chatgpt-y-claude).

## Validación realizada

La última validación de la implementación, realizada el 14 de octubre de 2026, terminó con **84 pruebas automatizadas aprobadas**:

| Suite | Resultado | Cobertura principal |
| --- | --- | --- |
| `tests/run.php` | 15 aprobadas | Funciones generales del sistema |
| `tests/chatgpt.php` | 19 aprobadas | Vigencia, ventas, pagos, ocupación, concurrencia y plantillas ChatGPT |
| `tests/claude.php` | 20 aprobadas | Mes sin gracia, ventas, pagos, ocupación y concurrencia Claude |
| `tests/chatgpt_existentes.php` | 22 aprobadas | Importación, revisión manual, fecha de pago desconocida y credenciales |
| `tests/chatgpt_textos.php` | 8 aprobadas | Texto de entrega, variables y singular/plural |

También se verificaron la sintaxis de los archivos PHP y el formato del diff. En el navegador se comprobaron los siguientes recorridos con cuentas ficticias y una base aislada:

- Importar cuentas pendientes y revisar una cuenta usando el inicio de gracia, manteniendo sin confirmar el pago original.
- Completar credenciales y vender dos dispositivos con el texto de entrega correcto.
- Vender dos dispositivos con 19 ocupados: el contador pasó a **21**, mostró el exceso y permitió seguir vendiendo.
- Vender un cupo con cinco ocupados en Claude: el contador pasó a **6**, mostró el exceso y permitió seguir vendiendo.
- Copiar la entrega con los datos de la cuenta de prueba y “2 dispositivos”, sin variables pendientes de sustituir.

Las pruebas de base de datos usan esquemas temporales; sus usuarios MySQL requieren permisos para crear y eliminar esos esquemas. Las sesiones, archivos, servidor y base temporales de la prueba en navegador se eliminaron al terminar. No se realizaron ventas de prueba en las cuentas reales.

Para repetir las suites desde la raíz del proyecto:

```bash
php tests/run.php
php tests/chatgpt.php
php tests/claude.php
php tests/chatgpt_existentes.php
php tests/chatgpt_textos.php
```

## Ubicación de la implementación

| Parte | Archivos principales |
| --- | --- |
| Lógica compartida | `app/models/CuentaCompartida.php`, `app/controllers/CuentasCompartidasController.php` |
| Reglas ChatGPT y revisión/importación | `app/models/ChatgptCuenta.php`, `app/controllers/ChatgptController.php` |
| Reglas Claude | `app/models/ClaudeCuenta.php`, `app/controllers/ClaudeController.php` |
| Pantallas | `app/views/chatgpt/`, `app/views/claude/` |
| Esquema incremental | `app/config/chatgpt_migrations.php`, `app/config/claude_migrations.php`, `app/config/migrations.php` |
| Texto inicial de ChatGPT | `app/config/chatgpt_templates.php` |
| Rutas y navegación | `public/index.php`, `app/views/layouts/header.php` |
| Actualización por consola | `deploy/migrate_chatgpt.php` |

El comando conserva el nombre `migrate_chatgpt.php` por compatibilidad, pero actualiza **ambos servicios**. Para una instalación anterior con migraciones automáticas desactivadas:

```bash
php deploy/migrate_chatgpt.php
```

El esquema base debe existir previamente. La guía de actualización y publicación está en [DEPLOY_HOSTINGER.md](DEPLOY_HOSTINGER.md#5-verificación-post-deploy).

## Pendientes para continuar

Actualización UI/UX del 19/10/2026: filtros y paginación de cuentas, acciones compactas, encabezados fijos, resúmenes desplegables, importes/fechas uniformes, panel orientado a pendientes y protección de cambios en modales. La documentación y validación están en [docs/mejoras-ui-ux.md](docs/mejoras-ui-ux.md#listados-y-protección-de-cambios). Estos ajustes no requieren migraciones de base de datos.

- [ ] El administrador confirma la vigencia de las cuentas precargadas y completa las credenciales faltantes.
- [ ] Recibir y configurar el texto de entrega de Claude.
- [ ] Definir y configurar los textos de renovación de ChatGPT y Claude.
- [ ] Cuando se solicite publicar la actualización, aplicar las migraciones y verificar los recorridos en Hostinger siguiendo la guía de despliegue.

Las pruebas registradas acreditan el comportamiento local. La revisión real de fechas, credenciales y accesos de cada cuenta corresponde al administrador.
