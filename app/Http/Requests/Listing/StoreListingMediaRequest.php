<?php

namespace App\Http\Requests\Listing;

use Illuminate\Foundation\Http\FormRequest;

class StoreListingMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El dueño real se valida en ListingPolicy (autorizada en el controller);
        // aquí solo se exige que venga un archivo válido.
        return true;
    }

    public function rules(): array
    {
        return [
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ];
    }
}
