<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class StoreOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Pertenencia a la conversación: se valida en el controlador.
    }

    public function rules(): array
    {
        return [
            // Precio por unidad (según el tipo de precio de la publicación).
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999'],
        ];
    }
}
