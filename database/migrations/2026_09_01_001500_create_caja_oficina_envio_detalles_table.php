<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja_oficina_envio_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caja_oficina_envio_id')->constrained('caja_oficina_envios')->cascadeOnDelete();
            $table->unsignedBigInteger('caja_oficina_movimiento_id');
            $table->enum('accion', ['nuevo', 'modificado', 'eliminado']);
            $table->json('datos')->comment('La fila de oficina al enviarla');
            $table->json('datos_antes')->nullable()->comment('Cómo se había enviado antes (modificado/eliminado)');
            $table->enum('resultado', ['anexado', 'omitido'])->nullable();
            $table->string('resultado_nota', 300)->nullable();
            $table->unsignedBigInteger('caja_movimiento_id')->nullable();
            $table->timestamps();

            $table->index('caja_oficina_movimiento_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_oficina_envio_detalles');
    }
};
