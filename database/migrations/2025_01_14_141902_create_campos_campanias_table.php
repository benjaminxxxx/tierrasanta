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
        Schema::create('campos_campanias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_campania')->nullable(); // Campo para la campaña, e.g., T.2024 o N2.2024
            $table->string('campo');
            $table->decimal('gasto_fdm', 8, 2)->nullable();
            $table->decimal('gasto_agua', 8, 2)->nullable();
            $table->decimal('gasto_planilla', 8, 2)->nullable();
            $table->decimal('gasto_cuadrilla', 8, 2)->nullable();
            $table->date('fecha_inicio'); // Campo para la fecha de vigencia
            $table->date('fecha_fin')->nullable();
            $table->unsignedBigInteger('usuario_modificador')->nullable();

            
            $table->foreign('campo')->references('nombre')->on('campos')->onDelete('cascade');
            $table->foreign('usuario_modificador')->references('id')->on('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campos_campanias');
    }
};
