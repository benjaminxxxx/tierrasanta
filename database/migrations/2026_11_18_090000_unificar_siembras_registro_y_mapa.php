<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de siembras = siembras del mapa de etapas (una sola fuente, 18/11/2026):
 *  - siembras.tipo: siembra (plantación del campo) | relleno (otra siembra dentro de una campaña).
 *  - siembras.origen: registro (a mano en Campo → Siembras) | deteccion (labores de siembra) | tarjeta | mapa | horas;
 *    motivo y detalle (labores, jornales) explican por qué existe.
 *  - siembras.campo_campania_id: campaña del relleno (o de la siembra con la que empezó).
 *  - campania_sectores.siembra_id: cada siembra del mapa apunta a su registro.
 * Al final se vuelven a detectar los mapas armados por el sistema y se enlazan todos con el registro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siembras', function (Blueprint $table) {
            if (!Schema::hasColumn('siembras', 'tipo')) {
                $table->string('tipo', 10)->default('siembra')->after('fecha_siembra');
            }
            if (!Schema::hasColumn('siembras', 'origen')) {
                $table->string('origen', 20)->default('registro')->after('tipo');
            }
            if (!Schema::hasColumn('siembras', 'motivo')) {
                $table->string('motivo', 255)->nullable()->after('origen');
            }
            if (!Schema::hasColumn('siembras', 'detalle')) {
                $table->json('detalle')->nullable()->after('motivo');
            }
            if (!Schema::hasColumn('siembras', 'campo_campania_id')) {
                $table->foreignId('campo_campania_id')->nullable()->after('campo_nombre')->constrained('campos_campanias')->nullOnDelete();
            }
        });
        if (!Schema::hasColumn('campania_sectores', 'siembra_id')) {
            Schema::table('campania_sectores', function (Blueprint $table) {
                $table->foreignId('siembra_id')->nullable()->after('fecha_siembra')->constrained('siembras')->nullOnDelete();
            });
        }
        Artisan::call('campania:detectar-sectores');
        Artisan::call('campania:sincronizar-siembras');
    }

    public function down(): void
    {
        if (Schema::hasColumn('campania_sectores', 'siembra_id')) {
            Schema::table('campania_sectores', function (Blueprint $table) {
                $table->dropConstrainedForeignId('siembra_id');
            });
        }
        Schema::table('siembras', function (Blueprint $table) {
            if (Schema::hasColumn('siembras', 'campo_campania_id')) {
                $table->dropConstrainedForeignId('campo_campania_id');
            }
            foreach (['tipo', 'origen', 'motivo', 'detalle'] as $col) {
                if (Schema::hasColumn('siembras', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
