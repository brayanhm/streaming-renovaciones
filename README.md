# Streaming Renovaciones - PHP 8 MVC

Aplicacion web completa para gestion de clientes, suscripciones, vencimientos y renovaciones de servicios streaming/IPTV.

## Stack

- PHP 8
- MySQL (PDO + prepared statements)
- Bootstrap 5
- MVC simple (sin frameworks)
- Front Controller: `public/index.php`

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
  - Renovar `+1`, `+3`, `+6` meses (inserta en `movimientos`)
  - Marcar "No renovo"
- CRUD completo:
  - Clientes
  - Plataformas
  - Tipos de suscripcion
  - Suscripciones

## Base de datos usada

El codigo esta alineado a estas tablas existentes:

- `usuarios`
- `clientes`
- `plataformas`
- `modalidades`
- `suscripciones`
- `movimientos`

## Configuracion

1. Verifica credenciales en `app/config/config.php`.
2. Apunta Apache a `public/` (o entra por `/public`).
3. URL local recomendada:
   - `http://localhost/streaming-renovaciones/public`

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

En cada suscripcion, el `precio_venta` es editable (puede diferir del precio base).
