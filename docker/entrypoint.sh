#!/bin/sh
set -e
cd /var/www/html

# Falla temprano y claro si falta lo indispensable.
: "${APP_KEY:?Falta APP_KEY (php artisan key:generate --show)}"
: "${DB_HOST:?Falta DB_HOST}"

php artisan config:cache

# Migra y siembra roles + catálogo (ambos idempotentes). NO corre DatabaseSeeder:
# ese crea un usuario de prueba que no debe existir en producción.
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder --force
php artisan db:seed --class=CatalogSeeder --force

# Crea/promueve el administrador si defines ADMIN_EMAIL (opcional: ADMIN_NAME, ADMIN_PASSWORD).
# Sin ADMIN_PASSWORD genera una temporal y la imprime en los logs de Render. Es idempotente:
# si la cuenta ya existe solo se asegura el rol. Borra estas variables después del primer arranque.
if [ -n "${ADMIN_EMAIL:-}" ]; then
  if [ -n "${ADMIN_PASSWORD:-}" ]; then
    php artisan agrolink:crear-admin "$ADMIN_EMAIL" --nombre="${ADMIN_NAME:-Administrador}" --password="$ADMIN_PASSWORD" || true
  else
    php artisan agrolink:crear-admin "$ADMIN_EMAIL" --nombre="${ADMIN_NAME:-Administrador}" || true
  fi
fi

# Moderadores (solo moderan): MODERATOR_EMAILS="uno@correo.com,otro@correo.com". Idempotente.
if [ -n "${MODERATOR_EMAILS:-}" ]; then
  for m in $(echo "$MODERATOR_EMAILS" | tr ',' ' '); do
    php artisan agrolink:crear-admin "$m" --rol=moderador --nombre="Moderador" || true
  done
fi

# Revisión previa a producción en los logs (no detiene el arranque).
php artisan agrolink:preflight || true

# Con disco local (sin S3) hace falta el enlace público; con S3 no estorba.
php artisan storage:link 2>/dev/null || true

exec "$@"
