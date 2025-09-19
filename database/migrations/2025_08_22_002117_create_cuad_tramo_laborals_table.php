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
        Schema::create('cuad_tramo_laborals', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->boolean('acumula_costos')->default(true);
            $table->decimal('total_a_pagar', 12, 2)->default(0);
            $table->decimal('dinero_recibido', 12, 2)->default(0);
            $table->decimal('saldo', 12, 2)->default(0);
            $table->string('titulo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuad_tramo_laborals');
    }
};
