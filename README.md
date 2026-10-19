# Ghost Store - PHP 8 MVC

Aplicacion web para la operacion de Ghost Store: gestion de clientes, membresias, vencimientos y renovaciones de servicios digitales.

El registro de avances, las validaciones realizadas y los pendientes de los apartados
ChatGPT y Claude están en [AVANCES.md](AVANCES.md).

## Stack

- PHP 8
- MySQL (PDO + prepared statements)
- Bootstrap 5
- MVC simple (sin frameworks)
- Front Controller: `public/index.php`
- Marca del sistema: `Ghost Store`
- Moneda del sistema: bolivianos (`Bs` / `BOB`)
- Formato numerico: Bolivia sin decimales (`1.234`)
- Zona horaria: Bolivia (`America/La_Paz`)

## Modulos incluidos

- Login con sesiones (`usuarios.password_hash` + `password_verify`)
- Dashboard por estados:
  - `CONTACTAR_2D`
  - `REENVIAR_1D`
  - `ESPERA`
  - `ACTIVO`
  - `VENCIDO`
  - `RECUP`
- Recalculo automatico de estados al cargar dashboard
- Acciones por suscripcion:
  - WhatsApp directo con plantillas por plataforma
  - Renovar meses segun configuracion de la plataforma (fallback: `+1`, `+3`, `+6`)
  - Marcar "No renovo"
- CRUD completo:
  - Clientes
  - Plataformas
  - Tipos de suscripcion
  - Suscripciones
- Duraciones disponibles por plataforma (ej. `1,3,7`) para validar tipos permitidos
- Dato de renovacion por plataforma renovable (`USUARIO` o `CORREO`) con validacion obligatoria en altas/ediciones de suscripcion
- Alta de cliente con suscripcion inicial (plataforma + duracion) en un solo paso

## Apartados ChatGPT y Claude

El apartado general de IA se reemplazó por accesos independientes a `/chatgpt`
y `/claude`. Cada servicio conserva sus cuentas, ventas, pagos y textos de forma
independiente.

ChatGPT se administra por cuenta principal, sin registrar clientes en cada venta:

- Se recomiendan 20 **dispositivos** por cuenta y el administrador configura su capacidad.
  Al llenarse, la cuenta sigue en distribución y todos los vendedores pueden
  continuar entregando hasta que el administrador seleccione otra cuenta.
  Una venta de 1 dispositivo
  cuesta Bs 30; una venta de 2 dispositivos cuesta Bs 55 y consume dos cupos.
- El administrador registra la fecha del pago y el costo real en bolivianos.
  El historial conserva también el costo nominal de USD 20; no se calcula
  un tipo de cambio automáticamente.
- Desde cada pago se cuenta un mes calendario pagado y después un mes calendario
  de gracia. Se ajustan fechas al último día del mes cuando es necesario.
- La gracia es interna: se vende como un mes normal. Una venta en el mes pagado
  vence al empezar la gracia; una venta en gracia vence al terminarla. Los
  períodos empiezan en su fecha inicial y terminan al comenzar la fecha límite.
- Pagar de nuevo inicia ambos períodos desde la nueva fecha de pago y conserva
  los cupos ocupados. Vencer tampoco libera cupos. Solo el administrador puede
  **liberar todos los cupos** de una cuenta; las ventas y pagos quedan en el historial.
- Las ventas no crean clientes ni suscripciones. Las asignaciones anteriores
  se conservan y sus dispositivos siguen ocupados hasta la liberación manual.
- `/chatgpt/distribucion` muestra la cuenta activa y permite registrar y copiar
  entregas de 1 o 2 dispositivos. `/chatgpt/plantillas` redirige a esa pantalla.
  Las entregas incluyen las instrucciones proporcionadas,
  con correo y contraseña de cada cuenta y concordancia para 1 o 2 dispositivos.
  La renovación general queda pendiente de configurar.
  Variables: `{CUENTA}`, `{CORREO}`, `{PASSWORD}`, `{DISPOSITIVOS}`, `{DISPOSITIVO_LABEL}`, `{PRECIO}`,
  y `{FECHA_VENCE}` (formato `YYYY-MM-DD`). La renovación general utiliza
  correo, nombre de cuenta y vencimiento; los precios se escriben en el texto
  porque el aviso se dirige a compradores de ambos planes.
- **Copiar entrega** en distribución registra una venta y ocupa sus dispositivos.
  **Volver a copiar** y los botones del historial no registran otra venta ni envían
  mensajes. Las ventas vencidas o liberadas conservan su historial pero no permiten
  copiar las credenciales actuales. Si el navegador impide copiar una nueva entrega,
  se muestra el texto para copiar manualmente sin volver a registrar la venta.
- El administrador puede importar cuentas existentes pegando correos, uno por línea.
  Se conservan las cuentas ya registradas y las nuevas quedan pendientes de fechas
  y credenciales. Esta importación no crea pagos, ventas ni clientes. Las cuentas
  marcadas para revisión conservan sus fechas anteriores como referencia y requieren
  confirmación del administrador antes de registrar nuevas ventas.
- «Revisar vigencia existente» solo se ofrece una vez para cuentas pendientes sin
  pagos registrados. Se ingresa la fecha del pago o el inicio de gracia.
  Si solo se conoce el inicio de gracia, la fecha original del pago queda sin confirmar.
  La revisión calcula las fechas siguientes y conserva dispositivos e historial,
  sin registrar un pago contable ni inventar su costo. Las renovaciones posteriores
  requieren registrar un pago.
- Para vender y copiar entregas se requieren fechas vigentes y credenciales completas.
- Las entregas estándar de ChatGPT y Claude incluyen `*texto*` para resaltar
  las partes importantes en WhatsApp. Las versiones estándar anteriores reciben
  el formato al generar el mensaje, incluida la opción «Volver a copiar».

Los administradores crean/editan cuentas, registran pagos, seleccionan la distribución,
liberan cupos y cambian el estado de las cuentas. Los operadores registran
ventas y copian los textos. Una cuenta inactiva o vencida no permite nuevas ventas.

En ChatGPT y Claude, el administrador puede usar **Editar dispositivos/cupos
ocupados** desde el contador de la cuenta o desde su pantalla de edición.
El ajuste establece el total ocupado, admite cero y cantidades superiores a la
capacidad y conserva las ventas y los pagos. Las entregas posteriores se suman a
ese total. Cada ajuste registra el valor anterior, el nuevo, el motivo y el usuario;
los diez últimos se muestran junto al formulario. Un formulario anterior a una
venta o a otro ajuste se rechaza para no perder cambios. Iniciar un nuevo ciclo
reinicia el contador y conserva también el historial de ajustes.

Para actualizar una instalación con las migraciones automáticas desactivadas:

```bash
php deploy/migrate_chatgpt.php
```

Este comando añade el esquema y clasifica las cuentas existentes; no borra
clientes, suscripciones, pagos ni ventas. Requiere el esquema base ya instalado.
La `APP_KEY` del `.env` debe conservarse al trasladar la base a otro servidor.

Pruebas del módulo: `php tests/chatgpt.php`. Usan una base temporal aislada y
la eliminan al terminar; el usuario de MySQL de pruebas requiere CREATE/DROP.

Claude tiene reglas propias: **5 cupos recomendados por cuenta**, una venta ocupa **1 cupo por
Bs 85**, y cada pago otorga **un mes calendario sin gracia**. El costo real de
la cuenta se registra manualmente en bolivianos; no se fija un costo en dólares.
Las ventas vencen con la cuenta y no registran clientes. Los pagos y el vencimiento
conservan los cupos ocupados hasta que el administrador los libera manualmente.
La capacidad es una referencia configurable; administradores y operadores pueden
superarla únicamente en la cuenta seleccionada para distribución. Solo existe una
cuenta seleccionada por servicio; elegir otra retira inmediatamente la anterior.

Los listados muestran primero la cuenta actual y después las reservas listas,
ordenadas por vencimiento final más cercano. Se señalan la siguiente cuenta y
sus posiciones de cola; las completas, bloqueadas e inactivas quedan después.
La selección sigue siendo manual y las búsquedas conservan esas prioridades.

El administrador puede usar **Distribuir** desde cada fila del listado. Una cuenta
Gratis preparada ofrece **Pagar y distribuir**: exige el costo real en Bs y registra
el pago de hoy junto con la selección, en una sola operación. Conserva los cupos,
el ciclo y el historial. Las cuentas ya vigentes se seleccionan sin registrar otro pago.

`/claude/distribucion` registra y copia entregas de un dispositivo;
`/claude/plantillas` redirige a esa pantalla. Se conservan los textos existentes.
Variables de entrega: `{CUENTA}`, `{CORREO}`, `{PASSWORD}`, `{CUPOS}`, `{PRECIO}` y
`{FECHA_VENCE}`. Para la renovación general se utilizan cuenta, correo y fecha;
el precio se puede escribir en el texto. Las plantillas quedan pendientes hasta
recibir su contenido. Pruebas de Claude: `php tests/claude.php`.

El comando `php deploy/migrate_chatgpt.php` actualiza el esquema de **ambos**
apartados, aunque conserva su nombre por compatibilidad.

Ambos servicios registran la fecha y hora del inicio de distribución al seleccionar
una cuenta y del primer llenado de cupos de cada ciclo. Las fechas se muestran en
el listado, el detalle y la distribución; el detalle conserva el historial por ciclo.
Volver a seleccionar, pagar o reabrir cupos conserva las fechas. **Iniciar nuevo ciclo**
abre un registro nuevo y conserva el anterior. Las fechas anteriores a esta función
quedan **Sin registrar**, sin estimaciones ni carga manual. La misma migración
incremental añade `cuentas_distribucion_historial`.

Pruebas adicionales: `php tests/chatgpt_textos.php` (sin acceso a DB) y
`php tests/chatgpt_existentes.php` (base temporal aislada).

Las mejoras de interfaz, navegación móvil y alcance de los reportes se describen en
`docs/mejoras-ui-ux.md`. `php tests/ui_ux.php` verifica los filtros y totales financieros,
la conservación del historial y la recuperación segura de formularios en una base temporal.

En escritorio, el menú lateral comienza compacto y se expande al pasar el cursor o
navegar con Tab. Se contrae al salir y admite Escape. **Fijar menú** lo mantiene
abierto y conserva la preferencia al recargar. En móvil se abre desde su botón.

ChatGPT incluye **Ver próximas fechas** para ordenar los cierres del mes pagado y los pagos
y cierres del fin de gracia. Cada aviso admite una anticipación configurable por el administrador
(3 días inicialmente). `php tests/chatgpt_avisos.php` verifica sus límites, permisos, configuración
y la separación entre vigencia por fechas y la marca manual Gratis en una base temporal aislada.
En entornos restringidos de Windows se puede indicar una carpeta de sesiones escribible:
`php -d session.save_path="%TEMP%" -d sys_temp_dir="%TEMP%" tests/chatgpt.php`
(en PowerShell, sustituir `%TEMP%` por `$env:TEMP`).

Las reglas de ciclos, entregas y protección de las suscripciones antiguas están
documentadas en [Distribución de cuentas](docs/distribucion-cuentas.md).

## Base de datos usada

El codigo esta alineado a estas tablas existentes:

- `usuarios`
- `clientes`
- `plataformas`
- `modalidades`
- `suscripciones`
- `movimientos`

## Configuracion

1. Copia `.env.example` a `.env`.
2. Configura credenciales DB y valores de app en `.env`.
   Define tambien `APP_KEY` antes de guardar contrasenas de cuentas. Para una
   instalacion nueva sin contrasenas cifradas, genera el valor con
   `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`. Si la base ya tiene
   contrasenas cifradas, recupera la clave original. Conserva esa misma clave
   en los respaldos y al trasladar el sistema; no la regeneres.
3. Para local, URL recomendada:
   - `http://localhost/streaming-renovaciones/public` (o el alias local que uses para Ghost Store)

Variables clave:

- `APP_ENV` (`development` o `production`)
- `APP_DEBUG` (`true`/`false`)
- `APP_RUN_MIGRATIONS` (`true`/`false`)
- `APP_KEY` (clave privada de cifrado, obligatoria para guardar contrasenas de cuentas)
- `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`

## Despliegue en Hostinger

1. En `public_html`, sube el proyecto completo.
2. Crea `.env` en la raiz del proyecto (puedes partir de `.env.example`) y coloca credenciales reales de Hostinger.
3. Importa la base de datos desde uno de los `.sql`.
4. Usa estos valores recomendados en produccion:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_RUN_MIGRATIONS=false`
5. El proyecto ya incluye:
   - `index.php` en raiz que carga `public/index.php`
   - `.htaccess` en raiz para enrutar y proteger carpetas sensibles
   - `public/.htaccess` para el front controller interno

Nota: si tu plan te permite definir document root del dominio en `public/`, tambien es valido y mas limpio.

## Usuario inicial

Si la tabla `usuarios` esta vacia, la pantalla de login habilita creacion de admin inicial.

## Rutas principales

Los formularios de creación y edición se abren en modales desde los listados. Al guardar se actualiza la pantalla de origen conservando los filtros y la posición. Desde clientes también se puede asignar una vigencia sin cambiar de pantalla. Los pagos y nuevos ciclos de cuentas usan el mismo modal. Los errores de validación conservan los datos introducidos.

También se renueva con vista previa de importe y nuevo vencimiento, se corrigen fechas, se consultan historiales de renovación y distribución y se ajustan cupos desde el índice. Completar contactos incorpora **Guardar y siguiente** para avanzar entre pendientes filtrados. **Mi perfil** se abre desde el encabezado con operaciones separadas de usuario y contraseña. Las renovaciones rechazan reenvíos y cambios de fecha o precio posteriores a abrir el formulario.

Ver [navegación y funcionamiento de los modales](docs/mejoras-ui-ux.md#formularios-en-modales). Pruebas HTTP aisladas: `php tests/modals.php`; con `--preview` se abre un servidor local con datos ficticios hasta presionar Enter en la terminal.

- `GET /login`
- `POST /login`
- `GET /logout`
- `GET /dashboard`
- `GET /clientes`, `POST /clientes`
- `GET /plataformas`, `POST /plataformas`
- `GET /tipos-suscripcion`, `POST /tipos-suscripcion`
- `GET /suscripciones`, `POST /suscripciones`

## Tipos de suscripcion (modalidades)

El modulo permite configurar por plataforma:

- Nombre del tipo (ej. `Cuenta completa`, `Por dispositivos`)
- Tipo de cuenta: `CUENTA_COMPLETA`, `POR_DISPOSITIVOS`, `AMBOS`
- Duracion configurable en meses (1, 3, 6 o cualquier valor)
- Cantidad de dispositivos (si aplica)
- Precio base

Opcionalmente, cada plataforma puede definir `duraciones_disponibles` (CSV, ejemplo `1,3,7`).
Si esta configurado, el sistema solo permite duraciones incluidas en esa lista para esa plataforma.

En cada suscripcion, el `precio_venta` es editable (puede diferir del precio base).
