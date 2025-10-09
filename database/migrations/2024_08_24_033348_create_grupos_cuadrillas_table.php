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
        Schema::create('cuad_grupos', function (Blueprint $table) {
            $table->string('codigo',30)->primary();
            $table->string('color');
            $table->string('nombre');
            $table->enum('modalidad_pago',['mensual','quincenal','semanal','variado'])->default('mensual');
            $table->decimal('costo_dia_sugerido', 8, 2);
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('eliminado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuad_grupos');
    }
};
