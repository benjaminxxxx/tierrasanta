<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Los totales del kardex eran decimal(18,13): 5 enteros, máximo S/ 99,999.99. Con los costos de compra
 * corregidos (2026_10_01_100000) un kardex de UREA llega a S/ 149,892 de saldo y la regeneración fallaba.
 * Se amplían solo los TOTALES a decimal(24,13) (hasta ~100 mil millones); los costos unitarios no cambian.
 */
return new class extends Migration {
    /** Nullables sin default. */
    private const MOVIMIENTOS = ['entrada_costo_total', 'salida_costo_total', 'saldo_costo_total'];
    /** NOT NULL default 0 (se conserva tal cual). */
    private const KARDEX_NO_NULOS = ['costo_total', 'total_entradas_costo', 'total_salidas_costo'];

    public function up(): void
    {
        $this->cambiar(24);
    }

    public function down(): void
    {
        $this->cambiar(18);
    }

    private function cambiar(int $precision): void
    {
        Schema::table('ins_kardex_movimientos', function (Blueprint $table) use ($precision) {
            foreach (self::MOVIMIENTOS as $columna) {
                $table->decimal($columna, $precision, 13)->nullable()->change();
            }
        });
        Schema::table('ins_kardexes', function (Blueprint $table) use ($precision) {
            foreach (self::KARDEX_NO_NULOS as $columna) {
                $table->decimal($columna, $precision, 13)->default(0)->change();
            }
            $table->decimal('costo_final', $precision, 13)->nullable()->change();
        });
    }
};
