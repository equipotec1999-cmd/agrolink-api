<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\ApiTestCase;

class AuthTest extends ApiTestCase
{
    private function register(array $over = [])
    {
        return $this->postJson('/api/register', $over + [
            'name' => 'Ana Prueba',
            'email' => 'ana@example.com',
            'password' => 'Secreta123',
            'password_confirmation' => 'Secreta123',
            'accept_terms' => true,
        ]);
    }

    /** El registro deja la cuenta pendiente; esta prueba la verifica con un código conocido. */
    private function registerAndVerify(array $over = []): string
    {
        $email = $over['email'] ?? 'ana@example.com';
        $this->register($over)->assertCreated();
        DB::table('codigos_verificacion_contacto')->update(['codigo_hash' => Hash::make('654321'), 'intentos' => 0]);
        $token = $this->postJson('/api/verify-contact', ['destination' => $email, 'code' => '654321', 'device_name' => 'test'])
            ->assertOk()->json('token');

        return $token;
    }

    public function test_registro_crea_usuario_pendiente_de_verificacion(): void
    {
        $this->register()
            ->assertCreated()
            ->assertJsonPath('verification.channel', 'email')
            ->assertJsonMissing(['token']);

        $this->assertDatabaseHas('usuarios', ['correo' => 'ana@example.com']);
    }

    public function test_registro_rechaza_contrasena_debil_y_correo_repetido(): void
    {
        $this->register(['password' => 'abc', 'password_confirmation' => 'abc'])->assertUnprocessable();

        // Ya confirmado: el correo queda ocupado (sin importar mayúsculas).
        $this->registerAndVerify();
        $this->register()->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->register(['email' => 'ANA@Example.com'])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_un_registro_sin_confirmar_no_aparta_el_correo(): void
    {
        $this->register(['phone' => '9991234567'])->assertCreated();
        // Se vuelve a registrar con el mismo correo y teléfono: reemplaza al pendiente.
        $this->register(['name' => 'Ana Otra', 'phone' => '9991234567'])->assertCreated();

        $this->assertSame(1, User::where('correo', 'ana@example.com')->count());
        $this->assertSame('Ana Otra', User::where('correo', 'ana@example.com')->value('nombre'));
    }

    public function test_el_correo_se_guarda_en_minusculas(): void
    {
        $this->register(['email' => '  Ana@Example.COM '])->assertCreated()
            ->assertJsonPath('verification.destination', 'ana@example.com');
    }

    public function test_login_sin_correo_confirmado_pide_el_codigo_y_no_da_sesion(): void
    {
        $this->register()->assertCreated();
        DB::table('codigos_verificacion_contacto')->delete();

        $this->postJson('/api/login', ['email' => 'ana@example.com', 'password' => 'Secreta123', 'device_name' => 'test'])
            ->assertOk()
            ->assertJsonPath('requires_verification', true)
            ->assertJsonPath('verification.destination', 'ana@example.com')
            ->assertJsonMissing(['token']);

        // Se mandó un código nuevo para confirmar desde la pantalla de login.
        $this->assertDatabaseHas('codigos_verificacion_contacto', ['destino' => 'ana@example.com', 'canal' => 'email']);
    }

    public function test_el_correo_de_codigo_lleva_la_plantilla_de_la_marca(): void
    {
        $html = app(\App\Services\ContactVerificationService::class)->renderHtml(
            'Confirma tu correo — AgroLink', 'texto', ['code' => '482915', 'title' => 'Confirma tu correo'],
        );
        $this->assertStringContainsString('Confirma tu correo', $html);
        $this->assertStringContainsString('El mercado del campo', $html);
        foreach (str_split('482915') as $d) {
            $this->assertStringContainsString(">{$d}</div>", $html);
        }
    }

    public function test_login_correcto_e_incorrecto(): void
    {
        $this->registerAndVerify();

        $this->postJson('/api/login', ['email' => 'ana@example.com', 'password' => 'Secreta123', 'device_name' => 'test'])
            ->assertOk()->assertJsonStructure(['user', 'token']);

        $this->postJson('/api/login', ['email' => 'ana@example.com', 'password' => 'otra', 'device_name' => 'test'])
            ->assertUnprocessable();
    }

    public function test_me_exige_sesion_y_logout_revoca_el_token(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();

        $token = $this->registerAndVerify();
        $this->withToken($token)->getJson('/api/me')->assertOk()->assertJsonPath('data.email', 'ana@example.com');

        $this->withToken($token)->postJson('/api/logout')->assertOk();
        $this->assertSame(0, User::first()->tokens()->count());
    }

    public function test_cambiar_contrasena_cierra_las_otras_sesiones(): void
    {
        $this->register()->assertCreated();
        $user = User::first();
        $user->tokens()->delete();
        $otro = $user->createToken('otro-telefono');
        $actual = $user->createToken('este-telefono');

        $this->withToken($actual->plainTextToken)->postJson('/api/me/password', [
            'current_password' => 'Secreta123',
            'password' => 'Nueva12345',
            'password_confirmation' => 'Nueva12345',
        ])->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $otro->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $actual->accessToken->id]);

        $this->postJson('/api/login', ['email' => 'ana@example.com', 'password' => 'Nueva12345', 'device_name' => 't'])->assertOk();
    }

    public function test_cambiar_contrasena_exige_la_actual(): void
    {
        $this->register()->assertCreated();
        $token = User::first()->createToken('x')->plainTextToken;

        $this->withToken($token)->postJson('/api/me/password', [
            'current_password' => 'incorrecta',
            'password' => 'Nueva12345',
            'password_confirmation' => 'Nueva12345',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');
    }

    public function test_editar_perfil(): void
    {
        $user = $this->makeUser();

        $this->as($user)->patchJson('/api/me', ['name' => 'Nombre Nuevo', 'phone' => '9861234567'])
            ->assertOk()->assertJsonPath('data.name', 'Nombre Nuevo');

        $this->assertDatabaseHas('usuarios', ['id' => $user->id, 'nombre' => 'Nombre Nuevo', 'telefono' => '9861234567']);
    }

    public function test_estadisticas_del_perfil_son_reales(): void
    {
        $seller = $this->makeUser('vendedor');
        $buyer = $this->makeUser();
        $this->makeListing($seller);
        $this->makeListing($seller, ['estatus' => 'borrador']);

        $this->as($seller)->getJson('/api/me/stats')->assertOk()
            ->assertJsonPath('data.listings', 1)
            ->assertJsonPath('data.sales', 0)
            ->assertJsonPath('data.purchases', 0)
            ->assertJsonPath('data.rating', null);

        $listing = $this->makeListing($seller);
        $id = $this->as($buyer)->postJson("/api/listings/{$listing->id}/conversation")->json('data.id');
        $this->postJson("/api/conversations/$id/offers", ['amount' => 100, 'quantity' => 1])->assertCreated();
        $this->as($seller)->postJson('/api/offers/'.\App\Models\Offer::firstOrFail()->id.'/accept')->assertOk();

        $this->getJson('/api/me/stats')->assertJsonPath('data.sales', 1)->assertJsonPath('data.listings', 2);
        $this->as($buyer)->getJson('/api/me/stats')->assertJsonPath('data.purchases', 1)->assertJsonPath('data.sales', 0);
    }
}
