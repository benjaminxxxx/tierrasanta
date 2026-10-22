<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja_tipos_cambio', function (Blueprint $table) {
            $table->date('fecha');
            $table->decimal('valor', 8, 4);
            $table->timestamps();
            $table->primary('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_tipos_cambio');
    }
};
