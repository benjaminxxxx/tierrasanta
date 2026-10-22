<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_tipos_suspension', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 5);
            $table->string('grupo', 3);
            $table->string('descripcion');
            $table->string('descripcion_corta', 100);
            $table->boolean('incluye_domingos')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_tipos_suspension');
    }
};
