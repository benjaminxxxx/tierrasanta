<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Servicios externos en campo: pagado (tabla servicios_campo_detalles) vs.
     * calculado (resumen_costo_diarios con origen_tipo = servicio_campo).
     */
    public function up(): void
    {
        Schema::table('costos_mensuales', function (Blueprint $table) {
            $table->decimal('costo_servicio_campo', 12, 2)->nullable()->default(0)->after('costo_fertilizante');
            $table->decimal('costo_servicio_campo_calculado', 12, 2)->nullable()->default(0)->after('costo_fertilizante_calculado');
        });
    }

    public function down(): void
    {
        Schema::table('costos_mensuales', function (Blueprint $table) {
            $table->dropColumn(['costo_servicio_campo', 'costo_servicio_campo_calculado']);
        });
    }
};
