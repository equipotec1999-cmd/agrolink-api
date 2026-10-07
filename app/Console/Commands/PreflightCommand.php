<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** Revisa que la configuración sea apta para producción. Sale con error si algo grave falla. */
class PreflightCommand extends Command
{
    protected $signature = 'agrolink:preflight';

    protected $description = 'Revisa la configuración de producción antes de lanzar';

    private int $fails = 0;

    public function handle(): int
    {
        $this->check(config('app.env') === 'production', 'APP_ENV=production');
        $this->check(! config('app.debug'), 'APP_DEBUG=false');
        $this->check(! empty(config('app.key')), 'APP_KEY definida');
        $this->check(str_starts_with((string) config('app.url'), 'https://'), 'APP_URL usa https');
        $this->check(config('database.default') === 'pgsql', 'Base de datos PostgreSQL');
        $this->check(config('database.connections.pgsql.sslmode') === 'require', 'DB_SSLMODE=require');
        $this->check(config('filesystems.default') === 's3', 'Fotos en almacenamiento S3 (no disco local)');
        $this->check(config('filesystems.verification_disk') === 'verificaciones', 'Documentos de verificación en bucket privado (VERIFICATION_BUCKET)');
        $this->check(config('filesystems.disks.verificaciones.bucket') !== config('filesystems.disks.s3.bucket') || ! config('filesystems.disks.verificaciones.bucket'), 'Bucket privado distinto al público', true);
        $this->check(in_array(config('logging.default'), ['stderr', 'stack'], true) && config('logging.channels.stderr') !== null, 'Logs a stderr');

        try {
            DB::connection()->getPdo();
            $this->check(true, 'Conexión a la base de datos');
            $this->check(! User::where('correo', 'test@example.com')->exists(), 'Sin usuario de prueba test@example.com');

            $admins = User::permission(['manage users', 'manage catalog', 'manage compliance rules', 'view audit logs'])->get();
            $this->check($admins->isNotEmpty(), 'Existe al menos un administrador (php artisan agrolink:crear-admin)');
            $sin2fa = $admins->filter(fn (User $u) => ! $u->dos_factores_confirmado_en);
            $this->check($sin2fa->isEmpty(), 'Todos los administradores tienen 2FA activo'.($sin2fa->isNotEmpty() ? ' (faltan: '.$sin2fa->pluck('correo')->join(', ').')' : ''), true);
        } catch (\Throwable $e) {
            $this->check(false, 'Conexión a la base de datos: '.$e->getMessage());
        }

        $this->newLine();
        $this->fails === 0
            ? $this->info('Todo listo para producción.')
            : $this->error("{$this->fails} revisión(es) fallaron.");

        return $this->fails === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function check(bool $ok, string $label, bool $warnOnly = false): void
    {
        if ($ok) {
            $this->line("  <info>✔</info> {$label}");

            return;
        }
        $this->line($warnOnly ? "  <comment>!</comment> {$label}" : "  <error>✘</error> {$label}");
        if (! $warnOnly) {
            $this->fails++;
        }
    }
}
