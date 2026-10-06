# Despliegue: Supabase (BD + fotos) + Render (API)

## 1. Supabase
1. Crear proyecto en supabase.com (guarda la contraseña de la BD).
2. **SQL Editor**: `create extension if not exists postgis with schema extensions;`
   (pgcrypto y unaccent los habilita la migración; Supabase ya los trae).
3. **Connect → Session pooler**: copia host (`aws-0-<región>.pooler.supabase.com`),
   puerto `5432` y usuario (`postgres.<ref>`). Usa el *session pooler* porque Render
   no tiene IPv6 (la conexión directa de Supabase sí lo requiere) y el *transaction
   pooler* (6543) no es compatible con PDO.
4. **Storage → New bucket** `listings`, marcado **Public**.
   **Storage → S3 Connection**: crea Access Key y anota endpoint y región.

## 2. Render
1. Sube este repo a GitHub (ya está). Render → New → Blueprint → `agrolink-api`.
2. Variables (dashboard):
   - `APP_KEY`: corre `php artisan key:generate --show` en tu PC.
   - `APP_URL`: `https://agrolink-api.onrender.com` (o el que te asigne).
   - `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD`: del session pooler.
   - `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`,
     `AWS_ENDPOINT` (`https://<ref>.storage.supabase.co/storage/v1/s3`),
     `AWS_URL` (`https://<ref>.supabase.co/storage/v1/object/public/listings`).
3. Al arrancar, el contenedor migra y siembra roles + catálogo solo.
4. Prueba: `https://<tu-servicio>.onrender.com/api/categories`.

## 3. App Flutter
`flutter run --dart-define=API_BASE_URL=https://<tu-servicio>.onrender.com/api`

## Notas
- Plan free de Render: se duerme tras ~15 min sin tráfico; la primera petición tarda ~1 min.
- Corre `composer install` en tu PC y haz commit de `composer.lock` (no existe aún).
- Usuarios admin/moderador: créalos después con `php artisan tinker` en el Shell de Render o localmente apuntando a la BD.
