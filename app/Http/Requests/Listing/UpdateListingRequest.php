<?php

namespace App\Http\Requests\Listing;

use Illuminate\Foundation\Http\FormRequest;

class UpdateListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La comprobación real de "es el dueño" vive en ListingPolicy (autorizada en el
        // controller), no aquí, para no repetir la regla de negocio en dos lugares.
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:180'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'price_type' => ['sometimes', 'in:fixed,per_unit,per_kg,per_animal,per_lot,quote'],
            'quantity' => ['sometimes', 'numeric', 'min:0.01'],
            'unit' => ['sometimes', 'string', 'max:30'],
            'negotiable' => ['sometimes', 'boolean'],
            'attributes' => ['sometimes', 'array'],
        ];
    }
}
