<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
      Schema::create('presentaciones', function (Blueprint $table) {
    $table->id();
    $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
    
    // Llave foránea hacia sunat_tabla6_codigo_unidad_medida
    $table->string('unidad_medida_codigo', 4)->nullable(); 
    
    $table->string('nombre'); // Ej: Caja x 100, Saco x 50kg
    $table->decimal('factor_conversion', 12, 4)->default(1.0000);
    $table->boolean('es_compra_defecto')->default(false);
    $table->boolean('activo')->default(true);
    $table->timestamps();

    $table->foreign('unidad_medida_codigo')
        ->references('codigo')
        ->on('sunat_tabla6_codigo_unidad_medida')
        ->nullOnDelete();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presentaciones');
    }
};
