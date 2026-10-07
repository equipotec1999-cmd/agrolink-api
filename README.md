# AgroLink API

Backend del marketplace agropecuario **AgroLink** para México. Laravel 11 + PostgreSQL con PostGIS + Supabase Storage, desplegado en Render con Docker. La app móvil Flutter vive en [`agrolink`](https://github.com/equipotec1999-cmd/agrolink).

**Estado:** Fase 8 (producción). Las fases anteriores (arquitectura, base de datos, backend, Flutter, chat/ofertas, moderación, pruebas) están cerradas. CI corre con GitHub Actions en cada *push*.

## Qué hace

- **Autenticación** con Sanctum (tokens Bearer para móvil, sesiones para web).
- **Verificación en dos pasos (2FA)** obligatoria para administradores. TOTP compatible con Google Authenticator y Authy, más códigos de respaldo.
- **Catálogo** jerárquico de categorías, tipos de producto y atributos dinámicos por tipo.
- **Publicaciones** con fotos, documentos, ubicación aproximada (±1 km, PostGIS) y moderación.
- **Chat y ofertas**: conversaciones por publicación, ofertas y contraofertas, aceptación crea una operación.
- **Operaciones y reseñas**: ciclo completo compra/venta con eventos y calificación por aspecto.
- **Moderación**: cola de publicaciones, reportes, verificación de vendedores (INE + comprobante) y reglas de cumplimiento.
- **Notificaciones** por push (Firebase) y en la app, con bitácora de auditoría.
- **Búsquedas guardadas** y favoritos.

## Pila

- PHP 8.3, Laravel 11, PHPUnit.
- PostgreSQL 16 + PostGIS 3 (Supabase en producción, Postgres local o Docker en desarrollo).
- Supabase Storage (compatible S3) para fotos y documentos. Un bucket público para fotos y otro **privado** para documentos de verificación.
- Spatie Laravel Permission para permisos y roles.
- Firebase Cloud Messaging para push.
- Render (contenedor Docker) para el deploy.

## Levantar en tu máquina

```bash
composer install
cp .env.example .env
php artisan key:generate

createdb agrolink
psql agrolink -c "CREATE EXTENSION IF NOT EXISTS postgis; CREATE EXTENSION IF NOT EXISTS pgcrypto; CREATE EXTENSION IF NOT EXISTS unaccent;"

php artisan migrate --seed
php artisan serve --host=0.0.0.0
```

El seeder `DatabaseSeeder` crea el catálogo, los roles y un usuario de prueba `test@example.com` (solo en desarrollo).

## Pruebas

```bash
php artisan test
```

67 pruebas que cubren autenticación, 2FA, publicaciones, moderación, chat, ofertas, reglas de cumplimiento, búsquedas guardadas, verificación de vendedores y utilidades de producción. CI corre esto mismo en cada *push* contra PostgreSQL con PostGIS.

## Base de datos

- Las tablas y columnas del dominio están en **español**: `usuarios`, `publicaciones`, `conversaciones`, `ofertas`, `operaciones`, `estatus`, `creado_en`, etcétera. Las tablas técnicas (`cache`, `jobs`, `sessions`, `personal_access_tokens`, `roles`, `permissions`) se quedan en inglés porque son del framework y sus paquetes.
- Los **valores de estatus también están en español**: `borrador`, `en_revision`, `publicada`, `rechazada`, `pendiente`, `aprobada`, `enviada`, `aceptada`, `contraoferta`, `retirada`, `vencida`, `abierto`, `resuelto`, `foto`, `ocultar_publicacion`, `descartar`.
- Documentación completa en [`docs/DICCIONARIO_DATOS.md`](docs/DICCIONARIO_DATOS.md). El esquema SQL y el catálogo inicial están en [`docs/schema.sql`](docs/schema.sql) y [`docs/semillas.sql`](docs/semillas.sql) (los regenera un workflow al cambiar las migraciones).

## Producción

Guía completa en [`docs/PRODUCCION.md`](docs/PRODUCCION.md). Resumen:

1. **Rotar claves** `APP_KEY`, contraseña de la BD y llaves S3.
2. **Bucket privado** `verificaciones` para INE y comprobantes.
3. **Crear administrador** con `ADMIN_EMAIL` en Render o `php artisan agrolink:crear-admin`.
4. **Revisar configuración** con `php artisan agrolink:preflight` (sale en los logs al arrancar).
5. **Lista de revisión** antes del lanzamiento (plan de pago en Render, Firebase, aviso de privacidad, firma del APK...).

## Endpoints

Todos están bajo `/api`. Los principales:

| Camino | Qué hace |
|---|---|
| `POST /register`, `POST /login` | Registro y login (devuelven token Bearer) |
| `POST /two-factor/setup`, `/confirm`, `/complete` | Activar y usar 2FA |
| `GET /categories` | Catálogo (categorías, tipos, atributos) |
| `GET /listings`, `GET /listings/{id}` | Feed, búsqueda y detalle |
| `POST /listings` | Crear borrador |
| `POST /listings/{id}/media` | Subir foto |
| `POST /listings/{id}/publish` | Enviar a revisión |
| `GET /conversations`, `POST /conversations` | Chat |
| `POST /conversations/{id}/offers` | Hacer oferta |
| `POST /offers/{id}/accept`, `/reject`, `/counter` | Responder oferta |
| `GET /me`, `PATCH /me`, `POST /me/password` | Mi cuenta |
| `GET /me/stats` | Mis cifras (operaciones, calificación) |
| `GET /verification`, `POST /verification` | Verificación de vendedor |
| `GET /moderation/queue`, `/reports`, `/verifications` | Cola de moderación |
| `GET /saved-searches` | Búsquedas guardadas |
| `GET /admin/compliance-rules` | Reglas de cumplimiento (solo admin) |

Las claves JSON de la API siguen en inglés (`title`, `price`, `status`...) para que el contrato con la app sea estable; los valores de `status` ahora llegan en español (`publicada`, `enviada`, etc.).

## Despliegue

El Dockerfile y el workflow de Render están en el repositorio. Cada `git push` a `main` redepliega automáticamente; el contenedor migra la base, siembra roles y catálogo, crea el administrador si pusiste `ADMIN_EMAIL`, y corre `agrolink:preflight` dejando el resultado en los logs.
