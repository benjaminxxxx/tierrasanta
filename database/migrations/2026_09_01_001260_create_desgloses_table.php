<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('desgloses', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_vale')->nullable();
            $table->date('fecha');
            $table->decimal('monto_inicial', 12, 2)->default(0.00);
            $table->decimal('saldo_anterior', 12, 2)->default(0.00);
            $table->decimal('monto_total_gastos', 12, 2)->default(0.00);
            $table->decimal('saldo_final', 12, 2)->default(0.00);
            $table->string('entregado_por')->nullable();
            $table->string('recibido_por')->nullable();
            $table->enum('estado', ['abierto', 'cerrado'])->default('abierto');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('desgloses');
    }
};
