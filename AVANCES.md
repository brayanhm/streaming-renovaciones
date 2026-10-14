# Avances de implementación — Ghost Store

Fecha de registro: **14 de octubre de 2026**. Zona horaria del sistema: `America/La_Paz`.

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

- [ ] El administrador confirma la vigencia de las cuentas precargadas y completa las credenciales faltantes.
- [ ] Recibir y configurar el texto de entrega de Claude.
- [ ] Definir y configurar los textos de renovación de ChatGPT y Claude.
- [ ] Cuando se solicite publicar la actualización, aplicar las migraciones y verificar los recorridos en Hostinger siguiendo la guía de despliegue.

Las pruebas registradas acreditan el comportamiento local. La revisión real de fechas, credenciales y accesos de cada cuenta corresponde al administrador.
