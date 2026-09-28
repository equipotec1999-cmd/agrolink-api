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
        if (! User::where('email', 'test@example.com')->exists()) {
            User::factory()->create([
                'name' => 'Usuario de prueba',
                'email' => 'test@example.com',
            ])->assignRole('vendedor');
        }
    }
}
