<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cochinilla_ingreso_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cochinilla_ingreso_id')->constrained('cochinilla_ingresos')->cascadeOnDelete();
            $table->string('sublote_codigo');
            $table->date('fecha');
            $table->decimal('total_kilos', 8, 2);
            $table->string('observacion')->nullable();
            $table->timestamps();

            $table->foreign('observacion')->references('codigo')->on('cochinilla_observaciones')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cochinilla_ingreso_detalles');
    }
};
