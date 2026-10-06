<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tablas de spatie/laravel-permission v6 (teams desactivado, ver config/permission.php).
// Con guardas hasTable porque en bases locales previas pudieron crearse a mano.
return new class extends Migration
{
    public function up(): void
    {
        $t = config('permission.table_names');
        $pivotRole = config('permission.column_names.role_pivot_key') ?: 'role_id';
        $pivotPermission = config('permission.column_names.permission_pivot_key') ?: 'permission_id';
        $morphKey = config('permission.column_names.model_morph_key');

        if (! Schema::hasTable($t['permissions'])) {
            Schema::create($t['permissions'], function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }

        if (! Schema::hasTable($t['roles'])) {
            Schema::create($t['roles'], function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }

        if (! Schema::hasTable($t['model_has_permissions'])) {
            Schema::create($t['model_has_permissions'], function (Blueprint $table) use ($t, $pivotPermission, $morphKey) {
                $table->unsignedBigInteger($pivotPermission);
                $table->string('model_type');
                $table->unsignedBigInteger($morphKey);
                $table->index([$morphKey, 'model_type'], 'model_has_permissions_model_id_model_type_index');
                $table->foreign($pivotPermission)->references('id')->on($t['permissions'])->onDelete('cascade');
                $table->primary([$pivotPermission, $morphKey, 'model_type'], 'model_has_permissions_permission_model_type_primary');
            });
        }

        if (! Schema::hasTable($t['model_has_roles'])) {
            Schema::create($t['model_has_roles'], function (Blueprint $table) use ($t, $pivotRole, $morphKey) {
                $table->unsignedBigInteger($pivotRole);
                $table->string('model_type');
                $table->unsignedBigInteger($morphKey);
                $table->index([$morphKey, 'model_type'], 'model_has_roles_model_id_model_type_index');
                $table->foreign($pivotRole)->references('id')->on($t['roles'])->onDelete('cascade');
                $table->primary([$pivotRole, $morphKey, 'model_type'], 'model_has_roles_role_model_type_primary');
            });
        }

        if (! Schema::hasTable($t['role_has_permissions'])) {
            Schema::create($t['role_has_permissions'], function (Blueprint $table) use ($t, $pivotRole, $pivotPermission) {
                $table->unsignedBigInteger($pivotPermission);
                $table->unsignedBigInteger($pivotRole);
                $table->foreign($pivotPermission)->references('id')->on($t['permissions'])->onDelete('cascade');
                $table->foreign($pivotRole)->references('id')->on($t['roles'])->onDelete('cascade');
                $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
            });
        }

        app('cache')->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        $t = config('permission.table_names');
        Schema::dropIfExists($t['role_has_permissions']);
        Schema::dropIfExists($t['model_has_roles']);
        Schema::dropIfExists($t['model_has_permissions']);
        Schema::dropIfExists($t['roles']);
        Schema::dropIfExists($t['permissions']);
    }
};
