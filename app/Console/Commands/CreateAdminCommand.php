<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/** Crea (o promueve) una cuenta de administrador. Debe activar 2FA en su primer inicio de sesión. */
class CreateAdminCommand extends Command
{
    protected $signature = 'agrolink:crear-admin {correo} {--nombre=Administrador} {--password=} {--rol=administrador}';

    protected $description = 'Crea una cuenta de administrador o moderador (rol administrador|moderador)';

    public function handle(): int
    {
        $rol = $this->option('rol');
        if (! in_array($rol, ['administrador', 'moderador'], true)) {
            $this->error('El rol debe ser administrador o moderador.');

            return self::FAILURE;
        }

        $correo = strtolower(trim($this->argument('correo')));
        $user = User::where('correo', $correo)->first();
        $password = null;

        if (! $user) {
            $password = $this->option('password') ?: Str::password(16, symbols: false);
            if (strlen($password) < 10) {
                $this->error('La contraseña debe tener al menos 10 caracteres.');

                return self::FAILURE;
            }
            $user = User::create([
                'nombre' => $this->option('nombre'),
                'correo' => $correo,
                'contrasena' => $password  // el modelo la cifra (cast hashed),
            ]);
        }

        $user->assignRole($rol);

        $this->info("Cuenta {$correo} con rol {$rol}.");
        if ($password) {
            $this->warn("Contraseña temporal: {$password}  (cámbiala al entrar)");
        }
        $this->line('Al iniciar sesión se le pedirá activar la verificación en dos pasos.');

        return self::SUCCESS;
    }
}
