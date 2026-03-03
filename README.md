# Ghost Store - PHP 8 MVC

Aplicacion web para la operacion de Ghost Store: gestion de clientes, membresias, vencimientos y renovaciones de servicios digitales.

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
