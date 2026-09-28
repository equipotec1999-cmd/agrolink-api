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
            'category_id' => $this->category_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'default_expiry_days' => $this->default_expiry_days,
            // Atributos dinámicos con las reglas del pivot: esto es lo que Flutter usa para
            // armar el formulario de "Características" en el wizard de publicar (Fase 1 §3).
            'attributes' => $this->whenLoaded('attributes', fn () => $this->attributes->map(fn ($attribute) => [
                'id' => $attribute->id,
                'key' => $attribute->attr_key,
                'label' => $attribute->label,
                'data_type' => $attribute->data_type,
                'unit' => $attribute->unit,
                'group' => $attribute->attr_group,
                'required' => (bool) $attribute->pivot->is_required,
                'filterable' => (bool) $attribute->pivot->is_filterable,
                // Postgres regresa las columnas decimal del pivot como STRING ("0.00"),
                // no como número — igual que nos pasó con price/quantity en ListingResource.
                // Se castea aquí para que el JSON mande un número real.
                'min' => $attribute->pivot->min_value !== null ? (float) $attribute->pivot->min_value : null,
                'max' => $attribute->pivot->max_value !== null ? (float) $attribute->pivot->max_value : null,
                'options' => $attribute->data_type === 'select'
                    ? $attribute->options->pluck('value')
                    : null,
            ])),
        ];
    }
}
