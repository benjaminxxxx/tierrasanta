<?php

use App\Constants\Permisos;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Recuperación de cochinilla de infestadores.
 *
 * - cochinilla_ingreso_origenes: un retorno de infestadores puede venir de varios campos de origen (un campo se infesta
 *   con mamá de 1 a 3 campos). Cada fila = kg de ese retorno que son de un campo/campaña de origen. Se sugiere por % de
 *   kg de mamá aportados y se puede corregir. El ingreso conserva campo_origen/campania_origen_id = el origen con más kg.
 * - cochinilla_recuperaciones: una "tanda" de infestación de un campo (días seguidos) marcada como recuperada
 *   (ya no se esperan más infestadores). Mientras no exista, la tanda figura como "por recuperar".
 *
 * Los ingresos con un solo origen ya registrados se copian como una fila con todos sus kg (así nada cambia).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('cochinilla_ingreso_origenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cochinilla_ingreso_id')->constrained('cochinilla_ingresos')->cascadeOnDelete();
            $table->string('campo_origen');
            $table->foreignId('campania_origen_id')->nullable()->constrained('campos_campanias')->nullOnDelete();
            $table->decimal('kg', 12, 2);
            $table->decimal('kg_madres', 12, 2)->nullable()->comment('Kg de mamá que aportó ese campo a la infestación');
            $table->decimal('porcentaje_sugerido', 7, 4)->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['cochinilla_ingreso_id', 'campo_origen'], 'coch_ing_origen_unico');
        });

        Schema::create('cochinilla_recuperaciones', function (Blueprint $table) {
            $table->id();
            $table->string('campo')->comment('Campo infestado (donde se recogen los infestadores)');
            $table->date('tanda_inicio');
            $table->date('tanda_fin');
            $table->foreignId('campo_campania_id')->nullable()->constrained('campos_campanias')->nullOnDelete();
            $table->decimal('kg_madres', 12, 2)->default(0);
            $table->decimal('kg_recuperados', 12, 2)->default(0)->comment('Al cerrar');
            $table->text('nota')->nullable();
            $table->foreignId('cerrada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cerrada_en')->nullable();
            $table->timestamps();
            $table->unique(['campo', 'tanda_inicio']);
        });

        // Trazabilidad del ingreso: quién lo registró y quién lo editó por última vez (el motivo y el detalle van a
        // auditorias). Los registros anteriores quedan sin dato.
        Schema::table('cochinilla_ingresos', function (Blueprint $table) {
            $table->foreignId('creado_por')->nullable()->after('stock_disponible')->constrained('users')->nullOnDelete();
            $table->foreignId('editado_por')->nullable()->after('creado_por')->constrained('users')->nullOnDelete();
        });

        // Ingresos de infestadores ya registrados: su único origen pasa a ser una fila con todos sus kg
        DB::statement("INSERT INTO cochinilla_ingreso_origenes (cochinilla_ingreso_id, campo_origen, campania_origen_id, kg, created_at, updated_at)
            SELECT id, campo_origen, campania_origen_id, COALESCE(total_kilos, 0), NOW(), NOW()
            FROM cochinilla_ingresos
            WHERE observacion LIKE 'infestador%' AND campo_origen IS NOT NULL AND campo_origen <> ''");

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        foreach ([Permisos::COCHINILLA_RECUPERACION, Permisos::COCHINILLA_RECUPERACION_GESTIONAR] as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
            Role::where('name', 'Administrador')->where('guard_name', 'web')->first()?->givePermissionTo($permiso);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cochinilla_recuperaciones');
        Schema::dropIfExists('cochinilla_ingreso_origenes');
        Schema::table('cochinilla_ingresos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('creado_por');
            $table->dropConstrainedForeignId('editado_por');
        });
        Permission::whereIn('name', [Permisos::COCHINILLA_RECUPERACION, Permisos::COCHINILLA_RECUPERACION_GESTIONAR])->delete();
    }
};
