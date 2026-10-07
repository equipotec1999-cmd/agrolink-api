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
    public function register(RegisterRequest $request, \App\Services\ContactVerificationService $verify)
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'nombre' => $request->string('name'),
                'apellidos' => $request->input('lastname'),
                'correo' => $request->input('email'),
                'telefono' => $request->input('phone'),
                'contrasena' => $request->string('password'),
            ]);

            Profile::create(['usuario_id' => $user->id]);

            return $user;
        });

        // El canal preferido se decide al momento del registro: si vino correo, correo;
        // si solo vino teléfono, SMS. La cuenta queda sin verificar hasta que el usuario
        // confirme el código. El token completo se entrega en /verify-contact.
        $channel = $request->input('verify_channel');
        $destination = null;
        if ($channel === 'email' && $user->correo) {
            $destination = $user->correo;
        } elseif ($channel === 'sms' && $user->telefono) {
            $destination = $user->telefono;
        } elseif ($user->correo) {
            $channel = 'email';
            $destination = $user->correo;
        } elseif ($user->telefono) {
            $channel = 'sms';
            $destination = $user->telefono;
        }

        if ($destination) {
            $verify->send($user, $channel, $destination);
        }

        return response()->json([
            'user' => new UserResource($user),
            'verification' => [
                'channel' => $channel,
                'destination' => $destination,
                'expires_in_minutes' => 15,
            ],
        ], 201);
    }

    /** Validar el código de confirmación: marca el contacto como verificado y entrega el token completo. */
    public function verifyContact(Request $request, \App\Services\ContactVerificationService $verify)
    {
        $data = $request->validate([
            'destination' => ['required', 'string', 'max:180'],
            'code' => ['required', 'string', 'size:6'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $result = $verify->verify($data['destination'], $data['code']);
        $user = $result['user'];
        $token = $user->createToken($data['device_name'] ?? 'registro')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user->load(['profile', 'sellerProfile'])),
            'token' => $token,
            'verified_channel' => $result['channel'],
        ]);
    }

    /** Reenvía el código al mismo contacto. Rate-limited por ruta. */
    public function resendVerification(Request $request, \App\Services\ContactVerificationService $verify)
    {
        $data = $request->validate([
            'destination' => ['required', 'string', 'max:180'],
        ]);

        $user = User::where('correo', $data['destination'])->orWhere('telefono', $data['destination'])->first();
        if (! $user) {
            // Respuesta genérica para no revelar si existe o no la cuenta.
            return response()->json(['message' => 'Si el contacto existe, enviamos un nuevo código.']);
        }
        $channel = $user->correo === $data['destination'] ? 'email' : 'sms';
        $verify->send($user, $channel, $data['destination']);

        return response()->json(['message' => 'Código reenviado.']);
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
