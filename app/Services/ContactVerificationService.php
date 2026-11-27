<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Confirmación de cuenta por correo: genera un código de 6 dígitos,
 * lo manda por el canal elegido y lo valida. Nunca guarda el código en claro.
 */
class ContactVerificationService
{
    private const CODE_LENGTH = 6;
    private const EXPIRES_MINUTES = 15;
    private const MAX_ATTEMPTS = 5;

    public function send(User $user, string $channel, string $destination): void
    {
        $code = str_pad((string) random_int(0, 999_999), self::CODE_LENGTH, '0', STR_PAD_LEFT);

        // Reemplaza cualquier código previo del mismo canal (único activo por canal).
        DB::table('codigos_verificacion_contacto')
            ->where('usuario_id', $user->id)
            ->where('canal', $channel)
            ->delete();

        DB::table('codigos_verificacion_contacto')->insert([
            'usuario_id' => $user->id,
            'canal' => $channel,
            'destino' => $destination,
            'codigo_hash' => Hash::make($code),
            'intentos' => 0,
            'expira_en' => Carbon::now()->addMinutes(self::EXPIRES_MINUTES),
            'creado_en' => Carbon::now(),
        ]);

        $this->sendByEmail($destination, $code);
    }

    /** Valida el código. Devuelve el canal que se verificó. */
    public function verify(string $destination, string $code): array
    {
        $row = DB::table('codigos_verificacion_contacto')
            ->where('destino', $destination)
            ->where('canal', '!=', 'password_reset')
            ->orderByDesc('id')
            ->first();

        if (! $row) {
            throw ValidationException::withMessages(['code' => ['No hay código activo para este contacto.']]);
        }

        if (Carbon::parse($row->expira_en)->isPast()) {
            DB::table('codigos_verificacion_contacto')->where('id', $row->id)->delete();
            throw ValidationException::withMessages(['code' => ['El código venció. Pide uno nuevo.']]);
        }

        if ((int) $row->intentos >= self::MAX_ATTEMPTS) {
            DB::table('codigos_verificacion_contacto')->where('id', $row->id)->delete();
            throw ValidationException::withMessages(['code' => ['Demasiados intentos. Pide un nuevo código.']]);
        }

        if (! Hash::check($code, $row->codigo_hash)) {
            DB::table('codigos_verificacion_contacto')->where('id', $row->id)->increment('intentos');
            throw ValidationException::withMessages(['code' => ['Código incorrecto.']]);
        }

        // Marca verificado el campo correspondiente del usuario.
        $user = User::findOrFail($row->usuario_id);
        $field = $row->canal === 'email' ? 'correo_verificado_en' : 'telefono_verificado_en';
        $user->forceFill([$field => Carbon::now()])->save();

        DB::table('codigos_verificacion_contacto')->where('id', $row->id)->delete();

        return ['user' => $user->fresh(), 'channel' => $row->canal];
    }

    private function sendByEmail(string $to, string $code, string $subject = 'Confirma tu cuenta — AgroLink', ?string $body = null): void
    {
        $text = $body ?? "Tu código de confirmación de AgroLink es: {$code}\n\nVence en 15 minutos. Si no lo pediste, ignora este mensaje.";

        if (! $this->deliver($to, $subject, $text)) {
            // Sin proveedor que funcione (modo desarrollo o mal configurado): deja el código en los logs
            // para poder probar desde Render → Logs.
            Log::warning("Correo NO enviado. Código para {$to}: {$code}");
        }
    }

    /**
     * Manda un correo por el primer proveedor que funcione: Brevo → Resend → MAIL_* (SMTP/log).
     * Devuelve true solo si un proveedor real lo aceptó. Público para el comando agrolink:probar-correo.
     */
    /** Motivo del último fallo de envío (para la prueba de correo de moderación). */
    public ?string $lastError = null;

    public function deliver(string $to, string $subject, string $text): bool
    {
        $this->lastError = null;
        $errors = [];
        $brevo = config('services.brevo.key');
        if ($brevo && config('services.brevo.from_email')) {
            try {
                Http::withHeaders(['api-key' => $brevo, 'accept' => 'application/json'])
                    ->timeout(10)
                    ->post('https://api.brevo.com/v3/smtp/email', [
                        'sender' => [
                            'email' => config('services.brevo.from_email'),
                            'name' => config('services.brevo.from_name'),
                        ],
                        'to' => [['email' => $to]],
                        'subject' => $subject,
                        'textContent' => $text,
                    ])
                    ->throw();

                return true;
            } catch (\Throwable $e) {
                $msg = $e instanceof \Illuminate\Http\Client\RequestException
                    ? $e->response->status().' '.$e->response->body()
                    : $e->getMessage();
                $errors[] = 'Brevo: '.$msg;
                Log::warning('No se pudo enviar correo por Brevo', ['error' => $msg]);
            }
        }

        $resend = config('services.resend.key');
        if ($resend) {
            try {
                Http::withToken($resend)
                    ->timeout(10)
                    ->post('https://api.resend.com/emails', [
                        'from' => config('services.resend.from'),
                        'to' => [$to],
                        'subject' => $subject,
                        'text' => $text,
                    ])
                    ->throw();

                return true;
            } catch (\Throwable $e) {
                $errors[] = 'Resend: '.$e->getMessage();
                Log::warning('No se pudo enviar correo por Resend', ['error' => $e->getMessage()]);
            }
        }

        if (! in_array(config('mail.default'), ['log', 'array'], true)) {
            try {
                Mail::raw($text, fn ($msg) => $msg->to($to)->subject($subject));

                return true;
            } catch (\Throwable $e) {
                $errors[] = 'MAIL: '.$e->getMessage();
                Log::warning('No se pudo enviar correo por MAIL_*', ['error' => $e->getMessage()]);
            }
        }

        if (! $brevo && ! $resend) {
            $errors[] = 'No hay proveedor configurado: falta BREVO_API_KEY en Render.';
        } elseif ($brevo && ! config('services.brevo.from_email')) {
            $errors[] = 'Falta BREVO_FROM_EMAIL en Render.';
        }
        $this->lastError = implode(' | ', $errors) ?: 'Error desconocido.';

        return false;
    }

    /** Envía un código de 6 dígitos para resetear la contraseña. Reemplaza cualquier código previo. */
    public function sendPasswordReset(User $user, string $destination): void
    {
        $code = str_pad((string) random_int(0, 999_999), self::CODE_LENGTH, '0', STR_PAD_LEFT);

        DB::table('codigos_verificacion_contacto')
            ->where('usuario_id', $user->id)
            ->where('canal', 'password_reset')
            ->delete();

        DB::table('codigos_verificacion_contacto')->insert([
            'usuario_id' => $user->id,
            'canal' => 'password_reset',
            'destino' => $destination,
            'codigo_hash' => Hash::make($code),
            'intentos' => 0,
            'expira_en' => Carbon::now()->addMinutes(self::EXPIRES_MINUTES),
            'creado_en' => Carbon::now(),
        ]);

        $this->sendByEmail($destination, $code, subject: 'Recupera tu contraseña — AgroLink',
            body: "Tu código para recuperar la contraseña de AgroLink es: {$code}\n\nVence en 15 minutos. Si no lo pediste, ignora este mensaje.");
    }

    /** Valida el código de reseteo y devuelve el usuario. */
    public function verifyPasswordReset(string $destination, string $code): User
    {
        $row = DB::table('codigos_verificacion_contacto')
            ->where('destino', $destination)
            ->where('canal', 'password_reset')
            ->orderByDesc('id')
            ->first();

        if (! $row) {
            throw ValidationException::withMessages(['code' => ['No hay código activo para este correo.']]);
        }
        if (Carbon::parse($row->expira_en)->isPast()) {
            DB::table('codigos_verificacion_contacto')->where('id', $row->id)->delete();
            throw ValidationException::withMessages(['code' => ['El código venció. Pide uno nuevo.']]);
        }
        if ((int) $row->intentos >= self::MAX_ATTEMPTS) {
            DB::table('codigos_verificacion_contacto')->where('id', $row->id)->delete();
            throw ValidationException::withMessages(['code' => ['Demasiados intentos. Pide un nuevo código.']]);
        }
        if (! Hash::check($code, $row->codigo_hash)) {
            DB::table('codigos_verificacion_contacto')->where('id', $row->id)->increment('intentos');
            throw ValidationException::withMessages(['code' => ['Código incorrecto.']]);
        }

        DB::table('codigos_verificacion_contacto')->where('id', $row->id)->delete();

        return User::findOrFail($row->usuario_id);
    }

}
