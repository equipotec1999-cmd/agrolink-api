<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            // Identifica el dispositivo/app que pide el token (Fase 1 §21: expiración y
            // revocación por dispositivo); Flutter manda algo como "android-<uuid>".
            'device_name' => ['required', 'string', 'max:100'],
        ];
    }
}
