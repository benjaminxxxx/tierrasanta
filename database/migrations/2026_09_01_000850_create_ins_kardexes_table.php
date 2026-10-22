<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ins_kardexes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->string('descripcion');
            $table->string('codigo_existencia', 10)->nullable();
            $table->unsignedSmallInteger('anio');
            $table->enum('tipo', ['blanco', 'negro'])->default('blanco');
            $table->decimal('stock_inicial', 18, 4)->default(0.0000);
            $table->decimal('costo_unitario', 18, 13)->default(0.0000000000000);
            $table->decimal('costo_total', 24, 13)->default(0.0000000000000);
            $table->decimal('stock_actual', 18, 4)->default(0.0000);
            $table->decimal('costo_unitario_promedio', 18, 13)->default(0.0000000000000);
            $table->decimal('stock_final', 18, 4)->nullable();
            $table->decimal('costo_final', 24, 13)->nullable();
            $table->enum('estado', ['activo', 'cerrado'])->default('activo');
            $table->enum('metodo_valuacion', ['promedio', 'peps'])->default('promedio');
            $table->string('file')->nullable();
            $table->decimal('total_entradas_cantidad', 18, 4)->default(0.0000);
            $table->decimal('total_entradas_costo', 24, 13)->default(0.0000000000000);
            $table->decimal('total_salidas_cantidad', 18, 4)->default(0.0000);
            $table->decimal('total_salidas_costo', 24, 13)->default(0.0000000000000);
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('movimientos_actualizados_at')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users');
            $table->foreignId('editado_por')->nullable()->constrained('users');
            $table->string('tipo_compra_codigo_inicial', 4)->nullable();
            $table->string('serie_inicial')->nullable();
            $table->string('numero_inicial')->nullable();
            $table->timestamps();

            $table->unique(['producto_id', 'anio', 'tipo'], 'unique_producto_anio_tipo');

            $table->foreign('tipo_compra_codigo_inicial')->references('codigo')->on('sunat_tabla10_tipo_comprobantes_pago')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ins_kardexes');
    }
};
