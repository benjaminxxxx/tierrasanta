<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion_historial', function (Blueprint $table) {
            $table->id();
            $table->string('configuracion_codigo');
            $table->decimal('valor', 12, 4);
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('configuracion_codigo')->references('codigo')->on('configuracion')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion_historial');
    }
};
