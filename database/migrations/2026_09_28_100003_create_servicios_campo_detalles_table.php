<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Detalle por campo trabajado. El costo es la parte proporcional del total pagado
     * (IGV incluido: la empresa no tiene crédito fiscal).
     * Si la campaña se elimina, campania_id queda null para poder reasignarla.
     */
    public function up(): void
    {
        Schema::create('servicios_campo_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servicio_campo_id')->constrained('servicios_campo')->cascadeOnDelete();
            $table->date('fecha');
            $table->string('campo');
            $table->unsignedBigInteger('campania_id')->nullable();
            $table->string('labor');
            $table->decimal('cantidad', 12, 3);
            $table->decimal('costo', 12, 2);
            $table->timestamps();

            $table->foreign('campo')->references('nombre')->on('campos')->restrictOnDelete();
            $table->foreign('campania_id')->references('id')->on('campos_campanias')->nullOnDelete();

            $table->index('fecha');
            $table->index('labor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicios_campo_detalles');
    }
};
