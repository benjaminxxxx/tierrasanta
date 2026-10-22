<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('almacen_producto_salidas', function (Blueprint $table) {
            $table->id();
            $table->integer('item')->nullable();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('campo_nombre');
            $table->decimal('cantidad', 10, 3)->nullable();
            $table->date('fecha_reporte');
            $table->decimal('costo_por_kg', 10, 2)->nullable();
            $table->decimal('total_costo', 10, 2)->nullable();
            $table->foreignId('maquinaria_id')->nullable()->constrained('maquinarias')->nullOnDelete();
            $table->integer('indice')->nullable();
            $table->enum('tipo_kardex', ['blanco', 'negro'])->nullable();
            $table->foreignId('movimiento_id')->nullable()->constrained('ins_kardex_movimientos')->nullOnDelete();
            $table->string('registro_carga', 20)->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('editado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('uso_id')->nullable()->constrained('ins_usos')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('almacen_producto_salidas');
    }
};
