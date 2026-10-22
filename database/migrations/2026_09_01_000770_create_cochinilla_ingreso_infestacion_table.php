<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cochinilla_ingreso_infestacion', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cochinilla_ingreso_id');
            $table->unsignedBigInteger('cochinilla_infestacion_id');
            $table->decimal('kg_asignados', 10, 2);
            $table->timestamps();

            $table->foreign('cochinilla_infestacion_id', 'fk_infestacion')->references('id')->on('cochinilla_infestaciones')->cascadeOnDelete();
            $table->foreign('cochinilla_ingreso_id', 'fk_ingreso')->references('id')->on('cochinilla_ingresos')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cochinilla_ingreso_infestacion');
    }
};
