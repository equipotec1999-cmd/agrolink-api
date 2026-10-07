<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            CatalogSeeder::class,
        ]);

        // Usuario de prueba para desarrollo local; en producción esto no se corre.
        if (! User::where('correo', 'test@example.com')->exists()) {
            User::factory()->create([
                'nombre' => 'Usuario de prueba',
                'correo' => 'test@example.com',
            ])->assignRole('vendedor');
        }
    }
}
