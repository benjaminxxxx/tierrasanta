<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Evaluación de brotes con 4° piso (campos con plantas de 4 pisos, desde oct-2026). Las evaluaciones anteriores
 * quedan con el 4° piso vacío (no había) y sus totales no cambian.
 *
 * Nombres: "_despues_n_dias" se mantiene por compatibilidad, pero el dato es "brotes del piso PARA ELIMINAR"
 * (siempre se midió así; solo cambió el nombre en pantalla). El total "2y3piso" de la campaña ahora suma 2° + 3° + 4°.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eval_brotes_por_piso_detalles', function (Blueprint $table) {
            if (!Schema::hasColumn('eval_brotes_por_piso_detalles', 'brotes_aptos_4p_actual')) {
                $table->integer('brotes_aptos_4p_actual')->nullable()->after('brotes_aptos_3p_despues_n_dias');
            }
            if (!Schema::hasColumn('eval_brotes_por_piso_detalles', 'brotes_aptos_4p_despues_n_dias')) {
                $table->integer('brotes_aptos_4p_despues_n_dias')->nullable()->after('brotes_aptos_4p_actual');
            }
        });
        Schema::table('campos_campanias', function (Blueprint $table) {
            if (!Schema::hasColumn('campos_campanias', 'brotexpiso_actual_brotes_4piso')) {
                $table->integer('brotexpiso_actual_brotes_4piso')->nullable()->after('brotexpiso_brotes_3piso_n_dias');
            }
            if (!Schema::hasColumn('campos_campanias', 'brotexpiso_brotes_4piso_n_dias')) {
                $table->integer('brotexpiso_brotes_4piso_n_dias')->nullable()->after('brotexpiso_actual_brotes_4piso');
            }
        });
    }

    public function down(): void
    {
        Schema::table('eval_brotes_por_piso_detalles', fn(Blueprint $t) => $t->dropColumn(['brotes_aptos_4p_actual', 'brotes_aptos_4p_despues_n_dias']));
        Schema::table('campos_campanias', fn(Blueprint $t) => $t->dropColumn(['brotexpiso_actual_brotes_4piso', 'brotexpiso_brotes_4piso_n_dias']));
    }
};
