<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cálculo explicado de la mano de obra indirecta (todo lo gastado en FDM): quién y cuándo lo calculó, el Excel y si
 * fue concluyente. Al calcular se reemplazan operativo_mano_obra_indirecta_blanco/negro.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('costos_mensuales', function (Blueprint $table) {
            $table->timestamp('moi_calculado_en')->nullable()->after('operativo_mano_obra_indirecta_negro');
            $table->foreignId('moi_calculado_por')->nullable()->after('moi_calculado_en')->constrained('users')->nullOnDelete();
            $table->string('moi_archivo')->nullable()->after('moi_calculado_por');
            $table->boolean('moi_concluyente')->nullable()->after('moi_archivo');
            $table->json('moi_resumen')->nullable()->after('moi_concluyente');
        });
    }

    public function down(): void
    {
        Schema::table('costos_mensuales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('moi_calculado_por');
            $table->dropColumn(['moi_calculado_en', 'moi_archivo', 'moi_concluyente', 'moi_resumen']);
        });
    }
};
