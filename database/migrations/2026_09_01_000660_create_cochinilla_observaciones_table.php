<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cochinilla_observaciones', function (Blueprint $table) {
            $table->string('codigo');
            $table->string('descripcion');
            $table->boolean('es_cosecha_mama')->default(false);
            $table->boolean('es_vendible')->default(true)->comment('false = va a infestación y vuelve como ingreso de infestadores: no se vende');
            $table->timestamps();
            $table->primary('codigo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cochinilla_observaciones');
    }
};
