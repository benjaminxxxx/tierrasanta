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
        Schema::create('registro_productividad_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registro_productividad_id')
                ->constrained('registro_productividads')
                ->onDelete('cascade')
                ->onUpdate('cascade')
                ->comment('Clave foránea hacia la tabla registro_productividads')
                ->index(); // Agregamos un índice para mejorar el rendimiento de las consultas.

            // Asignamos un nombre más corto a la clave foránea.
            $table->foreign('registro_productividad_id', 'fk_productividad_detalle_productividad')
                ->references('id')
                ->on('registro_productividads')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->integer('indice');
            $table->decimal('horas_trabajadas', 8, 2);
            $table->decimal('kg', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registro_productividad_detalles');
    }
};
