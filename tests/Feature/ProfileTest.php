<?php

namespace Tests\Feature;

use App\Models\Profile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\ApiTestCase;

class ProfileTest extends ApiTestCase
{
    public function test_registrarse_con_apellidos_lo_guarda(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Juan',
            'lastname' => 'Pérez López',
            'email' => 'juan@ejemplo.com',
            'password' => 'ClaveSegura123',
            'password_confirmation' => 'ClaveSegura123',
            'device_name' => 'test',
        ])->assertCreated()
          ->assertJsonPath('user.lastname', 'Pérez López')
          ->assertJsonPath('user.full_name', 'Juan Pérez López');
    }

    public function test_actualizar_perfil_guarda_bio_estado_municipio(): void
    {
        $user = $this->makeUser();
        Profile::firstOrCreate(['usuario_id' => $user->id]);
        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/me', [
                'name' => 'Ana María',
                'lastname' => 'Caamal',
                'bio' => 'Vendedora de miel desde 2015.',
                'state' => 'Yucatán',
                'municipality' => 'Tizimín',
            ])
            ->assertOk()
            ->assertJsonPath('data.profile.bio', 'Vendedora de miel desde 2015.')
            ->assertJsonPath('data.profile.municipality', 'Tizimín');

        $this->assertDatabaseHas('perfiles', ['usuario_id' => $user->id, 'municipio' => 'Tizimín']);
    }

    public function test_subir_avatar_devuelve_url(): void
    {
        Storage::fake('local');
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/me/avatar', ['avatar' => UploadedFile::fake()->image('yo.jpg', 300, 300)])
            ->assertOk()
            ->assertJsonPath('data.profile.avatar_url', fn ($url) => is_string($url) && str_contains($url, 'avatares'));
    }

    public function test_subir_avatar_rechaza_archivos_no_imagen(): void
    {
        Storage::fake('local');
        $user = $this->makeUser();
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/me/avatar', ['avatar' => UploadedFile::fake()->create('malware.pdf', 20)])
            ->assertStatus(422);
    }
}
