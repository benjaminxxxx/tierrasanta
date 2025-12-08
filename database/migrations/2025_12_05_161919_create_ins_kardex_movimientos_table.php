<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ins_kardex_movimientos', function (Blueprint $table) {
            $table->id();

            // Relación con kardex
            $table->foreignId('kardex_id')
                ->constrained('ins_kardexes')
                ->onDelete('cascade');

            // FECHA DEL MOVIMIENTO
            $table->date('fecha');

            // TIPO DE MOVIMIENTO (entrada/salida)
            $table->enum('tipo_mov', ['entrada', 'salida']);

            // REFERENCIAS DE DOCUMENTOS
            $table->string('tipo_documento', 10)->nullable(); // TABLA 10
            $table->string('serie', 10)->nullable();
            $table->string('numero', 20)->nullable();
            $table->unsignedSmallInteger('tipo_operacion')->nullable(); // TABLA 12

            // -----------------------------
            // CAMPOS PARA ENTRADAS
            // -----------------------------
            $table->decimal('entrada_cantidad', 18, 3)->nullable();
            $table->decimal('entrada_costo_unitario', 18, 13)->nullable();
            $table->decimal('entrada_costo_total', 18, 13)->nullable();

            // -----------------------------
            // CAMPOS PARA SALIDAS
            // -----------------------------
            $table->decimal('salida_cantidad', 18, 3)->nullable();

            // SALIDAS – DOS CONTEXTOS
            $table->string('salida_lote')->nullable();       // fertilizantes/pesticidas
            $table->string('salida_maquinaria')->nullable(); // combustible

            // COSTOS (calculados al reprocesar)
            $table->decimal('salida_costo_unitario', 18, 13)->nullable();
            $table->decimal('salida_costo_total', 18, 13)->nullable();

            // EXTRA: para combustible distribuido en varios campos
            $table->json('detalle_distribucion')->nullable();

            /*
            Ejemplo JSON:
            [
                { "campo_id": 1, "kg": 12.4, "costo": 55.30 },
                { "campo_id": 2, "kg": 8.2,  "costo": 36.50 }
            ]
            */

            // -----------------------------
            // CAMPOS DE SALDO (OBLIGATORIO)
            // -----------------------------
            $table->decimal('saldo_cantidad', 18, 3)->nullable();
            $table->decimal('saldo_costo_unitario', 18, 13)->nullable();
            $table->decimal('saldo_costo_total', 18, 13)->nullable();

            // ESTADO
            $table->enum('estado', ['activo', 'anulado'])->default('activo');

            $table->timestamps();

            // INDEX PARA LISTAR SUPER RÁPIDO
            $table->index(['kardex_id', 'fecha', 'tipo_mov']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ins_kardex_movimientos');
    }
};
