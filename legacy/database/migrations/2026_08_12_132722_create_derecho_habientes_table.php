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
        Schema::create('derecho_habientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombres');
            $table->date('fecha_nacimiento');
            $table->string('tipo_documento')->default('DNI');
            $table->string('documento')->unique(); // una persona = un registro, sin importar cuántos empleados lo declaren
            $table->enum('tipo', ['hijo', 'conyuge']);
            $table->boolean('discapacidad_severa')->default(false);
            $table->boolean('percibe_pension_no_contributiva')->default(false);
            $table->date('fecha_inicio_estudios')->nullable();
            $table->date('fecha_fin_estudios')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('derecho_habientes');
    }
};
