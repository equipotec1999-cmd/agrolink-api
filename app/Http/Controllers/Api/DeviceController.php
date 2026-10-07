<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    /** Registra (o reasigna a este usuario) el token push del celular. Idempotente. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'min:20', 'max:4096'],
            'platform' => ['nullable', 'in:android,ios'],
        ]);

        DeviceToken::updateOrCreate(
            ['token' => $data['token']],
            [
                'usuario_id' => $request->user()->id,
                'plataforma' => $data['platform'] ?? 'android',
                'ultimo_uso_en' => now(),
            ],
        );

        return response()->noContent();
    }

    /** Al cerrar sesión: deja de mandar push de esta cuenta a este celular. Solo borra tokens propios. */
    public function destroy(Request $request)
    {
        $data = $request->validate(['token' => ['required', 'string', 'max:4096']]);

        DeviceToken::where('token', $data['token'])->where('usuario_id', $request->user()->id)->delete();

        return response()->noContent();
    }
}
