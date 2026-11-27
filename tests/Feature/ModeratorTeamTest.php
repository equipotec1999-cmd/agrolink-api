<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\ApiTestCase;

class ModeratorTeamTest extends ApiTestCase
{
    private function owner(): User
    {
        $u = $this->makeUser('moderador', twoFactor: true);
        $u->forceFill(['correo' => 'ramirez12354cr@gmail.com'])->save();

        return $u->fresh();
    }

    public function test_solo_la_duena_ve_la_lista(): void
    {
        $owner = $this->owner();
        $this->as($owner)->getJson('/api/me')->assertJsonPath('data.is_owner', true);
        $this->getJson('/api/team/moderators')->assertOk()->assertJsonFragment(['email' => 'ramirez12354cr@gmail.com']);

        $otherMod = $this->makeUser('moderador', twoFactor: true);
        $this->as($otherMod)->getJson('/api/me')->assertJsonPath('data.is_owner', false);
        $this->getJson('/api/team/moderators')->assertForbidden();
        $this->as($this->makeUser())->getJson('/api/team/moderators')->assertForbidden();
    }

    public function test_agregar_con_correo_y_codigo_de_su_pantalla(): void
    {
        $owner = $this->owner();
        $candidate = $this->makeUser();

        $code = $this->as($candidate)->postJson('/api/me/moderator-invite-code')->assertOk()->json('code');
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        $wrong = $code === '000000' ? '111111' : '000000';
        $this->as($owner)->postJson('/api/team/moderators', ['email' => $candidate->correo, 'code' => $wrong])
            ->assertUnprocessable();
        $this->postJson('/api/team/moderators', ['email' => strtoupper($candidate->correo), 'code' => $code])
            ->assertCreated();

        $this->assertTrue($candidate->fresh()->hasRole('moderador'));
        // El código ya se usó.
        $this->postJson('/api/team/moderators', ['email' => $candidate->correo, 'code' => $code])->assertUnprocessable();
    }

    public function test_sin_codigo_no_se_agrega_a_nadie(): void
    {
        $owner = $this->owner();
        $candidate = $this->makeUser();
        $this->as($owner)->postJson('/api/team/moderators', ['email' => $candidate->correo, 'code' => '123456'])
            ->assertUnprocessable();
        $this->assertFalse($candidate->fresh()->hasRole('moderador'));

        // Un moderador que no es la dueña tampoco puede agregar.
        $code = $this->as($candidate)->postJson('/api/me/moderator-invite-code')->json('code');
        $this->as($this->makeUser('moderador', twoFactor: true))
            ->postJson('/api/team/moderators', ['email' => $candidate->correo, 'code' => $code])->assertForbidden();
    }

    public function test_quitar_moderador_pero_no_a_la_duena(): void
    {
        $owner = $this->owner();
        $mod = $this->makeUser('moderador', twoFactor: true);

        $this->as($owner)->deleteJson("/api/team/moderators/{$mod->id}")->assertOk();
        $this->assertFalse($mod->fresh()->hasRole('moderador'));
        $this->deleteJson("/api/team/moderators/{$owner->id}")->assertUnprocessable();
    }
}
