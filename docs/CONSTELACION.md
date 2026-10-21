# Capa analítica tipo constelación — AgroLink

Esquema **constelación** (*galaxy schema*): varias tablas de **hechos** que comparten las mismas tablas de **dimensiones**.

## Decisión de diseño (léela primero)

La app **sigue escribiendo en las tablas transaccionales** (`publicaciones`, `ofertas`, `conversaciones`, `operaciones`…). La constelación es una capa **derivada** que se llena desde ellas.

¿Por qué no reemplazar todo por dimensiones y hechos? Porque un esquema dimensional está hecho para **leer y agregar** (promedios, conteos, tendencias), no para escribir: un mensaje de chat o una oferta tendrían que actualizar varias tablas desnormalizadas y la app iría **más lenta**, no más rápida. Así se obtiene lo mejor de cada lado: escrituras simples y consistentes + consultas analíticas baratas.

## Diagrama

```
                         dim_tiempo
                             │
   dim_usuario ──┐           │           ┌── dim_estatus
                 │     ┌─────┴─────┐     │
   dim_producto ─┼────►│  HECHOS   │◄────┤
                 │     └───────────┘     │
   dim_ubicacion ┘                       └── (degeneradas: tipo_precio, motivo…)

 HECHOS:  hecho_publicaciones · hecho_ofertas · hecho_operaciones · hecho_reportes
```

Es una *constelación* porque los cuatro hechos comparten dimensiones conformadas: una oferta y una publicación del mismo producto apuntan al **mismo** registro de `dim_producto`.

## Dimensiones

| Tabla | Clave sustituta | Clave natural | Contenido |
|---|---|---|---|
| `dim_tiempo` | `fecha_id` (AAAAMMDD) | `fecha` | año, trimestre, mes, nombre del mes, semana, día, día de semana, fin de semana |
| `dim_usuario` | `usuario_sk` | `usuario_id` | vendedor verificado, operaciones completadas, calificación promedio, año de registro |
| `dim_producto` | `producto_sk` | `tipo_producto_id` | tipo, categoría |
| `dim_ubicacion` | `ubicacion_sk` | `(estado, municipio)` | estado, municipio (`Sin definir` si la publicación no tiene ubicación) |
| `dim_estatus` | `estatus_sk` | `(entidad, codigo)` | estatus de publicación, oferta, operación y reporte |

**Privacidad:** ninguna dimensión guarda nombre, correo ni teléfono. Solo agregados salen por la API.

## Hechos

| Tabla | Grano (una fila por…) | Medidas |
|---|---|---|
| `hecho_publicaciones` | publicación | `precio`, `cantidad`, `precio_unitario`, `num_fotos`, `num_favoritos`, `num_conversaciones`, `num_ofertas` |
| `hecho_ofertas` | oferta | `monto`, `cantidad`, `diferencia_pct_vs_lista` |
| `hecho_operaciones` | operación | `monto`, `cantidad`, `precio_unitario` |
| `hecho_reportes` | reporte | `horas_a_resolver` |

`precio_unitario` normaliza el precio para poder comparar: en publicaciones de tipo `fixed` o `per_lot` es `precio / cantidad`; en las demás (`per_kg`, `per_animal`…) el precio ya es unitario.

`diferencia_pct_vs_lista` es qué tan lejos del precio de lista quedó una oferta (−20 = 20 % por debajo).

## Cómo se llena

```bash
php artisan migrate                       # crea las tablas (y los índices nuevos)
php artisan agrolink:cargar-constelacion  # carga/actualiza todo
```

- Es **idempotente** (UPSERT): puedes correrla cuando quieras; las claves `*_sk` no cambian.
- Todo es SQL por conjuntos dentro de **una transacción**: si falla, no queda a medias.
- Se programa sola cada madrugada (03:00) si hay planificador. **Sin planificador** (Render free), los endpoints `/api/market/*` la refrescan al consultarse si pasaron más de 30 min.

## Endpoints públicos (solo agregados)

| Ruta | Qué devuelve |
|---|---|
| `GET /api/market/summary` | publicaciones activas/totales, ofertas, operaciones (cache 5 min) |
| `GET /api/market/prices?product_type_id=&state=` | precio unitario promedio/mínimo/máximo de lo publicado, por tipo de producto y estado |

## Consultas de ejemplo

Precio promedio por categoría y mes de publicación:

```sql
SELECT p.categoria_nombre, t.anio, t.mes, ROUND(AVG(h.precio_unitario), 2) AS promedio, COUNT(*) AS n
FROM hecho_publicaciones h
JOIN dim_producto p ON p.producto_sk = h.producto_sk
JOIN dim_tiempo   t ON t.fecha_id    = h.fecha_publicacion_id
WHERE NOT h.eliminada
GROUP BY 1, 2, 3
ORDER BY 2, 3, 1;
```

Cuánto se negocia por tipo de producto (ofertas aceptadas):

```sql
SELECT p.tipo_nombre, ROUND(AVG(o.diferencia_pct_vs_lista), 1) AS descuento_promedio_pct, COUNT(*) AS n
FROM hecho_ofertas o
JOIN dim_producto p ON p.producto_sk = o.producto_sk
JOIN dim_estatus  e ON e.estatus_sk  = o.estatus_sk
WHERE e.codigo = 'aceptada'
GROUP BY 1
ORDER BY 2;
```

Municipios con más publicaciones activas:

```sql
SELECT u.estado, u.municipio, COUNT(*) AS activas
FROM hecho_publicaciones h
JOIN dim_ubicacion u ON u.ubicacion_sk = h.ubicacion_sk
JOIN dim_estatus   e ON e.estatus_sk   = h.estatus_sk
WHERE e.codigo = 'publicada' AND NOT h.eliminada
GROUP BY 1, 2
ORDER BY 3 DESC
LIMIT 10;
```

## Lo que NO cambia

Ninguna tabla existente ni el contrato JSON de la API se modificaron: solo se agregaron tablas, índices y los dos endpoints `/api/market/*`. Esta capa es la misma que usa `agrolink-lite-api`; si ambas APIs comparten base de datos, las migraciones tienen los mismos nombres y se ejecutan una sola vez.
