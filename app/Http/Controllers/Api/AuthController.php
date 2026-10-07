<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'nombre' => $request->string('name'),
                'correo' => $request->string('email'),
                'telefono' => $request->input('phone'),
                'contrasena' => $request->string('password'),
            ]);

            Profile::create(['usuario_id' => $user->id]);

            return $user;
        });

        // Token sin expiración explícita aquí; el rate limiting de /login y el de
        // creación de cuentas se configuran a nivel de ruta (throttle), no aquí.
        $token = $user->createToken('registro')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('correo', $request->string('email'))->first();

        // Mensaje genérico a propósito: no revela si falló el correo o la contraseña.
        if (! $user || ! Hash::check($request->string('password'), $user->contrasena)) {
            throw ValidationException::withMessages([
                'email' => ['Correo o contraseña incorrectos.'],
            ]);
        }

        // Con verificación en dos pasos: solo un token pendiente (10 min) que únicamente
        // sirve para /two-factor/challenge; el token completo llega al validar el código.
        if ($user->hasTwoFactorEnabled()) {
            return response()->json([
                'requires_two_factor' => true,
                'challenge_token' => $user->createToken(
                    (string) $request->string('device_name'),
                    ['two-factor-pending'],
                    now()->addMinutes(10),
                )->plainTextToken,
            ]);
        }

        $token = $user->createToken($request->string('device_name'))->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }

    public function me(Request $request)
    {
        return new UserResource($request->user()->load(['profile', 'sellerProfile']));
    }
}
