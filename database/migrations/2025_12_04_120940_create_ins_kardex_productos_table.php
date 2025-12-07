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
        
        Schema::create('ins_kardexes', function (Blueprint $table) {
            $table->id();
            //campos al crear el kardex
            $table->foreignId('producto_id')->nullable()->constrained('productos')->onDelete('set null');
            $table->string('descripcion');
            $table->string('codigo_existencia',10)->nullable();
            $table->unsignedSmallInteger('anio'); // Año del kardex
            $table->enum('tipo', ['blanco', 'negro'])->default('blanco'); // Tipo de kardex
            $table->decimal('stock_inicial', 18, 3);
            $table->decimal('costo_unitario', 18, 13);
            $table->decimal('costo_total', 18, 13);
            //campos que se registran despues
            $table->decimal('stock_final', 18, 3)->nullable();
            $table->decimal('costo_final', 18, 13)->nullable();
            $table->enum('estado', ['activo', 'cerrado'])->default('activo');
            $table->enum('metodo_valuacion', ['promedio', 'peps'])->default('promedio');
            $table->string('file', 255)->nullable();
            $table->timestamps();

            // Restricción única: un producto solo puede tener un kardex por año y tipo
            $table->unique(['codigo_existencia', 'anio', 'tipo'], 'unique_producto_anio_tipo');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ins_kardexes');
    }
};
