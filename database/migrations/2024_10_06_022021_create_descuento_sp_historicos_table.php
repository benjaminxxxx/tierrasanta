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
        Schema::create('descuento_sp_historicos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('descuento_codigo'); // Referencia a la tabla descuentos
            $table->decimal('porcentaje', 5, 2);
            $table->decimal('porcentaje_65', 5, 2);
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable(); // Si es NULL, significa que el descuento está vigente
            $table->timestamps();
        
            $table->foreign('descuento_codigo')->references('codigo')->on('descuento_sp')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('descuento_sp_historicos');
    }
};
