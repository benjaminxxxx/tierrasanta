<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kardex_carga_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kardex_carga_id')->constrained('kardex_cargas')->cascadeOnDelete();
            $table->unsignedInteger('fila');
            $table->string('codigo_existencia', 20);
            $table->string('nombre');
            $table->decimal('entradas_cantidad', 18, 4)->nullable();
            $table->decimal('entradas_importe', 24, 4)->nullable();
            $table->decimal('salidas_cantidad', 18, 4)->nullable();
            $table->decimal('salidas_importe', 24, 4)->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->text('mensaje')->nullable();
            $table->unsignedBigInteger('producto_id')->nullable();
            $table->string('producto_nombre')->nullable();
            $table->unsignedBigInteger('kardex_id')->nullable();
            $table->boolean('kardex_creado')->default(false);
            $table->unsignedInteger('compras')->nullable();
            $table->unsignedInteger('salidas')->nullable();
            $table->unsignedInteger('intentos')->default(0);
            $table->unsignedInteger('version_archivo')->nullable();
            $table->timestamp('procesado_at')->nullable();
            $table->timestamps();

            $table->index(['kardex_carga_id', 'fila']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kardex_carga_detalles');
    }
};
