<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\ApiTestCase;

class PasswordResetTest extends ApiTestCase
{
    public function test_forgot_responde_200_aunque_el_correo_no_exista(): void
    {
        $this->postJson('/api/forgot-password', ['email' => 'nadie@correo.com'])->assertOk();
    }

    public function test_reset_cambia_la_contrasena_con_el_codigo_correcto(): void
    {
        $user = User::factory()->create(['correo' => 'ramirez12354cr@gmail.com']);

        $this->postJson('/api/forgot-password', ['email' => $user->correo])->assertOk();

        // Reemplaza el hash por uno conocido para probar sin leer el correo.
        DB::table('codigos_verificacion_contacto')
            ->where('usuario_id', $user->id)
            ->where('canal', 'password_reset')
            ->update(['codigo_hash' => Hash::make('123456')]);

        $this->postJson('/api/reset-password', [
            'email' => $user->correo,
            'code' => '123456',
            'password' => 'NuevaClave1',
        ])->assertOk();

        $this->postJson('/api/login', [
            'email' => $user->correo,
            'password' => 'NuevaClave1',
            'device_name' => 'test',
        ])->assertOk();
    }

    public function test_codigo_incorrecto_devuelve_422(): void
    {
        $user = User::factory()->create();
        $this->postJson('/api/forgot-password', ['email' => $user->correo])->assertOk();

        $this->postJson('/api/reset-password', [
            'email' => $user->correo,
            'code' => '000000',
            'password' => 'NuevaClave1',
        ])->assertUnprocessable();
    }

    public function test_el_codigo_llega_aunque_la_cuenta_tenga_mayusculas(): void
    {
        $user = User::factory()->create(['correo' => 'Ana.Campo@Gmail.com']);

        $this->postJson('/api/forgot-password', ['email' => 'ana.campo@gmail.com'])->assertOk();

        $this->assertDatabaseHas('codigos_verificacion_contacto', ['usuario_id' => $user->id, 'canal' => 'password_reset']);
    }

    public function test_prueba_de_correo_solo_para_moderacion_y_dice_por_que_falla(): void
    {
        config(['services.brevo.key' => null, 'services.resend.key' => null]);

        $this->as($this->makeUser())->postJson('/api/moderation/mail-test')->assertForbidden();
        $this->as($this->makeUser('moderador'))->postJson('/api/moderation/mail-test')
            ->assertOk()
            ->assertJsonPath('sent', false)
            ->assertJsonPath('error', fn ($e) => str_contains($e, 'BREVO_API_KEY'));
    }
}
