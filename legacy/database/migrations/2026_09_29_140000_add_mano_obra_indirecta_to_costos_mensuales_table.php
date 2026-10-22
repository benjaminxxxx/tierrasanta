<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mano de obra indirecta de planilla (feriados, descansos médicos, licencias con goce,
 * vacaciones pagadas, bono de asistencia): parte de lo pagado que no tiene trabajo en campo.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('costos_mensuales', function (Blueprint $table) {
            $table->decimal('costo_mano_obra_indirecta', 12, 2)->nullable()->after('costo_planilla_calculado');
        });
    }

    public function down(): void
    {
        Schema::table('costos_mensuales', function (Blueprint $table) {
            $table->dropColumn('costo_mano_obra_indirecta');
        });
    }
};
