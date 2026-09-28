<?php

namespace App\Http\Requests\Listing;

use Illuminate\Foundation\Http\FormRequest;

class StoreListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // cualquier usuario autenticado puede publicar; auth:sanctum ya lo exige en la ruta.
    }

    /**
     * Validación server-side completa (regla §20): los rangos min/max y "requerido" de
     * cada atributo dinámico se re-validan en ListingService contra product_type_attributes,
     * nunca solo aquí ni solo en Flutter.
     */
    public function rules(): array
    {
        return [
            'product_type_id' => ['required', 'integer', 'exists:product_types,id'],
            'property_id' => ['nullable', 'integer', 'exists:properties,id'],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'price_type' => ['required', 'in:fixed,per_unit,per_kg,per_animal,per_lot,quote'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['required', 'string', 'max:30'],
            'sale_mode' => ['required', 'in:individual,lot'],
            'negotiable' => ['boolean'],

            // Solo si no se manda property_id: hay que dar la ubicación a mano.
            'location' => ['required_without:property_id', 'array'],
            'location.state' => ['required_with:location', 'string', 'max:100'],
            'location.municipality' => ['required_with:location', 'string', 'max:100'],
            'location.postal_code' => ['nullable', 'string', 'max:10'],
            'location.lat' => ['required_with:location', 'numeric', 'between:-90,90'],
            'location.lng' => ['required_with:location', 'numeric', 'between:-180,180'],

            // Atributos dinámicos: clave = attr_key (p.ej. "raza"), valor según data_type.
            'attributes' => ['nullable', 'array'],
        ];
    }
}
