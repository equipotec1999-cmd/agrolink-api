<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\ApiTestCase;

class ProductionToolsTest extends ApiTestCase
{
    public function test_las_respuestas_llevan_cabeceras_de_seguridad(): void
    {
        $this->getJson('/api/categories')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_crear_admin_crea_cuenta_con_rol_y_contrasena_valida(): void
    {
        $this->artisan('agrolink:crear-admin', ['correo' => 'Admin@Ejemplo.com', '--password' => 'ClaveSegura123'])
            ->assertSuccessful();

        $user = User::where('correo', 'admin@ejemplo.com')->firstOrFail();
        $this->assertTrue($user->hasRole('administrador'));
        $this->assertTrue($user->needsTwoFactor());

        // Debe poder iniciar sesión con esa contraseña (no doble cifrado).
        $this->postJson('/api/login', ['email' => 'admin@ejemplo.com', 'password' => 'ClaveSegura123', 'device_name' => 'test'])
            ->assertOk();
    }

    public function test_crear_admin_rechaza_contrasena_corta_y_rol_invalido(): void
    {
        $this->artisan('agrolink:crear-admin', ['correo' => 'a@b.com', '--password' => 'corta'])->assertFailed();
        $this->artisan('agrolink:crear-admin', ['correo' => 'a@b.com', '--rol' => 'dios'])->assertFailed();
        $this->assertDatabaseMissing('usuarios', ['correo' => 'a@b.com']);
    }

    public function test_preflight_falla_en_entorno_de_pruebas(): void
    {
        $this->artisan('agrolink:preflight')->assertFailed();
    }
}
