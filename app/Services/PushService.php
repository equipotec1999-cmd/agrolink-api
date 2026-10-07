<?php

namespace App\Services;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Push con Firebase Cloud Messaging (API HTTP v1), sin librerías extra: firma el JWT de la
 * cuenta de servicio con openssl, lo cambia por un access token (cacheado) y envía.
 *
 * Config: FIREBASE_CREDENTIALS_BASE64 = el JSON de la cuenta de servicio en base64 (una línea).
 * Sin esa variable el push queda desactivado (no es un error). Un fallo aquí NUNCA debe
 * tumbar la acción de negocio que lo dispara.
 */
class PushService
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    private static function credentials(): ?array
    {
        $b64 = config('services.firebase.credentials_base64');
        if (! $b64) {
            return null;
        }
        $json = json_decode((string) base64_decode(trim($b64), true), true);
        if (! is_array($json) || empty($json['client_email']) || empty($json['private_key']) || empty($json['project_id'])) {
            throw new RuntimeException('FIREBASE_CREDENTIALS_BASE64 no es un JSON de cuenta de servicio válido.');
        }

        return $json;
    }

    private static function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /** JWT RS256 firmado con la llave privada de la cuenta de servicio. */
    public static function signedJwt(array $creds, ?int $now = null): string
    {
        $now ??= time();
        $aud = $creds['token_uri'] ?? 'https://oauth2.googleapis.com/token';
        $head = self::b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = self::b64url(json_encode([
            'iss' => $creds['client_email'],
            'scope' => self::SCOPE,
            'aud' => $aud,
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        if (! openssl_sign("$head.$claims", $signature, $creds['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('No se pudo firmar el JWT de Firebase (llave privada inválida).');
        }

        return "$head.$claims.".self::b64url($signature);
    }

    private static function accessToken(array $creds): string
    {
        return Cache::remember('fcm_access_token', 3000, function () use ($creds) {
            $response = Http::asForm()->timeout(5)->post($creds['token_uri'] ?? 'https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => self::signedJwt($creds),
            ]);
            $token = $response->json('access_token');
            if (! $response->successful() || ! $token) {
                throw new RuntimeException('Google rechazó la autenticación de Firebase: '.$response->body());
            }

            return $token;
        });
    }

    /** Envía a todos los dispositivos del usuario. $data: valores string (requisito de FCM). */
    public static function toUser(int $userId, string $title, string $body, array $data = []): void
    {
        try {
            $creds = self::credentials();
            if (! $creds) {
                return;
            }
            $tokens = DeviceToken::where('usuario_id', $userId)->pluck('token');
            if ($tokens->isEmpty()) {
                return;
            }

            $access = self::accessToken($creds);
            $url = "https://fcm.googleapis.com/v1/projects/{$creds['project_id']}/messages:send";
            $data = array_map('strval', $data);

            foreach ($tokens as $token) {
                $response = Http::withToken($access)->timeout(5)->post($url, ['message' => [
                    'token' => $token,
                    'notification' => ['title' => $title, 'body' => $body],
                    'data' => $data,
                    'android' => [
                        'priority' => 'HIGH',
                        'notification' => ['channel_id' => 'agrolink_default'],
                    ],
                ]]);

                if ($response->status() === 401) {
                    Cache::forget('fcm_access_token');
                }
                // Token que ya no existe (app desinstalada, token rotado): se borra.
                $errors = collect($response->json('error.details', []))->pluck('errorCode');
                if ($response->status() === 404 || $errors->contains('UNREGISTERED')) {
                    DeviceToken::where('token', $token)->delete();
                } elseif (! $response->successful()) {
                    report(new RuntimeException('FCM respondió '.$response->status().': '.$response->body()));
                }
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
