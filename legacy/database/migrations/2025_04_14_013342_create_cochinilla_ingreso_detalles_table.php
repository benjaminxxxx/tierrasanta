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
        Schema::create('cochinilla_ingreso_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cochinilla_ingreso_id');
            $table->string('sublote_codigo');
            $table->date('fecha');
            $table->decimal('total_kilos', 8, 2);
            $table->string('observacion')->nullable(); // clave foránea
        
            $table->timestamps();
        
            $table->foreign('cochinilla_ingreso_id')->references('id')->on('cochinilla_ingresos')->onDelete('cascade');
            $table->foreign('observacion')->references('codigo')->on('cochinilla_observaciones')->onDelete('set null');
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cochinilla_ingreso_detalles');
    }
};
