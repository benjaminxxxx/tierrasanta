<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja_arqueo_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caja_arqueo_id')->constrained('caja_arqueos')->cascadeOnDelete();
            $table->foreignId('caja_fuente_id')->constrained('caja_fuentes');
            $table->decimal('monto', 14, 2);
            $table->timestamps();

            $table->unique(['caja_arqueo_id', 'caja_fuente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_arqueo_detalles');
    }
};
