<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductTypeResource extends JsonResource
{
    /**
     * Mapa estático de tipos de precio permitidos por producto. Vive aquí
     * en lugar de la BD porque es parte del contrato con la app y rara vez
     * cambia; evitar un join y una columna más por fila.
     */
    private const PRICE_TYPES = [
        // Ganado: por cabeza, por peso, por lote entero o a cotizar.
        'equinos' => ['per_animal', 'per_kg', 'per_lot', 'quote'],
        'bovinos' => ['per_animal', 'per_kg', 'per_lot', 'quote'],
        'ovinos' => ['per_animal', 'per_kg', 'per_lot', 'quote'],
        'caprinos' => ['per_animal', 'per_kg', 'per_lot', 'quote'],
        'porcinos' => ['per_animal', 'per_kg', 'per_lot', 'quote'],
        'aves' => ['per_animal', 'per_lot', 'quote'],
        // Apicultura: miel se mide en kilos; colmenas, núcleos y reinas son unidades enteras.
        'colmenas' => ['per_unit', 'per_lot', 'quote'],
        'nucleos' => ['per_unit', 'per_lot'],
        'reinas' => ['per_unit'],
        'miel' => ['per_kg', 'per_unit', 'quote'],
        // Agricultura: cosecha a granel por kilo; plantas y árboles por pieza.
        'chiles' => ['per_kg', 'per_lot', 'quote'],
        'frutas' => ['per_kg', 'per_lot', 'quote'],
        'hortalizas' => ['per_kg', 'per_lot', 'quote'],
        'plantas' => ['per_unit', 'per_lot', 'quote'],
    ];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->categoria_id,
            'name' => $this->nombre,
            'slug' => $this->slug,
            'icon' => $this->icono,
            'default_expiry_days' => $this->dias_vigencia_predeterminados,
            // Tipos de precio que aplican a este producto; cae a opciones genéricas si no mapea.
            'price_types' => self::PRICE_TYPES[$this->slug] ?? ['per_unit', 'per_lot', 'quote'],
            // Atributos dinámicos con las reglas del pivot: esto es lo que Flutter usa para
            // armar el formulario de "Características" en el wizard de publicar (Fase 1 §3).
            'attributes' => $this->whenLoaded('attributes', fn () => $this->attributes->map(fn ($attribute) => [
                'id' => $attribute->id,
                'key' => $attribute->clave,
                'label' => $attribute->etiqueta,
                'data_type' => $attribute->tipo_dato,
                'unit' => $attribute->unidad,
                'group' => $attribute->grupo,
                'required' => (bool) $attribute->pivot->es_obligatorio,
                'filterable' => (bool) $attribute->pivot->es_filtrable,
                // Postgres regresa las columnas decimal del pivot como STRING ("0.00"),
                // no como número — igual que nos pasó con price/quantity en ListingResource.
                // Se castea aquí para que el JSON mande un número real.
                'min' => $attribute->pivot->valor_minimo !== null ? (float) $attribute->pivot->valor_minimo : null,
                'max' => $attribute->pivot->valor_maximo !== null ? (float) $attribute->pivot->valor_maximo : null,
                'options' => $attribute->tipo_dato === 'select'
                    ? $attribute->options->pluck('valor')
                    : null,
            ])),
        ];
    }
}
