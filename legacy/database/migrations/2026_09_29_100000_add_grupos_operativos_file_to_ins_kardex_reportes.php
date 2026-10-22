<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Los reportes de kardex pasan a elegirse por grupo operativo (fertilizante, pesticida, combustible...)
 * en lugar de por categoría, y guardan el Excel generado.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('ins_kardex_reportes', function (Blueprint $table) {
            $table->json('grupos_operativos')->nullable()->after('tipo_kardex');
            $table->string('file')->nullable()->after('grupos_operativos');
            $table->timestamp('generado_at')->nullable()->after('file');
        });

        Schema::table('ins_kardex_reporte_detalles', function (Blueprint $table) {
            $table->string('grupo_operativo', 50)->nullable()->after('ins_kardex_id');
        });

        // Reportes existentes: grupos = los de sus categorías
        foreach (DB::table('ins_kardex_reportes')->pluck('id') as $reporteId) {
            $grupos = DB::table('ins_kardex_reporte_categorias as rc')
                ->join('ins_categorias as c', 'c.codigo', '=', 'rc.categoria_codigo')
                ->where('rc.reporte_id', $reporteId)
                ->whereNotNull('c.grupo_operativo')
                ->distinct()
                ->pluck('c.grupo_operativo')
                ->values()
                ->all();

            DB::table('ins_kardex_reportes')->where('id', $reporteId)
                ->update(['grupos_operativos' => json_encode($grupos)]);
        }
    }

    public function down(): void
    {
        Schema::table('ins_kardex_reporte_detalles', function (Blueprint $table) {
            $table->dropColumn('grupo_operativo');
        });
        Schema::table('ins_kardex_reportes', function (Blueprint $table) {
            $table->dropColumn(['grupos_operativos', 'file', 'generado_at']);
        });
    }
};
