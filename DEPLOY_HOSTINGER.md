# Deploy en Hostinger (Ghost Store)

Guía completa para subir el proyecto y su base de datos a Hostinger.

> **Antes de empezar:** si tu Hostinger ya tiene datos en producción, **haz un
> respaldo de esa base** (phpMyAdmin → Exportar) antes de importar, porque la
> importación de este proyecto reemplaza las tablas.

---

## 1) Qué subir (y qué NO)

Sube el proyecto completo a la carpeta del dominio (normalmente `public_html`):

```
index.php
.htaccess
app/
public/
storage/
.env            (creado a partir de .env.example, con valores de producción)
```

**No subir:**
- `.git/`, `deploy/` (salvo el `.sql` que importarás y luego borras), `tests/`
- Los volcados `.sql` de la raíz (`streaming_renovaciones*.sql`) y `storage/backups/`
- Tu `.env` local (crea uno nuevo de producción en el servidor)

---

## 2) Configurar el `.env` de producción

Copia `.env.example` a `.env` en el servidor y complétalo:

```env
APP_ENV=production
APP_DEBUG=false
APP_RUN_MIGRATIONS=false
APP_TIMEZONE=America/La_Paz
RECUP_DAYS=3
CONTACT_DAYS=3

# CRITICO: la MISMA clave de tu .env local (ver paso 2.1)
APP_KEY=...

DB_HOST=localhost
DB_PORT=3306
DB_NAME=tu_base
DB_USER=tu_usuario
DB_PASS=tu_password
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

### 2.1) `APP_KEY` (importante)

La `APP_KEY` cifra las **contraseñas de las cuentas** guardadas en la base. El
export ya trae contraseñas cifradas con tu clave local, así que en Hostinger
debes usar **exactamente la misma**. Cópiala de tu `.env` local:

```bash
# En tu máquina, muestra la línea a copiar:
grep APP_KEY .env
```

Pega ese valor tal cual en el `.env` del servidor. Si pones otra clave, las
contraseñas guardadas quedarán ilegibles (lo demás sigue funcionando).

> `APP_RUN_MIGRATIONS=false` es correcto: el export ya trae el esquema completo
> (incluye `password_cuenta`, `auditoria`, `login_intentos` y las llaves foráneas
> corregidas), así que no hace falta que corran migraciones en producción.

---

## 3) Importar la base de datos

1. En hPanel crea la base de datos y el usuario; anota `DB_NAME`, `DB_USER`, `DB_PASS`.
2. Entra a **phpMyAdmin** → selecciona la base → pestaña **Importar**.
3. Sube el archivo:
   ```
   deploy/streaming_renovaciones_hostinger.sql
   ```
   (charset UTF-8; el archivo ya incluye `DROP TABLE`/`CREATE`/`INSERT` y **no**
   trae `CREATE DATABASE`/`USE`, así que importa directo en tu base ya creada).
4. Verifica que `DB_NAME`, `DB_USER` y `DB_PASS` del `.env` coincidan con lo creado.
5. Por seguridad, **borra el `.sql` del servidor** después de importar (contiene datos de clientes).

Usuario admin incluido en el export: **`ghostbhm`** (usa tu contraseña actual).

---

## 4) Publicar y permisos

1. Sube los archivos a `public_html` (o la carpeta del dominio). El `index.php` y
   el `.htaccess` de la raíz ya enrutan todo y sirven los estáticos desde `public/`.
2. Da permiso de **escritura** a:
   - `storage/logs/`   (para `app.log`)
   - `storage/backups/` (para el respaldo automático antes de importar CSV, opcional)
3. Abre el dominio y prueba el **login**.

---

## 5) Verificación post-deploy

- [ ] Entra al login y accede con `ghostbhm`.
- [ ] El **logo** carga (dashboard/navbar).
- [ ] El **dashboard** muestra las suscripciones y los contadores.
- [ ] **Reportes** abre sin error (KPIs + gráfico).
- [ ] En **Clientes → un cliente** se ve la contraseña de la cuenta descifrada
      (confirma que `APP_KEY` quedó bien).
- [ ] Prueba una **importación CSV** pequeña (opcional).

---

## 6) Si algo falla

1. Pon temporalmente `APP_DEBUG=true` en `.env`.
2. Revisa `storage/logs/app.log` (ahí quedan las excepciones).
3. Errores comunes:
   - **Contraseñas de cuenta ilegibles** → `APP_KEY` distinta a la local.
   - **500 al abrir** → revisa credenciales de BD en `.env` y permisos de `storage/`.
   - **El logo no carga** → confirma que subiste `public/img/logo.png` y el `.htaccess` de la raíz.
4. Corrige y vuelve a `APP_DEBUG=false`.

---

## 7) Respaldos

- Antes de cambios grandes, exporta desde phpMyAdmin o deja `MYSQLDUMP_PATH`
  configurado para el respaldo automático antes de cada importación CSV.
- Programa un respaldo periódico de la base en hPanel si está disponible.
