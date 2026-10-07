<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\ApiTestCase;

class ContactVerificationTest extends ApiTestCase
{
    public function test_registrarse_con_correo_envia_codigo_y_no_entrega_token(): void
    {
        Mail::fake();
        $response = $this->postJson('/api/register', [
            'name' => 'Ana',
            'email' => 'ana@ejemplo.com',
            'password' => 'ClaveSegura123',
            'password_confirmation' => 'ClaveSegura123',
        ])->assertCreated()
          ->assertJsonMissing(['token'])
          ->assertJsonPath('verification.channel', 'email')
          ->assertJsonPath('verification.destination', 'ana@ejemplo.com');

        $this->assertDatabaseHas('codigos_verificacion_contacto', [
            'destino' => 'ana@ejemplo.com',
            'canal' => 'email',
        ]);
    }

    public function test_registrarse_falla_sin_correo(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Luis',
            'phone' => '+52 999 123 4567',
            'password' => 'ClaveSegura123',
            'password_confirmation' => 'ClaveSegura123',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_verify_contact_marca_verificado_y_entrega_token(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Ana',
            'email' => 'ana@ejemplo.com',
            'password' => 'ClaveSegura123',
            'password_confirmation' => 'ClaveSegura123',
        ])->assertCreated();

        // El código real está hasheado; forzamos uno conocido para la prueba.
        $knownCode = '123456';
        DB::table('codigos_verificacion_contacto')->update([
            'codigo_hash' => Hash::make($knownCode),
            'intentos' => 0,
        ]);

        $this->postJson('/api/verify-contact', [
            'destination' => 'ana@ejemplo.com',
            'code' => $knownCode,
            'device_name' => 'test',
        ])->assertOk()
          ->assertJsonPath('verified_channel', 'email')
          ->assertJsonStructure(['token', 'user']);

        $this->assertNotNull(User::where('correo', 'ana@ejemplo.com')->first()->correo_verificado_en);
    }

    public function test_verify_contact_rechaza_codigo_incorrecto(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Ana',
            'email' => 'ana@ejemplo.com',
            'password' => 'ClaveSegura123',
            'password_confirmation' => 'ClaveSegura123',
        ])->assertCreated();

        $this->postJson('/api/verify-contact', [
            'destination' => 'ana@ejemplo.com',
            'code' => '000000',
        ])->assertStatus(422);
    }
}
