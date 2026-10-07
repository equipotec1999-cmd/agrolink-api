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

    private function sendByEmail(string $to, string $code): void
    {
        $text = "Tu código de confirmación de AgroLink es: {$code}\n\nVence en 15 minutos. Si no lo pediste, ignora este mensaje.";

        // Preferido: API de Resend (más confiable que SMTP desde Render).
        $key = config('services.resend.key');
        if ($key) {
            try {
                Http::withToken($key)
                    ->timeout(10)
                    ->post('https://api.resend.com/emails', [
                        'from' => config('services.resend.from'),
                        'to' => [$to],
                        'subject' => 'Confirma tu cuenta — AgroLink',
                        'text' => $text,
                    ])
                    ->throw();

                return;
            } catch (\Throwable $e) {
                Log::warning('No se pudo enviar correo por Resend', ['error' => $e->getMessage()]);
            }
        }

        // Respaldo: el mailer configurado en MAIL_* (por defecto "log").
        try {
            Mail::raw($text, fn ($msg) => $msg->to($to)->subject('Confirma tu cuenta — AgroLink'));
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar correo de verificación', ['error' => $e->getMessage()]);
        }
        // Sin proveedor real (modo desarrollo): deja el código en los logs.
        if (! $key) {
            Log::info("Código de verificación (email) para {$to}: {$code}");
        }
    }
}
