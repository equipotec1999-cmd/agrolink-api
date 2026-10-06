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

# Con disco local (sin S3) hace falta el enlace público; con S3 no estorba.
php artisan storage:link 2>/dev/null || true

exec "$@"
