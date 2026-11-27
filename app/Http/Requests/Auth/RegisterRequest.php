<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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
    /** El correo se guarda siempre en minúsculas y sin espacios. */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => strtolower(trim($this->input('email')))]);
        }
    }

    public function rules(): array
    {
        $email = (string) $this->input('email');

        return [
            'name' => ['required', 'string', 'max:150'],
            'lastname' => ['nullable', 'string', 'max:150'],
            // El correo es obligatorio: ahí llega el código de confirmación.
            // Una cuenta que nunca confirmó su correo no lo «aparta»: se reemplaza al registrarse otra vez.
            'email' => ['required', 'email', 'max:255', function ($attr, $value, $fail) {
                $taken = \App\Models\User::whereRaw('lower(correo) = ?', [$value])
                    ->whereNotNull('correo_verificado_en')
                    ->exists();
                if ($taken) {
                    $fail('Ya existe una cuenta con este correo.');
                }
            }],
            // El teléfono es solo dato de contacto (opcional, no se verifica).
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9 ()-]{8,20}$/', Rule::unique('usuarios', 'telefono')->where(
                fn ($q) => $q->where(fn ($w) => $w->whereNotNull('correo_verificado_en')->orWhereRaw('lower(correo) <> ?', [$email]))
            )],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            // Aceptar el aviso de privacidad y los términos (y declarar ser mayor de edad).
            'accept_terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Teléfono inválido.',
            'accept_terms.accepted' => 'Para crear tu cuenta debes aceptar el aviso de privacidad y los términos.',
        ];
    }
}
