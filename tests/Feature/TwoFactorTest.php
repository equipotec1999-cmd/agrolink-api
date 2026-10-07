<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Totp;
use Tests\ApiTestCase;

class TwoFactorTest extends ApiTestCase
{
    private function step(int $offset = 0): int
    {
        return intdiv(time(), 30) + $offset;
    }

    /** Activa la verificación para el usuario autenticado y devuelve [secreto, códigos de recuperación]. */
    private function enable(User $user): array
    {
        $secret = $this->as($user)->postJson('/api/two-factor/setup')->assertOk()->json('data.secret');
        $codes = $this->postJson('/api/two-factor/confirm', ['code' => Totp::code($secret, $this->step())])
            ->assertOk()->json('data.recovery_codes');

        return [$secret, $codes];
    }

    public function test_activacion_entrega_ocho_codigos_de_recuperacion(): void
    {
        $user = $this->makeUser();
        [, $codes] = $this->enable($user);

        $this->assertCount(8, $codes);
        $this->assertNotNull($user->fresh()->dos_factores_confirmado_en);
        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());
    }

    public function test_confirmar_con_codigo_incorrecto_no_activa(): void
    {
        $user = $this->makeUser();
        $this->as($user)->postJson('/api/two-factor/setup')->assertOk();

        $this->postJson('/api/two-factor/confirm', ['code' => '000000'])->assertUnprocessable();
        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());
    }

    public function test_login_con_2fa_da_token_pendiente_que_no_sirve_para_nada_mas(): void
    {
        $user = $this->makeUser();
        [$secret] = $this->enable($user);
        $user->tokens()->delete();

        $login = $this->postJson('/api/login', ['email' => $user->correo, 'password' => 'password', 'device_name' => 't'])
            ->assertOk()->assertJsonPath('requires_two_factor', true);
        $this->assertArrayNotHasKey('token', $login->json());

        $pending = $login->json('challenge_token');
        $this->withToken($pending)->getJson('/api/me')->assertForbidden();

        // El código ya usado al activar no sirve de nuevo; el del siguiente intervalo sí.
        $this->withToken($pending)->postJson('/api/two-factor/challenge', ['code' => Totp::code($secret, $this->step())])
            ->assertUnprocessable();

        $full = $this->withToken($pending)->postJson('/api/two-factor/challenge', ['code' => Totp::code($secret, $this->step(1))])
            ->assertOk()->json('token');

        $this->app['auth']->forgetGuards();
        $this->withToken($full)->getJson('/api/me')->assertOk();
    }

    public function test_codigo_de_recuperacion_sirve_una_sola_vez(): void
    {
        $user = $this->makeUser();
        [, $codes] = $this->enable($user);
        $user->tokens()->delete();

        $login = fn () => $this->postJson('/api/login', ['email' => $user->correo, 'password' => 'password', 'device_name' => 't'])
            ->json('challenge_token');

        $this->withToken($login())->postJson('/api/two-factor/challenge', ['recovery_code' => $codes[0]])->assertOk();

        $this->app['auth']->forgetGuards();
        $this->withToken($login())->postJson('/api/two-factor/challenge', ['recovery_code' => $codes[0]])->assertUnprocessable();
    }

    public function test_cuenta_administrativa_sin_2fa_no_entra_a_rutas_protegidas(): void
    {
        $admin = $this->makeUser('administrador');

        $this->as($admin)->getJson('/api/moderation/listings')
            ->assertForbidden()->assertJsonPath('code', 'two_factor_setup_required');

        $this->getJson('/api/admin/compliance-rules')->assertForbidden();
    }

    public function test_cuenta_administrativa_con_2fa_activa_no_puede_desactivarla(): void
    {
        $admin = $this->makeUser('administrador');
        [$secret] = $this->enable($admin);

        $this->postJson('/api/two-factor/disable', [
            'password' => 'password',
            'code' => Totp::code($secret, $this->step(1)),
        ])->assertForbidden();

        $this->assertTrue($admin->fresh()->hasTwoFactorEnabled());
    }

    public function test_usuario_normal_desactiva_con_contrasena_y_codigo(): void
    {
        $user = $this->makeUser();
        [$secret] = $this->enable($user);

        $this->postJson('/api/two-factor/disable', ['password' => 'mala', 'code' => Totp::code($secret, $this->step(1))])
            ->assertUnprocessable();

        $this->postJson('/api/two-factor/disable', ['password' => 'password', 'code' => Totp::code($secret, $this->step(1))])
            ->assertNoContent();

        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());
    }
}
