# Carpeta de despliegue

Artefactos para subir a Hostinger. Ver la guía completa en
[`../DEPLOY_HOSTINGER.md`](../DEPLOY_HOSTINGER.md).

## Contenido

- `streaming_renovaciones_hostinger.sql` — **export completo de la base**
  (esquema + datos) para importar en phpMyAdmin de una base **ya creada**
  (no trae `CREATE DATABASE`/`USE`). Incluye:
  - Todas las tablas: `clientes`, `plataformas`, `modalidades`, `suscripciones`,
    `movimientos`, `usuarios`, `auditoria`, `login_intentos`.
  - La columna `password_cuenta` (contraseñas de cuentas, cifradas con tu `APP_KEY`).
  - Llaves foráneas corregidas: borrar una plataforma o un plan queda **bloqueado**
    si tiene datos asociados (`ON DELETE RESTRICT`), evitando pérdidas en cascada.
  - Usuario admin: `ghostbhm`.

## Importante

- El `.sql` **contiene datos de clientes**: no se versiona (está en `.gitignore`)
  y conviene **borrarlo del servidor** después de importarlo.
- Al importar, usa en el `.env` de Hostinger la **misma `APP_KEY`** que en local,
  o las contraseñas de cuentas quedarán ilegibles.
