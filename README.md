# AgroLink API — Fase 3

Backend Laravel del marketplace agropecuario. Migraciones **confirmadas contra un
`php artisan migrate` real** (Postgres 17, corrido en tu máquina el 25/sep/2026). Esta
entrega agrega Models, Policies, RBAC por permisos, el seeder del catálogo real y los
primeros endpoints: Auth, Catalog y Listings.

## Bug real encontrado y corregido (ya en las migraciones)

`unaccent()` de Postgres no es `IMMUTABLE` y, además, el nombre del diccionario debe ir
calificado con esquema al llamarse desde una función SQL que se inlinea dentro de un
índice. La función `immutable_unaccent()` (migración `2026_01_15_000000`) quedó así:

```sql
SELECT public.unaccent('public.unaccent'::regdictionary, $1)
```

Confirmado con `php artisan migrate` real, no solo con SQL suelto.

## Cómo levantarlo desde cero

```bash
composer install --no-security-blocking   # ver nota abajo
cp .env.example .env
php artisan key:generate

createdb agrolink
psql agrolink -c "CREATE EXTENSION IF NOT EXISTS postgis; CREATE EXTENSION IF NOT EXISTS pgcrypto; CREATE EXTENSION IF NOT EXISTS unaccent;"

php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
php artisan db:seed
```

`--no-security-blocking`: Composer bloquea por defecto instalar paquetes con advisories de
seguridad reportadas, incluso parches menores. Es una bandera de una sola vez, no cambia
nada permanente; para producción (Fase 8) hay que revisar esas advisories puntuales antes
de desplegar.

`php artisan db:seed` corre:
- **RolesAndPermissionsSeeder**: roles `vendedor`/`moderador`/`administrador`, pero el
  código nunca debe comprobar el nombre del rol — siempre `$user->can('permiso')`
  (Fase 1 §7, regla §20).
- **CatalogSeeder**: las mismas categorías/tipos/atributos que ya están en
  `lib/features/catalog/data/mock/mock_catalog.dart` del prototipo Flutter — ver la nota
  de desviación dentro del propio seeder: algunos atributos (`raza`, `proposito`,
  `estado_sanitario`, etc.) tienen opciones distintas según el tipo de producto, así que
  en la BD llevan clave interna sufijada (`raza_equino`, `raza_bovino`...) aunque el
  `label` que ve el usuario siga siendo el mismo ("Raza"). El catálogo global en Postgres
  no puede tener dos listas de opciones distintas bajo la misma clave.

## Endpoints de esta entrega

```
POST   /api/register
POST   /api/login
POST   /api/logout            (auth:sanctum)
GET    /api/me                (auth:sanctum)

GET    /api/categories
GET    /api/product-types/{id}/attributes

GET    /api/listings
GET    /api/listings/{id}
POST   /api/listings          (auth:sanctum)
PATCH  /api/listings/{id}     (auth:sanctum, dueño)
DELETE /api/listings/{id}     (auth:sanctum, dueño)
POST   /api/listings/{id}/publish   (auth:sanctum, dueño)
POST   /api/listings/{id}/archive   (auth:sanctum, dueño)
POST   /api/listings/{id}/media        (auth:sanctum, dueño) — sube UNA foto (multipart)
DELETE /api/listings/{id}/media/{mid}  (auth:sanctum, dueño)
```

### Fotos en desarrollo local: usa el disco `public`, no `s3`

`.env.example` trae `FILESYSTEM_DISK=s3` (lo correcto para producción), pero en tu máquina
no hay bucket real configurado. Para que subir fotos funcione en local, en tu `.env`:

```
FILESYSTEM_DISK=public
```

y corre una vez:

```bash
php artisan storage:link
```

(crea el symlink `public/storage -> storage/app/public` para que las URLs de
`ListingMediaResource` sirvan las fotos). En producción (Fase 8) se vuelve a `s3` apuntando
al bucket real — el código no cambia, solo la config.

`publish` es la acción que implementa "se publica primero, se aprueba después"
(confirmado 25/sep): pone `status=published` y calcula `expires_at`, sin tocar
`moderation_status` — eso lo hace un módulo de moderación aparte (Fase 6).

## Pendiente / no incluido aún en esta entrega

- **2FA de administradores** (Fase 1 §21): las columnas ya existen en `users`, pero la
  lógica de TOTP (generar secreto, QR, confirmar código) no está implementada todavía —
  no quise dejar una versión a medias o simulada.
- Chat/ofertas, moderación, reportes, reseñas: sus tablas y Models ya existen, pero sin
  Controllers todavía (Fase 5/6 del plan).
- Búsqueda en lenguaje natural (el parser que ya vive en el prototipo Flutter) — el
  endpoint `GET /api/listings` solo tiene filtro de texto simple por ahora.

## Estructura de este paquete

```
app/Models/            25 modelos Eloquent, uno por tabla del MVP
app/Http/Controllers/Api/   Auth, Catalog, Listings
app/Http/Requests/     FormRequests con validación server-side completa (regla §20)
app/Http/Resources/    nunca exponen ubicación exacta (Fase 1 §6)
app/Policies/           ListingPolicy (autorización por dueño, no por rol)
app/Services/           ListingService (lógica de publicar/atributos dinámicos/ubicación)
database/seeders/       CatalogSeeder + RolesAndPermissionsSeeder
database/migrations/    31 migraciones (30 propias + RBAC de spatie)
routes/api.php
```
