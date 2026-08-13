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
        Schema::create('empleado_derecho_habientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('plan_empleados')->cascadeOnDelete();
            $table->foreignId('derecho_habiente_id')->constrained('derecho_habientes')->cascadeOnDelete();
            $table->enum('rol', ['padre', 'madre', 'conyuge', 'tutor']);
            $table->unsignedTinyInteger('mes_vigencia')->nullable();
            $table->unsignedSmallInteger('anio_vigencia')->nullable();
            $table->boolean('activo')->default(true);
            $table->string('motivo_inactivacion')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Un derecho_habiente solo puede tener UN padre y UNA madre (rol es parte de la unicidad)
            $table->unique(['derecho_habiente_id', 'rol']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empleado_derecho_habientes');
    }
};
