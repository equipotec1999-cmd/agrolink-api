<?php

namespace Tests;

use App\Models\Listing;
use App\Models\ProductType;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;

/** Base de las pruebas de la API: base limpia + catálogo y permisos sembrados. */
abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RolesAndPermissionsSeeder::class, CatalogSeeder::class]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** Usuario normal, o con un rol (vendedor / moderador / administrador). */
    protected function makeUser(?string $role = null, bool $twoFactor = false): User
    {
        $user = User::factory()->create();
        if ($role) {
            $user->assignRole($role);
        }
        if ($twoFactor) {
            $user->forceFill(['dos_factores_confirmado_en' => now()])->save();
        }

        return $user->fresh();
    }

    /** Autentica con sesión completa (habilidad `*`). */
    protected function as(User $user): static
    {
        Sanctum::actingAs($user, ['*']);

        return $this;
    }

    protected function productType(): ProductType
    {
        return ProductType::query()->firstOrFail();
    }

    protected function makeListing(User $owner, array $overrides = []): Listing
    {
        return Listing::create($overrides + [
            'usuario_id' => $owner->id,
            'tipo_producto_id' => $this->productType()->id,
            'titulo' => 'Borregos de engorda',
            'slug' => 'borregos-'.uniqid(),
            'descripcion' => 'Lote de prueba',
            'precio' => 1500,
            'tipo_precio' => 'per_animal',
            'cantidad' => 10,
            'unidad' => 'cabezas',
            'modalidad_venta' => 'individual',
            'negociable' => true,
            'estatus' => 'published',
            'estatus_moderacion' => 'pending',
            'publicado_en' => now(),
            'vence_en' => now()->addDays(30),
        ]);
    }
}
