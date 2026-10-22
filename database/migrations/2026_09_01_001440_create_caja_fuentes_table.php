<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja_fuentes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->boolean('es_oficina')->default(false)->comment('Dinero que está en la oficina (lo que lleva la caja de oficina)');
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique('nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_fuentes');
    }
};
