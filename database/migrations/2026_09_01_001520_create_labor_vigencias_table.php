<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labor_vigencias', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('codigo');
            $table->string('nombre_labor');
            $table->string('codigo_mano_obra')->nullable();
            $table->string('tipo_asistencia_codigo', 10)->nullable();
            $table->string('unidades', 20)->nullable();
            $table->date('desde')->nullable()->comment('null = desde siempre');
            $table->date('hasta');
            $table->string('motivo', 500)->nullable()->comment('Por qué se reasignó el código');
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['codigo', 'hasta']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labor_vigencias');
    }
};
