<?php

use App\Constants\Permisos;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/** Permiso del reporte "Historial laboral" (Reporte y Auditoría), dado al Administrador. */
return new class extends Migration {
    public function up(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::firstOrCreate(['name' => Permisos::REPORTE_HISTORIAL_LABORAL, 'guard_name' => 'web']);
        Role::where('name', 'Administrador')->where('guard_name', 'web')->first()?->givePermissionTo(Permisos::REPORTE_HISTORIAL_LABORAL);
    }

    public function down(): void
    {
        Permission::where('name', Permisos::REPORTE_HISTORIAL_LABORAL)->delete();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
