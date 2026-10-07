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
            // Al menos uno de los dos; el que venga, único. Si viene también el otro, también único.
            'email' => ['required_without:phone', 'nullable', 'email', 'max:255', 'unique:usuarios,correo'],
            'phone' => ['required_without:email', 'nullable', 'string', 'max:20', 'regex:/^\+?[0-9 ()-]{8,20}$/', 'unique:usuarios,telefono'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            // Canal preferido: si vienen los dos contactos, con esto el usuario escoge por dónde recibir el código.
            'verify_channel' => ['nullable', 'in:email,sms'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required_without' => 'Necesitamos correo o teléfono.',
            'phone.required_without' => 'Necesitamos correo o teléfono.',
            'phone.regex' => 'Teléfono inválido.',
        ];
    }
}
