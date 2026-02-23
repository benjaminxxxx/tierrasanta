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
        Schema::create('plan_ordenes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('empleado_id')
                ->constrained('plan_empleados')   // referencia exacta a tu tabla
                ->cascadeOnDelete();              // <─ solicitado

            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('mes');   // 1–12
            $table->unsignedInteger('orden');     // orden dentro del mes

            $table->timestamps();

            $table->unique(['anio', 'mes', 'empleado_id'], 'unique_mes_anio_empleado');
            $table->index(['anio', 'mes'], 'index_mes_anio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_ordenes');
    }
};
