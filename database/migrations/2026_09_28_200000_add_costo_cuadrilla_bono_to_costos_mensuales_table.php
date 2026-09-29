<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Bonos de cuadrilla que NO se pagan con el jornal (se acumulan y se pagan aparte), separados
     * de "cuadrilla" (jornal + bonos con jornal) para compararlos por su cuenta:
     * pagado = bonos del mes en cuad_bonos_actividades; calculado = 'cuadrilla_bono' del resumen.
     */
    public function up(): void
    {
        Schema::table('costos_mensuales', function (Blueprint $table) {
            $table->decimal('costo_cuadrilla_bono', 12, 2)->nullable()->default(0)->after('costo_cuadrilla');
            $table->decimal('costo_cuadrilla_bono_calculado', 12, 2)->nullable()->default(0)->after('costo_cuadrilla_calculado');
        });
    }

    public function down(): void
    {
        Schema::table('costos_mensuales', function (Blueprint $table) {
            $table->dropColumn(['costo_cuadrilla_bono', 'costo_cuadrilla_bono_calculado']);
        });
    }
};
