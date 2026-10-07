<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Verificación en dos pasos (TOTP). Activación: setup -> confirm (con un código de la app).
 * Inicio de sesión: login devuelve un token pendiente -> challenge lo cambia por uno completo.
 */
class TwoFactorController extends Controller
{
    private function secretOf(User $user): ?string
    {
        return $user->secreto_dos_factores ? Crypt::decryptString($user->secreto_dos_factores) : null;
    }

    /** Código de recuperación: se guarda solo su huella (HMAC con la APP_KEY), nunca el código. */
    private function fingerprint(string $code): string
    {
        return hash_hmac('sha256', strtoupper(str_replace('-', '', trim($code))), (string) config('app.key'));
    }

    private function invalid(string $field = 'code'): never
    {
        throw ValidationException::withMessages([$field => ['Código incorrecto o vencido.']]);
    }

    /** Valida un código TOTP (y lo marca como usado) o un código de recuperación (y lo consume). */
    private function acceptCode(User $user, ?string $code, ?string $recovery): bool
    {
        if ($code) {
            $step = Totp::verify($this->secretOf($user) ?? '', $code, $user->dos_factores_ultimo_paso);
            if ($step === null) {
                return false;
            }
            $user->forceFill(['dos_factores_ultimo_paso' => $step])->save();

            return true;
        }

        if ($recovery) {
            $hashes = json_decode((string) $user->codigos_recuperacion_dos_factores, true) ?: [];
            $fp = $this->fingerprint($recovery);
            foreach ($hashes as $i => $stored) {
                if (hash_equals($stored, $fp)) {
                    unset($hashes[$i]);
                    $user->forceFill(['codigos_recuperacion_dos_factores' => json_encode(array_values($hashes))])->save();

                    return true;
                }
            }
        }

        return false;
    }

    /** Paso 1: genera el secreto (aún sin activar). La app lo muestra para escribirlo en la app autenticadora. */
    public function setup(Request $request)
    {
        $user = $request->user();
        abort_if($user->hasTwoFactorEnabled(), 422, 'La verificación en dos pasos ya está activa.');

        $secret = Totp::newSecret();
        $user->forceFill([
            'secreto_dos_factores' => Crypt::encryptString($secret),
            'dos_factores_ultimo_paso' => null,
        ])->save();

        return response()->json(['data' => [
            'secret' => $secret,
            'otpauth_url' => Totp::uri($user->correo, $secret),
        ]]);
    }

    /** Paso 2: con un código válido se activa y se entregan (una sola vez) los códigos de recuperación. */
    public function confirm(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:12']]);
        $user = $request->user();

        abort_if($user->hasTwoFactorEnabled(), 422, 'La verificación en dos pasos ya está activa.');
        abort_unless($user->secreto_dos_factores, 422, 'Primero inicia la configuración.');

        $step = Totp::verify($this->secretOf($user), $data['code']);
        if ($step === null) {
            $this->invalid();
        }

        $codes = collect(range(1, 8))->map(fn () => strtoupper(Str::random(5).'-'.Str::random(5)))->all();
        $user->forceFill([
            'dos_factores_confirmado_en' => now(),
            'dos_factores_ultimo_paso' => $step,
            'codigos_recuperacion_dos_factores' => json_encode(array_map(fn ($c) => $this->fingerprint($c), $codes)),
        ])->save();

        return response()->json(['data' => ['recovery_codes' => $codes]]);
    }

    /** Desactivar: contraseña + código. Las cuentas administrativas no pueden desactivarla. */
    public function disable(Request $request)
    {
        $data = $request->validate([
            'password' => ['required', 'string'],
            'code' => ['nullable', 'string', 'max:12'],
            'recovery_code' => ['nullable', 'string', 'max:20'],
        ]);
        $user = $request->user();

        abort_unless($user->hasTwoFactorEnabled(), 422, 'La verificación en dos pasos no está activa.');
        abort_if($user->needsTwoFactor(), 403, 'Las cuentas administrativas no pueden desactivar la verificación en dos pasos.');

        if (! Hash::check($data['password'], $user->contrasena)) {
            throw ValidationException::withMessages(['password' => ['Contraseña incorrecta.']]);
        }
        if (! $this->acceptCode($user, $data['code'] ?? null, $data['recovery_code'] ?? null)) {
            $this->invalid();
        }

        $user->forceFill([
            'secreto_dos_factores' => null,
            'codigos_recuperacion_dos_factores' => null,
            'dos_factores_confirmado_en' => null,
            'dos_factores_ultimo_paso' => null,
        ])->save();

        return response()->noContent();
    }

    /** Inicio de sesión, paso 2: el token pendiente + un código = token completo. */
    public function challenge(Request $request)
    {
        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:12'],
            'recovery_code' => ['nullable', 'string', 'max:20'],
        ]);
        $user = $request->user();
        $pending = $user->currentAccessToken();

        abort_unless($user->hasTwoFactorEnabled(), 422, 'La verificación en dos pasos no está activa.');

        if (! $this->acceptCode($user, $data['code'] ?? null, $data['recovery_code'] ?? null)) {
            $this->invalid();
        }

        $name = $pending->name;
        $pending->delete();

        return response()->json([
            'user' => new UserResource($user),
            'token' => $user->createToken($name)->plainTextToken,
        ]);
    }
}
