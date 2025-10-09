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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_comercial');
            $table->string('ingrediente_activo')->nullable();
            $table->enum('categoria', ['fertilizante', 'pesticida', 'combustible'])->default('fertilizante');
            $table->string('categoria_pesticida')->nullable();
            $table->string('codigo_tipo_existencia', 4)->nullable(); 
            $table->string('codigo_unidad_medida', 4)->nullable(); 

            // Establecer las claves foráneas
            $table->foreign('codigo_tipo_existencia')->references('codigo')->on('sunat_tabla5_tipo_existencias')->onDelete('set null');
            $table->foreign('codigo_unidad_medida')->references('codigo')->on('sunat_tabla6_codigo_unidad_medida')->onDelete('set null');
            $table->foreign('categoria_pesticida')
                ->references('codigo')
                ->on('categoria_pesticidas')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
