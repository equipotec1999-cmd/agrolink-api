<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Reúne la lógica de "publicar un listing" que no es simple CRUD: validar los atributos
 * dinámicos contra product_type_attributes, guardar la ubicación (con jitter para el
 * punto aproximado) y mantener attributes_cache sincronizada. Se separa del controller
 * para que este quede delgado (regla §20: SOLID donde tenga sentido).
 */
class ListingService
{
    public function create(User $user, array $data): Listing
    {
        return DB::transaction(function () use ($user, $data) {
            $listing = Listing::create([
                'usuario_id' => $user->id,
                'tipo_producto_id' => $data['product_type_id'],
                'predio_id' => $data['property_id'] ?? null,
                'titulo' => $data['title'],
                'slug' => $this->uniqueSlug($data['title']),
                'descripcion' => $data['description'] ?? null,
                'precio' => $data['price'] ?? null,
                'tipo_precio' => $data['price_type'],
                'cantidad' => $data['quantity'],
                'unidad' => $data['unit'],
                'modalidad_venta' => $data['sale_mode'] ?? 'individual',
                'negociable' => $data['negotiable'] ?? false,
                'estatus' => 'draft',
                'estatus_moderacion' => 'pending',
            ]);

            $this->syncAttributes($listing, $data['attributes'] ?? []);
            $this->syncLocation($listing, $data);

            return $listing->fresh(['productType', 'user.sellerProfile', 'location']);
        });
    }

    public function update(Listing $listing, array $data): Listing
    {
        return DB::transaction(function () use ($listing, $data) {
            // Claves de la API (inglés) -> columnas de la base (español).
            $columnas = [
                'title' => 'titulo', 'description' => 'descripcion', 'price' => 'precio',
                'price_type' => 'tipo_precio', 'quantity' => 'cantidad', 'unit' => 'unidad',
                'negotiable' => 'negociable',
            ];
            foreach ($columnas as $clave => $columna) {
                if (array_key_exists($clave, $data)) {
                    $listing->{$columna} = $data[$clave];
                }
            }
            $listing->save();

            if (array_key_exists('attributes', $data)) {
                $this->syncAttributes($listing, $data['attributes']);
            }

            return $listing->fresh(['productType', 'user.sellerProfile', 'location']);
        });
    }

    /**
     * Publica el listing: status -> published (se ve ya, Fase 1 §5 "publicar primero,
     * aprobar después"). moderation_status NO se toca aquí; sigue 'pending' hasta que
     * moderación lo revise (Fase 6). expires_at usa el default del tipo de producto.
     */
    public function publish(Listing $listing): Listing
    {
        $listing->update([
            'estatus' => 'published',
            'publicado_en' => now(),
            'vence_en' => now()->addDays($listing->productType->dias_vigencia_predeterminados),
        ]);

        return $listing;
    }

    public function archive(Listing $listing): Listing
    {
        $listing->update(['estatus' => 'archived']);

        return $listing;
    }

    /**
     * Valida cada atributo enviado contra product_type_attributes (requerido, rango
     * numérico) y lo guarda en la columna correcta de listing_attribute_values según su
     * data_type, además de refrescar attributes_cache para el filtrado rápido por JSONB.
     */
    private function syncAttributes(Listing $listing, array $submitted): void
    {
        $rules = $listing->productType->attributes()->get()->keyBy('clave');

        $missing = $rules->filter(fn ($attr) => $attr->pivot->es_obligatorio && ! array_key_exists($attr->clave, $submitted));
        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'attributes' => 'Faltan atributos requeridos: '.$missing->pluck('etiqueta')->implode(', '),
            ]);
        }

        $listing->attributeValues()->delete();
        $cache = [];

        foreach ($submitted as $key => $value) {
            $attribute = $rules->get($key);
            if (! $attribute || $value === null || $value === '') {
                continue; // atributo desconocido para este tipo, o vacío: se ignora, no se inventa.
            }

            if ($attribute->pivot->valor_minimo !== null && is_numeric($value) && $value < $attribute->pivot->valor_minimo) {
                throw ValidationException::withMessages(["attributes.$key" => "El mínimo para {$attribute->etiqueta} es {$attribute->pivot->valor_minimo}."]);
            }
            if ($attribute->pivot->valor_maximo !== null && is_numeric($value) && $value > $attribute->pivot->valor_maximo) {
                throw ValidationException::withMessages(["attributes.$key" => "El máximo para {$attribute->etiqueta} es {$attribute->pivot->valor_maximo}."]);
            }

            $row = ['publicacion_id' => $listing->id, 'atributo_id' => $attribute->id];

            $row = match ($attribute->tipo_dato) {
                'number' => $row + ['valor_numero' => $value],
                'boolean' => $row + ['valor_booleano' => (bool) $value],
                'date' => $row + ['valor_fecha' => $value],
                'select' => $row + [
                    'opcion_id' => $attribute->options->firstWhere('valor', $value)?->id,
                    'valor_texto' => $value,
                ],
                default => $row + ['valor_texto' => $value],
            };

            $listing->attributeValues()->create($row);
            $cache[$key] = $value;
        }

        $listing->update(['atributos_cache' => $cache]);
    }

    /**
     * Si viene property_id, copia esa ubicación (desplazando el punto aproximado ~1-2 km
     * al azar, para no delatar la ubicación exacta de la finca — Fase 1 §6). Si no, usa
     * la ubicación suelta que mandó el formulario.
     */
    private function syncLocation(Listing $listing, array $data): void
    {
        if (! empty($data['property_id'])) {
            $property = Property::findOrFail($data['property_id']);
            $state = $property->estado;
            $municipality = $property->municipio;
            $postalCode = $property->codigo_postal;

            $point = DB::selectOne(
                'SELECT ST_Y(ubicacion_exacta::geometry) as lat, ST_X(ubicacion_exacta::geometry) as lng FROM predios WHERE id = ?',
                [$property->id]
            );
            $lat = $point->lat;
            $lng = $point->lng;
        } else {
            $state = $data['location']['state'];
            $municipality = $data['location']['municipality'];
            $postalCode = $data['location']['postal_code'] ?? null;
            $lat = $data['location']['lat'];
            $lng = $data['location']['lng'];
        }

        // Jitter ~0.01-0.02° (~1-2 km) para el punto público; el exacto queda intacto.
        $approxLat = $lat + (mt_rand(-200, 200) / 10000);
        $approxLng = $lng + (mt_rand(-200, 200) / 10000);

        // Un solo upsert: exact_location y approx_location son NOT NULL, así que la
        // fila no puede crearse primero sin puntos y rellenarse después.
        DB::statement(
            'INSERT INTO ubicaciones_publicacion
                (publicacion_id, predio_id, estado, municipio, codigo_postal, ubicacion_exacta, ubicacion_aproximada, creado_en, actualizado_en)
             VALUES (?, ?, ?, ?, ?, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, NOW(), NOW())
             ON CONFLICT (publicacion_id) DO UPDATE SET
                predio_id = EXCLUDED.predio_id,
                estado = EXCLUDED.estado,
                municipio = EXCLUDED.municipio,
                codigo_postal = EXCLUDED.codigo_postal,
                ubicacion_exacta = EXCLUDED.ubicacion_exacta,
                ubicacion_aproximada = EXCLUDED.ubicacion_aproximada,
                actualizado_en = NOW()',
            [
                $listing->id,
                $data['property_id'] ?? null,
                $state,
                $municipality,
                $postalCode,
                $lng, $lat,
                $approxLng, $approxLat,
            ]
        );
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 1;
        while (Listing::where('slug', $slug)->exists()) {
            $slug = "{$base}-".(++$i);
        }

        return $slug;
    }
}
