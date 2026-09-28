<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\ListingLocation;
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
                'user_id' => $user->id,
                'product_type_id' => $data['product_type_id'],
                'property_id' => $data['property_id'] ?? null,
                'title' => $data['title'],
                'slug' => $this->uniqueSlug($data['title']),
                'description' => $data['description'] ?? null,
                'price' => $data['price'] ?? null,
                'price_type' => $data['price_type'],
                'quantity' => $data['quantity'],
                'unit' => $data['unit'],
                'sale_mode' => $data['sale_mode'] ?? 'individual',
                'negotiable' => $data['negotiable'] ?? false,
                'status' => 'draft',
                'moderation_status' => 'pending',
            ]);

            $this->syncAttributes($listing, $data['attributes'] ?? []);
            $this->syncLocation($listing, $data);

            return $listing->fresh(['productType', 'user.sellerProfile', 'location']);
        });
    }

    public function update(Listing $listing, array $data): Listing
    {
        return DB::transaction(function () use ($listing, $data) {
            $listing->fill(array_intersect_key($data, array_flip([
                'title', 'description', 'price', 'price_type', 'quantity', 'unit', 'negotiable',
            ])));
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
            'status' => 'published',
            'published_at' => now(),
            'expires_at' => now()->addDays($listing->productType->default_expiry_days),
        ]);

        return $listing;
    }

    public function archive(Listing $listing): Listing
    {
        $listing->update(['status' => 'archived']);

        return $listing;
    }

    /**
     * Valida cada atributo enviado contra product_type_attributes (requerido, rango
     * numérico) y lo guarda en la columna correcta de listing_attribute_values según su
     * data_type, además de refrescar attributes_cache para el filtrado rápido por JSONB.
     */
    private function syncAttributes(Listing $listing, array $submitted): void
    {
        $rules = $listing->productType->attributes()->get()->keyBy('attr_key');

        $missing = $rules->filter(fn ($attr) => $attr->pivot->is_required && ! array_key_exists($attr->attr_key, $submitted));
        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'attributes' => 'Faltan atributos requeridos: '.$missing->pluck('label')->implode(', '),
            ]);
        }

        $listing->attributeValues()->delete();
        $cache = [];

        foreach ($submitted as $key => $value) {
            $attribute = $rules->get($key);
            if (! $attribute || $value === null || $value === '') {
                continue; // atributo desconocido para este tipo, o vacío: se ignora, no se inventa.
            }

            if ($attribute->pivot->min_value !== null && is_numeric($value) && $value < $attribute->pivot->min_value) {
                throw ValidationException::withMessages(["attributes.$key" => "El mínimo para {$attribute->label} es {$attribute->pivot->min_value}."]);
            }
            if ($attribute->pivot->max_value !== null && is_numeric($value) && $value > $attribute->pivot->max_value) {
                throw ValidationException::withMessages(["attributes.$key" => "El máximo para {$attribute->label} es {$attribute->pivot->max_value}."]);
            }

            $row = ['listing_id' => $listing->id, 'attribute_id' => $attribute->id];

            $row = match ($attribute->data_type) {
                'number' => $row + ['value_number' => $value],
                'boolean' => $row + ['value_bool' => (bool) $value],
                'date' => $row + ['value_date' => $value],
                'select' => $row + [
                    'option_id' => $attribute->options->firstWhere('value', $value)?->id,
                    'value_text' => $value,
                ],
                default => $row + ['value_text' => $value],
            };

            $listing->attributeValues()->create($row);
            $cache[$key] = $value;
        }

        $listing->update(['attributes_cache' => $cache]);
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
            $state = $property->state;
            $municipality = $property->municipality;
            $postalCode = $property->postal_code;

            $point = DB::selectOne(
                'SELECT ST_Y(exact_location::geometry) as lat, ST_X(exact_location::geometry) as lng FROM properties WHERE id = ?',
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

        ListingLocation::updateOrCreate(
            ['listing_id' => $listing->id],
            [
                'property_id' => $data['property_id'] ?? null,
                'state' => $state,
                'municipality' => $municipality,
                'postal_code' => $postalCode,
            ]
        );

        DB::statement(
            'UPDATE listing_locations SET exact_location = ST_SetSRID(ST_MakePoint(?, ?), 4326), approx_location = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE listing_id = ?',
            [$lng, $lat, $approxLng, $approxLat, $listing->id]
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
