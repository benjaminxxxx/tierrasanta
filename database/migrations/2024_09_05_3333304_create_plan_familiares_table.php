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
        Schema::create('plan_familiares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_empleado_id')
                ->constrained('plan_empleados')
                ->onDelete('cascade');
            $table->string('nombres');
            $table->date('fecha_nacimiento');
            $table->string('documento');
            $table->boolean('esta_estudiando')->default(false);
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['documento', 'plan_empleado_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_familiares');
    }
};
