<?php

namespace App\Console\Commands;

use App\Services\ContactVerificationService;
use Illuminate\Console\Command;

/** Prueba el envío de correos (recuperar contraseña) desde Render → Shell. */
class ProbarCorreoCommand extends Command
{
    protected $signature = 'agrolink:probar-correo {correo}';

    protected $description = 'Envía un correo de prueba con el proveedor configurado (Brevo, Resend o MAIL_*)';

    public function handle(ContactVerificationService $mail): int
    {
        $this->line('Brevo:  '.(config('services.brevo.key') ? 'configurado ('.config('services.brevo.from_email').')' : 'sin BREVO_API_KEY'));
        $this->line('Resend: '.(config('services.resend.key') ? 'configurado ('.config('services.resend.from').')' : 'sin RESEND_KEY'));
        $this->line('MAIL:   '.config('mail.default'));

        $ok = $mail->deliver($this->argument('correo'), 'Prueba de correo — AgroLink',
            "Si lees esto, AgroLink ya puede mandar los códigos de recuperación.");

        if ($ok) {
            $this->info('Enviado. Revisa la bandeja (y spam).');

            return self::SUCCESS;
        }
        $this->error('No se envió. Revisa los avisos en Render → Logs.');

        return self::FAILURE;
    }
}
