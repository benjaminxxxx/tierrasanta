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
        Schema::create('desgloses', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_vale')->nullable(); // Ej: "Referencia Vale Nro"
            $table->date('fecha'); // Ej: 12-Set
            $table->decimal('monto_inicial', 12, 2)->default(0); // Dinero enviado de Oficina Central
            $table->decimal('saldo_anterior', 12, 2)->default(0); // Saldo arrastrado del periodo anterior
            $table->decimal('monto_total_gastos', 12, 2)->default(0); // Suma total de los egresos
            $table->decimal('saldo_final', 12, 2)->default(0); // (Saldo anterior + Inicial) - Gastos
            $table->string('entregado_por')->nullable();
            $table->string('recibido_por')->nullable();
            $table->enum('estado', ['abierto', 'cerrado'])->default('abierto');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('desgloses');
    }
};