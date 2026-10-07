<?php

use App\Constants\Permisos;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/** Permiso de Sistema → Configuración (días de las evaluaciones de brotes y otros parámetros de negocio). */
return new class extends Migration {
    public function up(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::firstOrCreate(['name' => Permisos::SISTEMA_CONFIGURACION, 'guard_name' => 'web']);
        Role::where('name', 'Administrador')->where('guard_name', 'web')->first()?->givePermissionTo(Permisos::SISTEMA_CONFIGURACION);
    }

    public function down(): void
    {
        Permission::where('name', Permisos::SISTEMA_CONFIGURACION)->delete();
    }
};
