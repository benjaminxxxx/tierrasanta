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
        Schema::create('cuad_tramos_laborales', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->boolean('acumula_costos')->default(true);
            $table->decimal('total_a_pagar', 12, 2)->default(0);
            $table->decimal('dinero_recibido', 12, 2)->default(0);
            $table->decimal('saldo', 12, 2)->default(0);
            $table->string('titulo')->nullable();
            $table->date('fecha_hasta_bono')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuad_tramos_laborales');
    }
};
