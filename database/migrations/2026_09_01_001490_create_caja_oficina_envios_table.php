<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja_oficina_envios', function (Blueprint $table) {
            $table->id();
            $table->enum('estado', ['pendiente', 'anexado'])->default('pendiente');
            $table->string('nota', 500)->nullable();
            $table->unsignedInteger('cambios')->default(0);
            $table->foreignId('enviado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('enviado_nombre', 150)->nullable();
            $table->foreignId('anexado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('anexado_nombre', 150)->nullable();
            $table->timestamp('anexado_at')->nullable();
            $table->timestamps();

            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_oficina_envios');
    }
};
