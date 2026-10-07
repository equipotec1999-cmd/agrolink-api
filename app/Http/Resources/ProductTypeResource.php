<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->categoria_id,
            'name' => $this->nombre,
            'slug' => $this->slug,
            'icon' => $this->icono,
            'default_expiry_days' => $this->dias_vigencia_predeterminados,
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
