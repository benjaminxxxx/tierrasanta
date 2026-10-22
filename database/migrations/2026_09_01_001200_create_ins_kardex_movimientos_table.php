<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ins_kardex_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kardex_id')->constrained('ins_kardexes')->cascadeOnDelete();
            $table->date('fecha');
            $table->enum('tipo_mov', ['entrada', 'salida']);
            $table->string('tipo_documento', 10)->nullable();
            $table->string('serie', 10)->nullable();
            $table->string('numero', 20)->nullable();
            $table->unsignedSmallInteger('tipo_operacion')->nullable();
            $table->decimal('entrada_cantidad', 18, 3)->nullable();
            $table->decimal('entrada_costo_unitario', 18, 13)->nullable();
            $table->decimal('entrada_costo_total', 24, 13)->nullable();
            $table->decimal('salida_cantidad', 18, 3)->nullable();
            $table->string('salida_lote')->nullable();
            $table->string('salida_maquinaria')->nullable();
            $table->decimal('salida_costo_unitario', 18, 13)->nullable();
            $table->decimal('salida_costo_total', 24, 13)->nullable();
            $table->json('detalle_distribucion')->nullable();
            $table->decimal('saldo_cantidad', 18, 3)->nullable();
            $table->decimal('saldo_costo_unitario', 18, 13)->nullable();
            $table->decimal('saldo_costo_total', 24, 13)->nullable();
            $table->enum('estado', ['activo', 'anulado'])->default('activo');
            $table->unsignedBigInteger('stock_movimiento_id')->nullable();
            $table->string('origen_glosa')->nullable();
            $table->timestamps();

            $table->index(['kardex_id', 'fecha', 'tipo_mov']);

            $table->foreign('stock_movimiento_id', 'ins_kardex_mov_stock_mov_fk')->references('id')->on('movimientos_stock')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ins_kardex_movimientos');
    }
};
