<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('planilla_blanco_detalles', function (Blueprint $table) {
            $table->decimal('negro_sueldo_por_dia_total', 10, 2)->nullable();
            $table->decimal('negro_sueldo_por_hora_total', 12, 5)->nullable();
            $table->decimal('negro_otros_bonos_acumulados', 10, 2)->nullable();
            $table->decimal('negro_sueldo_final_empleado', 10, 2)->nullable();
            $table->string('esta_jubilado', 10)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('planilla_blanco_detalles', function (Blueprint $table) {
            $table->dropColumn([
                'negro_sueldo_por_dia_total',
                'negro_sueldo_por_hora_total',
                'negro_otros_bonos_acumulados',
                'negro_sueldo_final_empleado',
                'esta_jubilado'
            ]);
        });
    }
};
