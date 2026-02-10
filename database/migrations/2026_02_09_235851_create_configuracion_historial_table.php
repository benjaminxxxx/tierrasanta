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
        Schema::create('configuracion_historial', function (Blueprint $table) {
            $table->id();

            // Relación
            $table->string('configuracion_codigo');
            $table->foreign('configuracion_codigo')
                ->references('codigo')
                ->on('configuracion')
                ->onDelete('cascade');

            $table->decimal('valor', 12, 4);

            // Vigencia
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();

            // Meta adicional opcional
            $table->boolean('activo')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuracion_historial');
    }
};
