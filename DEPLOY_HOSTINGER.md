# Deploy en Hostinger (Ghost Store)

## 1) Preparar archivos

1. Asegura que existan estos archivos en la raiz del proyecto:
   - `index.php`
   - `.htaccess`
   - `.env` (copiado desde `.env.example`)
2. No subas `.git` ni carpetas de desarrollo.

## 2) Configurar `.env`

Usa valores de produccion:

```env
APP_ENV=production
APP_DEBUG=false
APP_RUN_MIGRATIONS=false

DB_HOST=localhost
DB_PORT=3306
DB_NAME=tu_base
DB_USER=tu_usuario
DB_PASS=tu_password
```

## 3) Base de datos

1. Crea la base de datos en hPanel.
2. Importa tu dump SQL (phpMyAdmin).
3. Verifica que `DB_NAME`, `DB_USER` y `DB_PASS` coincidan.

## 4) Publicar

1. Sube el proyecto a `public_html` (o a la carpeta del dominio).
2. Verifica permisos de escritura:
   - `storage/logs/` (escritura para logs)
3. Abre el dominio y prueba login.

## 5) Si algo falla

1. Activa temporalmente:
   - `APP_DEBUG=true`
2. Revisa `storage/logs/app.log`
3. Corrige y vuelve a:
   - `APP_DEBUG=false`
