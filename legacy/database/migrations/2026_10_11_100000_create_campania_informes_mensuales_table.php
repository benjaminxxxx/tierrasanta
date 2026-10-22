<?php

use App\Constants\Permisos;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Informe mensual de campaña: al cierre de cada mes, foto de la campaña desde su inicio hasta el fin de mes (o hasta
 * su fecha de fin si cierra ese mes). Se guarda congelado (datos) para que el Excel y la vista no cambien solos; se
 * puede regenerar y cada regeneración sube la versión.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('campania_informes_mensuales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campo_campania_id')->constrained('campos_campanias');
            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('mes');
            $table->date('fecha_desde');
            $table->date('fecha_corte');
            $table->boolean('es_cierre')->default(false)->comment('La campaña termina este mes: corte en su fecha de fin');
            $table->unsignedInteger('version')->default(1);
            $table->longText('datos');
            $table->json('advertencias')->nullable();
            $table->decimal('total_soles', 14, 2)->default(0);
            $table->decimal('total_usd_ha', 14, 2)->nullable();
            $table->foreignId('generado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['campo_campania_id', 'anio', 'mes']);
            $table->index(['anio', 'mes']);
        });

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        foreach ([Permisos::CAMPAÑA_INFORME_MENSUAL, Permisos::CAMPAÑA_INFORME_MENSUAL_GESTIONAR] as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
            Role::where('name', 'Administrador')->where('guard_name', 'web')->first()?->givePermissionTo($permiso);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('campania_informes_mensuales');
        Permission::whereIn('name', [Permisos::CAMPAÑA_INFORME_MENSUAL, Permisos::CAMPAÑA_INFORME_MENSUAL_GESTIONAR])->delete();
    }
};
