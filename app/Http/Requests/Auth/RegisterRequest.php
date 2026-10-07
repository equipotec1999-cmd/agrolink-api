<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validación server-side completa: nunca se confía en la del cliente (regla del
     * proyecto §20), aunque Flutter valide lo mismo antes de enviar.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'lastname' => ['nullable', 'string', 'max:150'],
            // El correo es obligatorio: ahí llega el código de confirmación.
            'email' => ['required', 'email', 'max:255', 'unique:usuarios,correo'],
            // El teléfono es solo dato de contacto (opcional, no se verifica).
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9 ()-]{8,20}$/', 'unique:usuarios,telefono'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Teléfono inválido.',
        ];
    }
}
