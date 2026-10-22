<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicios_campo_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servicio_campo_id')->constrained('servicios_campo')->cascadeOnDelete();
            $table->date('fecha');
            $table->string('campo');
            $table->foreignId('campania_id')->nullable()->constrained('campos_campanias')->nullOnDelete();
            $table->string('labor');
            $table->decimal('cantidad', 12, 3);
            $table->decimal('costo', 12, 2);
            $table->timestamps();

            $table->index('fecha');
            $table->index('labor');

            $table->foreign('campo')->references('nombre')->on('campos')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicios_campo_detalles');
    }
};
