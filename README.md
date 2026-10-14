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

- Se recomiendan 20 **dispositivos** por cuenta. El sistema avisa cuando se alcanza
  o supera esa cantidad y permite seguir registrando ventas. Una venta de 1 dispositivo
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
- `/chatgpt/plantillas` configura entrega de 1 dispositivo, entrega de 2
  dispositivos y renovación. Las entregas incluyen las instrucciones proporcionadas,
  con correo y contraseña de cada cuenta y concordancia para 1 o 2 dispositivos.
  La renovación general queda pendiente de configurar.
  Variables: `{CUENTA}`, `{CORREO}`, `{PASSWORD}`, `{DISPOSITIVOS}`, `{DISPOSITIVO_LABEL}`, `{PRECIO}`,
  y `{FECHA_VENCE}` (formato `YYYY-MM-DD`). La renovación general utiliza
  correo, nombre de cuenta y vencimiento; los precios se escriben en el texto
  porque el aviso se dirige a compradores de ambos planes.
- Copiar una entrega o renovación no registra otra venta ni envía mensajes.
  Al guardar una venta se intenta copiar su entrega; si el navegador lo impide,
  se muestra el texto para copiar manualmente.
- El administrador puede importar cuentas existentes pegando correos, uno por línea.
  Se conservan las cuentas ya registradas y las nuevas quedan pendientes de fechas
  y credenciales. Esta importación no crea pagos, ventas ni clientes. Las cuentas
  marcadas para revisión conservan sus fechas anteriores como referencia y requieren
  confirmación del administrador antes de registrar nuevas ventas.
- En «Revisar vigencia existente» se ingresa la fecha del pago o el inicio de gracia.
  Si solo se conoce el inicio de gracia, la fecha original del pago queda sin confirmar.
  La revisión calcula las fechas siguientes y conserva dispositivos e historial,
  sin registrar un pago contable ni inventar su costo.
- Para vender y copiar entregas se requieren fechas vigentes y credenciales completas.

Los administradores crean/editan cuentas, registran pagos, configuran textos,
liberan cupos y cambian el estado de las cuentas. Los operadores registran
ventas y copian los textos. Una cuenta inactiva o vencida no permite nuevas ventas.

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
Superar los 5 cupos muestra una advertencia y permite continuar vendiendo.

Los textos de Claude se configuran en `/claude/plantillas`: entrega y renovación.
Variables de entrega: `{CUENTA}`, `{CORREO}`, `{PASSWORD}`, `{CUPOS}`, `{PRECIO}` y
`{FECHA_VENCE}`. Para la renovación general se utilizan cuenta, correo y fecha;
el precio se puede escribir en el texto. Las plantillas quedan pendientes hasta
recibir su contenido. Pruebas de Claude: `php tests/claude.php`.

El comando `php deploy/migrate_chatgpt.php` actualiza el esquema de **ambos**
apartados, aunque conserva su nombre por compatibilidad.

Pruebas adicionales: `php tests/chatgpt_textos.php` (sin acceso a DB) y
`php tests/chatgpt_existentes.php` (base temporal aislada).

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
3. Para local, URL recomendada:
   - `http://localhost/streaming-renovaciones/public` (o el alias local que uses para Ghost Store)

Variables clave:

- `APP_ENV` (`development` o `production`)
- `APP_DEBUG` (`true`/`false`)
- `APP_RUN_MIGRATIONS` (`true`/`false`)
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
