<?php

namespace Database\Seeders;

use App\Services\Sistema\PermisosServicio;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos del árbol (config/permisos_tree.php) y el rol Super Admin (tiene todo por el Gate de AppServiceProvider).
 * Los demás roles y sus permisos llegan con el Excel de recursos. Se puede volver a ejecutar: solo crea lo que falta.
 */
class PermisosSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        PermisosServicio::sincronizarPermisosEnBD(config('permisos_tree'));
        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
