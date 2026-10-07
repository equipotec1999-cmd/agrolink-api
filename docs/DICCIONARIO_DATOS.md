# Diccionario de datos — AgroLink

Base de datos PostgreSQL + PostGIS. Los nombres de **tablas y columnas del dominio están en español**. La API (JSON) conserva sus claves en inglés; el mapeo se hace en los *Resources*, *Requests* y servicios de Laravel.

Se quedan en inglés las tablas técnicas de Laravel y de paquetes: `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `sessions`, `migrations`, `personal_access_tokens` (Sanctum) y `permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions` (spatie/laravel-permission). Los valores guardados de los campos tipo lista (por ejemplo `estatus = published`) también conservan su código en inglés porque son parte del contrato con la API.

Fechas estándar: `creado_en`, `actualizado_en` y, donde aplica, `eliminado_en` (borrado lógico).


## `atributos`

Campos dinámicos que se pueden pedir al publicar (raza, peso, variedad...). *(antes: `attributes`)*

| Columna | Tipo | Nulo | Antes |
|---|---|---|---|
| `id` | entero grande | no | — |
| `clave` | texto | no | `attr_key` |
| `etiqueta` | texto | no | `label` |
| `tipo_dato` | texto | no | `data_type` |
| `unidad` | texto | sí | `unit` |
| `grupo` | texto | no | `attr_group` |
| `creado_en` | fecha y hora | sí | `created_at` |
| `actualizado_en` | fecha y hora | sí | `updated_at` |

## `bitacora_auditoria`

Registro de acciones para auditoría. *(antes: `audit_logs`)*

| Columna | Tipo | Nulo | Antes |
|---|---|---|---|
| `id` | entero grande | no | — |
| `usuario_id` | entero grande | sí | `user_id` |
| `accion` | texto | no | `action` |
| `auditable_tipo` | texto | sí | `auditable_type` |
| `auditable_id` | entero grande | sí | — |
| `cambios` | JSON | sí | `changes` |
| `direccion_ip` | texto | sí | `ip_address` |
| `agente_usuario` | texto | sí | `user_agent` |
| `creado_en` | fecha y hora | sí | `created_at` |

## `busquedas_guardadas`

Búsquedas guardadas con avisos opcionales. *(antes: `saved_searches`)*

| Columna | Tipo | Nulo | Antes |
|---|---|---|---|
| `id` | entero grande | no | — |
| `usuario_id` | entero grande | no | `user_id` |
| `nombre` | texto | no | `name` |
| `consulta` | JSON | no | `query` |
| `avisar_coincidencia` | sí/no | no | `notify_on_match` |
| `avisar_cambio_precio` | sí/no | no | `notify_on_price_change` |
| `ultimo_aviso_en` | fecha y hora | sí | `last_notified_at` |
| `creado_en` | fecha y hora | sí | `created_at` |
| `actualizado_en` | fecha y hora | sí | `updated_at` |

## `categorias`

Categorías raíz del catálogo (animales, apicultura, agricultura). *(antes: `categories`)*

| Columna | Tipo | Nulo | Antes |
|---|---|---|---|
| `id` | entero grande | no | — |
| `categoria_padre_id` | entero grande | sí | `parent_id` |
| `nombre` | texto | no | `name` |
| `slug` | texto | no | — |
| `clave_color` | texto | no | `color_key` |
| `icono` | texto | sí | `icon` |
| `orden` | entero pequeño | no | `sort_order` |
| `activo` | sí/no | no | `is_active` |
| `creado_en` | fecha y hora | sí | `created_at` |
| `actualizado_en` | fecha y hora | sí | `updated_at` |

## `conversaciones`

Conversaciones entre comprador y vendedor sobre una publicación. *(antes: `conversations`)*

| Columna | Tipo | Nulo | Antes |
|---|---|---|---|
| `id` | entero grande | no | — |
| `publicacion_id` | entero grande | no | `listing_id` |
| `comprador_id` | entero grande | no | `buyer_id` |
| `vendedor_id` | entero grande | no | `seller_id` |
| `ultimo_mensaje_en` | fecha y hora | sí | `last_message_at` |
| `creado_en` | fecha y hora | sí | `created_at` |
| `actualizado_en` | fecha y hora | sí | `updated_at` |

## `documentos_publicacion`

Documentos de respaldo de una publicación (certificados, guías...). *(antes: `listing_documents`)*

| Columna | Tipo | Nulo | Antes |
|---|---|---|---|
| `id` | entero grande | no | — |
| `publicacion_id` | entero grande | no | `listing_id` |
| `nombre` | texto | no | `name` |
| `ruta_almacenamiento` | texto | no | `storage_path` |
| `estatus` | texto | no | `status` |
| `nota` | texto | sí | `note` |
| `revisado_por` | entero grande | sí | `reviewed_by` |
| `revisado_en` | fecha y hora | sí | `reviewed_at` |
| `creado_en` | fecha y hora | sí | `created_at` |
| `actualizado_en` | fecha y hora | sí | `updated_at` |

## `eventos_operacion`

Historial de cambios de estatus de una operación. *(antes: `operation_events`)*

| Columna | Tipo | Nulo | Antes |
|---|---|---|---|
| `id` | entero grande | no | — |
| `operacion_id` | entero grande | no | `operation_id` |
| `estatus_anterior` | texto | sí | `from_status` |
| `estatus_nuevo` | texto | no | `to_status` |
| `actor_id` | entero grande | sí | — |
| `nota` | texto | sí | `note` |
| `creado_en` | fecha y hora | sí | `created_at` |

## `favoritos`

Publicaciones guardadas por cada usuario. *(antes: `favorites`)*

| Columna | Tipo | Nulo | Antes |
|---|---|---|---|
| `id` | entero grande | no | — |
| `usuario_id` | entero grande | no | `user_id` |
| `publicacion_id` | entero grande | no | `listing_id` |
| `creado_en` | fecha y hora | sí | `created_at` |

## `medios_publicacion`

Fotos y videos de una publicación (solo la ruta; el archivo vive en el almacenamiento). *(antes: `listing_media`)*

| Columna | Tipo | Nulo | Antes |
|---|---|---|---|
| `id` | entero grande | no | — |
| `publicacion_id` | entero grande | no | `listing_id` |
| `tipo` | texto | no | `type` |
| `ruta_almacenamiento` | texto | no | `storage_path` |
| `posicion` | entero pequeño | no | `position` |
| `ancho` | entero pequeño | sí | `width` |
| `alto` | entero pequeño | sí | `height` |
| `duracion_segundos` | entero pequeño | sí | `duration_seconds` |
| `creado_en` | fecha y hora | sí | `created_at` |
| `actualizado_en` | fecha y hora | sí | `updated_at` |

## `mensajes`

Mensajes de una conversación (texto u oferta). *(antes: `messages`)*

| Columna | Tipo | Nulo | Antes |
|---|---|---|---|
| `id` | entero grande | no | — |
| `conversacion_id` | entero grande | no | `conversation_id` |
| `remitente_id` | entero grande | no | `sender_id` |
| `oferta_id` | entero grande | sí | `offer_id` |
| `cuerpo` | texto largo | sí | `body` |
| `leido_en` | fecha y hora | sí | `read_at` |
| `creado_en` | fecha y hora | sí | `created_at` |
| `actualizado_en` | fecha y hora | sí | `updated_at` |

## `notificaciones`

Notificaciones para los usuarios. *(antes: `notifications`)*

| Columna | Tipo | Nulo | Antes |
|---|---|---|---|
| `id` | uuid | no | — |
| `tipo` | texto | no | `type` |
| `notificable_tipo` | texto | no | `notifiable_type` |
| `notificable_id` | entero grande | no | `notifiable_id` |
| `datos` | JSON | no | `data` |
| `leido_en` | fecha y hora | sí | `read_at` |
| `creado_en` | fecha y hora | sí | `created_at` |
| `actualizado_en` | fecha y hora | sí | `updated_at` |

## `ofertas`

Ofertas hechas dentro de una conversación. *(antes: `offers`)*

| Columna | Tipo | Nulo | Antes |
|---|---|---|---|
| `id` | entero grande | no | — |
| `conversacion_id` | entero grande | no | `conversation_id` |
| `remitente_id` | entero grande | no | `sender_id` |
| `monto` | decimal | no | `amount` |
| `cantidad` | decimal | no | `quantity` |
| `estatus` | texto | no | `status` |
| `vence_en` | fecha y hora | sí | `expires_at` |
| `creado_en` | fecha y hora | sí | `created_at` |
| `actualizado_en` | fecha y hora | sí | `updated_at` |

## `opciones_atributo`

Opciones de los atributos de tipo lista. *(antes: `attribute_options`)*

| Columna | Tipo | Nulo | Antes |
|---|---|---|---|
| `id` | entero grande | no | — |
| `atributo_id` | entero grande | no | `attribute_id` |
| `valor` | texto | no | `value` |
| `orden` | entero pequeño | no | `sort_order` |
| `creado_en` | fecha y hora | sí | `created_at` |
| `actualizado_en` | fecha y hora | sí | `updated_at` |

## `operaciones`

Operaciones de compra-venta que nacen de una oferta aceptada. *(antes: `operations`)*

| Columna | Tipo | Nulo | Antes |
|---|---|---|---|
| `id` | entero grande | no | — |
| `oferta_id` | entero grande | no | `offer_id` |
| `publicacion_id` | entero grande | no | `listing_id` |
| `comprador_id` | entero grande | no | `buyer_id` |
| `vendedor_id` | entero grande | no | `seller_id` |
| `monto` | decimal | no | `amount` |
| `cantidad` | decimal | no | `quantity` |
| `estatus` | texto | no | `status` |
| `creado_en` | fecha y hora | sí | `created_at` |
| `actualizado_en` | fecha y hora | sí | `updated_at` |

## `perfiles`

Datos personales opcionales del usuario (relación 1 a 1). *(antes: `profiles`)*

| Columna | Tipo | Nulo | Antes |
|---|---|---|---|
| `usuario_id` | entero grande | no | `user_id` |
| `ruta_avatar` | texto | sí | `avatar_path` |
| `biografia` | texto largo | sí | `bio` |
| `estado` | texto | sí | `state` |
| `municipio` | texto | sí | `municipality` |
| `creado_en` | fecha y hora | sí | `created_at` |
| `actualizado_en` | fecha y hora | sí | `updated_at` |

## `perfiles_vendedor`

Reputación y verificación del usuario como vendedor (1 a 1). *(antes: `seller_profiles`)*

| Columna | Tipo | Nulo | Antes |
|---|---|---|---|
| `usuario_id` | entero grande | no | `user_id` |
| `nombre_negocio` | texto | sí | `business_name` |
| `verificado` | sí/no | no | `is_verified` |
| `verificado_en` | fecha y hora | sí | `verified_at` |
| `operaciones_completadas` | entero | no | `completed_operations` |
| `operaciones_canceladas` | entero | no | `cancelled_operations` |
| `calificacion_exactitud` | decimal | sí | `rating_accuracy` |
| `calificacion_cumplimiento` | decimal | sí | `rating_fulfillment` |
| `calificacion_comunicacion` | decimal | sí | `rating_communication` |
| `minutos_respuesta_promedio` | entero | sí | `avg_response_minutes` |
| `creado_en` | fecha y hora | sí | `created_at` |
| `actualizado_en` | fecha y hora | sí | `updated_at` |

## `predios`

Fincas o ranchos del usuario, con ubicación. *(antes: `properties`)*

| Columna | Tipo | Nulo | Antes |
|---|---|---|---|
| `id` | entero grande | no | — |
| `usuario_id` | entero grande | no | `user_id` |
| `nombre` | texto | no | `name` |
| `estado` | texto | no | `state` |
| `municipio` | texto | no | `municipality` |
| `codigo_postal` | texto | sí | `postal_code` |
| `ubicacion_exacta` | punto geográfico | sí | `exact_location` |
| `ubicacion_aproximada` | punto geográfico | sí | `approx_location` |
| `es_predeterminado` | sí/no | no | `is_default` |
| `creado_en` | fecha y hora | sí | `created_at` |
| `actualizado_en` | fecha y hora | sí | `updated_at` |

## `publicaciones`

Anuncios publicados por los vendedores. *(antes: `listings`)*

| Columna | Tipo | Nulo | Antes |
|---|---|---|---|
| `id` | entero grande | no | — |
| `usuario_id` | entero grande | no | `user_id` |
| `tipo_producto_id` | entero grande | no | `product_type_id` |
| `predio_id` | entero grande | sí | `property_id` |
| `titulo` | texto | no | `title` |
| `slug` | texto | no | — |
| `descripcion` | texto largo | sí | `description` |
| `precio` | decimal | sí | `price` |
| `tipo_precio` | texto | no | `price_type` |
| `moneda` | texto fijo | no | `currency` |
| `cantidad` | decimal | no | `quantity` |
| `unidad` | texto | no | `unit` |
| `modalidad_venta` | texto | no | `sale_mode` |
| `negociable` | sí/no | no | `negotiable` |
| `estatus` | texto | no | `status` |
| `estatus_moderacion` | texto | no | `moderation_status` |
| `atributos_cache` | JSON | no | `attributes_cache` |
| `publicado_en` | fecha y hora | sí | `published_at` |
| `vence_en` | fecha y hora | sí | `expires_at` |
| `creado_en` | fecha y hora | sí | `created_at` |
| `actualizado_en` | fecha y hora | sí | `updated_at` |
| `eliminado_en` | fecha y hora | sí | `deleted_at` |

## `reglas_cumplimiento`

Reglas normativas por tipo de producto o categoría (documentos requeridos, fuentes). *(antes: `compliance_rules`)*

| Columna | Tipo | Nulo | Antes |
|---|---|---|---|
| `id` | entero grande | no | — |
| `tipo_producto_id` | entero grande | sí | `product_type_id` |
| `categoria_id` | entero grande | sí | `category_id` |
| `titulo` | texto | no | `title` |
| `descripcion` | texto largo | sí | `description` |
| `documento_sugerido` | sí/no | no | `document_suggested` |
| `documento_requerido` | sí/no | no | `document_required` |
| `nombre_fuente` | texto | no | `source_name` |
| `url_fuente` | texto | sí | `source_url` |
| `vigente_desde` | fecha | sí | `effective_from` |
| `vigente_hasta` | fecha | sí | `effective_to` |
| `creado_en` | fecha y hora | sí | `created_at` |
| `actualizado_en` | fecha y hora | sí | `updated_at` |

## `reportes`

Denuncias sobre publicaciones u otros elementos. *(antes: `reports`)*

| Columna | Tipo | Nulo | Antes |
|---|---|---|---|
| `id` | entero grande | no | — |
| `reportante_id` | entero grande | no | `reporter_id` |
| `reportable_tipo` | texto | no | `reportable_type` |
| `reportable_id` | entero grande | no | — |
| `motivo` | texto | no | `reason` |
| `descripcion` | texto | sí | `description` |
| `estatus` | texto | no | `status` |
| `resuelto_por` | entero grande | sí | `resolved_by` |
| `resuelto_en` | fecha y hora | sí | `resolved_at` |
| `nota_resolucion` | texto | sí | `resolution_note` |
| `creado_en` | fecha y hora | sí | `created_at` |
| `actualizado_en` | fecha y hora | sí | `updated_at` |

## `resenas`

Calificaciones entre comprador y vendedor al cerrar una operación. *(antes: `reviews`)*

| Columna | Tipo | Nulo | Antes |
|---|---|---|---|
| `id` | entero grande | no | — |
| `operacion_id` | entero grande | no | `operation_id` |
| `autor_id` | entero grande | no | `reviewer_id` |
| `evaluado_id` | entero grande | no | `reviewee_id` |
| `exactitud` | entero pequeño | sí | `accuracy` |
| `cumplimiento` | entero pequeño | sí | `fulfillment` |
| `comunicacion` | entero pequeño | sí | `communication` |
| `pago` | entero pequeño | sí | `payment` |
| `recepcion` | entero pequeño | sí | `reception` |
| `comentario` | texto | sí | `comment` |
| `creado_en` | fecha y hora | sí | `created_at` |
| `actualizado_en` | fecha y hora | sí | `updated_at` |

## `tipo_producto_atributos`

Qué atributos aplican a cada tipo de producto y sus reglas (obligatorio, rango...). *(antes: `product_type_attributes`)*

| Columna | Tipo | Nulo | Antes |
|---|---|---|---|
| `id` | entero grande | no | — |
| `tipo_producto_id` | entero grande | no | `product_type_id` |
| `atributo_id` | entero grande | no | `attribute_id` |
| `es_obligatorio` | sí/no | no | `is_required` |
| `es_filtrable` | sí/no | no | `is_filterable` |
| `orden` | entero pequeño | no | `sort_order` |
| `valor_minimo` | decimal | sí | `min_value` |
| `valor_maximo` | decimal | sí | `max_value` |
| `creado_en` | fecha y hora | sí | `created_at` |
| `actualizado_en` | fecha y hora | sí | `updated_at` |

## `tipos_producto`

Tipos de producto dentro de cada categoría (bovinos, miel, chile...). *(antes: `product_types`)*

| Columna | Tipo | Nulo | Antes |
|---|---|---|---|
| `id` | entero grande | no | — |
| `categoria_id` | entero grande | no | `category_id` |
| `nombre` | texto | no | `name` |
| `slug` | texto | no | — |
| `icono` | texto | sí | `icon` |
| `dias_vigencia_predeterminados` | entero pequeño | no | `default_expiry_days` |
| `orden` | entero pequeño | no | `sort_order` |
| `activo` | sí/no | no | `is_active` |
| `creado_en` | fecha y hora | sí | `created_at` |
| `actualizado_en` | fecha y hora | sí | `updated_at` |

## `ubicaciones_publicacion`

Ubicación exacta (privada) y aproximada (pública) de una publicación. *(antes: `listing_locations`)*

| Columna | Tipo | Nulo | Antes |
|---|---|---|---|
| `publicacion_id` | entero grande | no | `listing_id` |
| `predio_id` | entero grande | sí | `property_id` |
| `estado` | texto | no | `state` |
| `municipio` | texto | no | `municipality` |
| `codigo_postal` | texto | sí | `postal_code` |
| `ubicacion_exacta` | punto geográfico | no | `exact_location` |
| `ubicacion_aproximada` | punto geográfico | no | `approx_location` |
| `creado_en` | fecha y hora | sí | `created_at` |
| `actualizado_en` | fecha y hora | sí | `updated_at` |

## `usuarios`

Cuentas de usuario (compradores y vendedores). *(antes: `users`)*

| Columna | Tipo | Nulo | Antes |
|---|---|---|---|
| `id` | entero grande | no | — |
| `nombre` | texto | no | `name` |
| `correo` | texto | no | `email` |
| `telefono` | texto | sí | `phone` |
| `correo_verificado_en` | fecha y hora | sí | `email_verified_at` |
| `contrasena` | texto | no | `password` |
| `secreto_dos_factores` | texto largo | sí | `two_factor_secret` |
| `codigos_recuperacion_dos_factores` | texto largo | sí | `two_factor_recovery_codes` |
| `dos_factores_confirmado_en` | fecha y hora | sí | `two_factor_confirmed_at` |
| `token_recordar` | texto | sí | `remember_token` |
| `creado_en` | fecha y hora | sí | `created_at` |
| `actualizado_en` | fecha y hora | sí | `updated_at` |
| `eliminado_en` | fecha y hora | sí | `deleted_at` |

## `valores_atributo_publicacion`

Valor de cada atributo dinámico capturado en una publicación. *(antes: `listing_attribute_values`)*

| Columna | Tipo | Nulo | Antes |
|---|---|---|---|
| `id` | entero grande | no | — |
| `publicacion_id` | entero grande | no | `listing_id` |
| `atributo_id` | entero grande | no | `attribute_id` |
| `opcion_id` | entero grande | sí | `option_id` |
| `valor_texto` | texto largo | sí | `value_text` |
| `valor_numero` | decimal | sí | `value_number` |
| `valor_booleano` | sí/no | sí | `value_bool` |
| `valor_fecha` | fecha | sí | `value_date` |
| `nivel_verificacion` | texto | no | `verification_level` |
| `creado_en` | fecha y hora | sí | `created_at` |
| `actualizado_en` | fecha y hora | sí | `updated_at` |

## `vendedores_seguidos`

Vendedores que sigue cada usuario. *(antes: `followed_sellers`)*

| Columna | Tipo | Nulo | Antes |
|---|---|---|---|
| `id` | entero grande | no | — |
| `usuario_id` | entero grande | no | `user_id` |
| `vendedor_id` | entero grande | no | `seller_id` |
| `creado_en` | fecha y hora | sí | `created_at` |

## `tokens_dispositivo`

Token de notificaciones push (Firebase) de cada celular con sesión. *(tabla nueva)*

| Columna | Tipo | Nulo | Antes |
|---|---|---|---|
| `id` | entero grande | no | — |
| `usuario_id` | entero grande | no | — |
| `token` | texto largo (único) | no | — |
| `plataforma` | texto | no | — |
| `ultimo_uso_en` | fecha y hora | sí | — |
| `creado_en` | fecha y hora | sí | — |
| `actualizado_en` | fecha y hora | sí | — |

## Columnas agregadas en la Fase 6

| Tabla | Columna | Tipo | Para qué |
|---|---|---|---|
| `publicaciones` | `motivo_moderacion` | texto (300), nulo | Motivo del último rechazo/suspensión; lo ve el dueño. |
| `usuarios` | `dos_factores_ultimo_paso` | entero grande, nulo | Último intervalo TOTP aceptado (evita reusar un código). |
