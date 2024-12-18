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
        Schema::create('gasto_adicional_por_grupo_cuadrillas', function (Blueprint $table) {
            $table->id(); // Clave primaria
            $table->decimal('monto', 10, 2); // Campo para precios con hasta 10 dígitos, 2 decimales
            $table->string('descripcion');
            $table->unsignedBigInteger('cua_asistencia_semanal_grupo_id'); // Relación con 'cua_asistencia_semanal_grupos'
            $table->timestamps();

            // Definición de la clave foránea con un nombre corto
            $table->foreign('cua_asistencia_semanal_grupo_id', 'fk_cua_asistencia_grupo')
                  ->references('id')
                  ->on('cua_asistencia_semanal_grupos')
                  ->onDelete('cascade'); // Borra los registros relacionados si se elimina el grupo
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gasto_adicional_por_grupo_cuadrillas');
    }
};
