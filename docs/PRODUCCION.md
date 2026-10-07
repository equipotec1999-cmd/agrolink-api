# Producción (Fase 8)

## 1. Rotar las claves expuestas (hacer YA)
Estas claves se pegaron en chats/capturas durante el desarrollo; considéralas comprometidas.

1. **APP_KEY** — en tu PC: `php artisan key:generate --show` → pégala en Render › Environment.
   *Efecto:* se cierran todas las sesiones y los secretos 2FA guardados dejan de descifrarse
   (los administradores deberán volver a activar 2FA: `UPDATE usuarios SET secreto_dos_factores=NULL,
   codigos_recuperacion_dos_factores=NULL, dos_factores_confirmado_en=NULL;`). Hazlo antes de tener usuarios reales.
2. **Contraseña de la BD** — Supabase › Project Settings › Database › Reset database password →
   actualiza `DB_PASSWORD` en Render.
3. **Llaves S3** — Supabase › Storage › S3 Connection: borra la Access Key vieja, crea otra →
   actualiza `AWS_ACCESS_KEY_ID` y `AWS_SECRET_ACCESS_KEY`.
4. **Token de GitHub** que se haya compartido: GitHub › Settings › Developer settings › revócalo.
5. Guarda las claves nuevas solo en el dashboard de Render / un gestor de contraseñas. Nunca en chats ni en git.

## 2. Bucket privado para documentos de verificación
1. Supabase › Storage › New bucket `verificaciones`, **Public: desactivado**.
2. Render › Environment: `VERIFICATION_BUCKET=verificaciones`.
3. Los documentos nuevos se guardan ahí; los anteriores siguen leyéndose del disco donde se subieron
   (columna `disco`). La API es la única que los entrega, con sesión de moderación y registro en bitácora.

## 3. Crear el administrador
En Render › Shell (o local apuntando a la BD):
```
php artisan agrolink:crear-admin tu-correo@dominio.com --nombre="Tu Nombre"
```
Imprime una contraseña temporal. Al entrar en la app se te pedirá activar la verificación en dos pasos.
Moderadores: añade `--rol=moderador`.

## 4. Revisión automática
```
php artisan agrolink:preflight
```
Revisa APP_ENV/DEBUG/KEY/URL https, BD con SSL, S3, bucket privado, que no exista el usuario de prueba y
que todos los administradores tengan 2FA. Termina con error si algo grave falla. Córrelo en el Shell de Render
antes de lanzar y después de cada cambio de configuración.

## 5. Lista previa al lanzamiento
- [ ] Claves rotadas (sección 1) y `agrolink:preflight` en verde
- [ ] `APP_DEBUG=false`, `APP_ENV=production`, `LOG_LEVEL=warning`
- [ ] Plan de Render **de pago** (el gratuito se duerme y la primera petición tarda ~1 min; los avisos push llegan tarde)
- [ ] Supabase: copias de seguridad activas (plan Pro: respaldos diarios) y RLS no necesaria (solo la API accede)
- [ ] Credenciales de Firebase (push) cargadas como variable `FIREBASE_CREDENTIALS` en Render
- [ ] Al menos un administrador con 2FA y un moderador
- [ ] Catálogo y reglas de cumplimiento revisados (Reglas de cumplimiento en la app)
- [ ] Aviso de privacidad y términos publicados (se manejan INE y ubicación)
- [ ] App Android: firmar el APK/AAB de release (`flutter build appbundle --dart-define=API_BASE_URL=...`),
      icono y nombre finales, `applicationId` definitivo
- [ ] Prueba de punta a punta en un teléfono real: registro, publicar, moderar, chat, oferta, verificación
- [ ] CI en verde en ambos repos (tests API y Flutter)
- [ ] Plan de respaldo: quién atiende reportes y a qué hora responde moderación

## 6. Operación
- **Logs:** Render › Logs (van a stderr). Errores 500 aparecen ahí con el detalle.
- **Auditoría:** tabla `bitacora_auditoria` (aprobaciones, rechazos, vistas de documentos, cambios de reglas).
- **Límites de peticiones:** 120/min por IP en toda la API, y más estrictos en login, registro, 2FA, ofertas, reportes y verificación.
- **Despliegue:** cada `git push` a `main` redepliega y migra solo.
