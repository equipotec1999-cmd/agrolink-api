<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * RBAC por PERMISOS, no por rol fijo (Fase 1 §7, regla §20: nunca `if user.role ==
 * 'admin'` en el código). Los Controllers/Policies deben comprobar permisos concretos
 * (p.ej. `$user->can('moderate listings')`), nunca el nombre del rol. Los roles de abajo
 * son solo una forma cómoda de agrupar permisos para asignarlos a un usuario.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'manage own listings',
            'moderate listings',
            'moderate documents',
            'resolve reports',
            'manage compliance rules',
            'manage catalog',
            'manage users',
            'view audit logs',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $vendedor = Role::findOrCreate('vendedor');
        $vendedor->syncPermissions(['manage own listings']);

        $moderador = Role::findOrCreate('moderador');
        $moderador->syncPermissions(['manage own listings', 'moderate listings', 'moderate documents', 'resolve reports']);

        $admin = Role::findOrCreate('administrador');
        $admin->syncPermissions($permissions); // el admin sigue siendo "el rol con todos los permisos", no un caso especial en código.
    }
}
