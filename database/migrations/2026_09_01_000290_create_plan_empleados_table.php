<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Empleado de planilla: solo lo propio del empleado. Sus datos naturales (DNI, nombres, nacimiento, dirección…) están
 * en su persona y no llevan historial. El historial laboral está en contratos y sus partículas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_empleados', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36);
            $table->foreignId('persona_id')->constrained('personas');
            $table->integer('orden')->nullable();
            $table->text('comentarios')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('eliminado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique('persona_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_empleados');
    }
};
