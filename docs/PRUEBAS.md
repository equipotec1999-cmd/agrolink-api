# Pruebas automáticas (Fase 7)

## Dónde corren
- **GitHub Actions** en cada `push` a `main` y en cada pull request:
  - API: `.github/workflows/tests.yml` (PHP 8.3 + PostgreSQL con PostGIS).
  - App: `.github/workflows/flutter.yml` (`flutter analyze` + `flutter test`).
- Si algo falla, el resumen del error queda como anotación del job en la pestaña *Actions*.

## Qué cubren (API, 55 pruebas)
- Cuentas: registro, login, sesión, cambio de contraseña (cierra otras sesiones), editar perfil.
- Verificación en dos pasos: activar, token pendiente, códigos TOTP y de recuperación (un solo uso), cuentas administrativas obligadas.
- Publicaciones: crear (con ubicación aproximada, atributos obligatorios), publicar, feed, permisos del dueño, mis publicaciones.
- Moderación: permisos por *permiso* (no por rol), cola, rechazar/aprobar, reenviar, reportes, suspender.
- Chat y ofertas: conversación idempotente, privacidad, contraoferta, aceptar (crea la operación con el total), rechazar/cancelar.
- Reglas de cumplimiento (admin + 2FA + bitácora), búsquedas guardadas, favoritos, textos de notificación.

## Correrlas en tu computadora (API)
Las pruebas **borran y recrean** su base, por eso usan una propia (`agrolink_test`) y se niegan a correr contra cualquier base cuyo nombre no termine en `_test`.

```bash
# PostgreSQL con PostGIS local (ejemplo con Docker)
docker run -d --name agrolink-pg -e POSTGRES_PASSWORD=postgres -e POSTGRES_DB=agrolink_test -p 5432:5432 postgis/postgis:16-3.4

cd ~/Descargas/agrolink-api
composer install
DB_USERNAME=postgres DB_PASSWORD=postgres vendor/bin/phpunit
```

## Correrlas (app)
```bash
cd ~/Descargas/agrolink
flutter analyze
flutter test
```
