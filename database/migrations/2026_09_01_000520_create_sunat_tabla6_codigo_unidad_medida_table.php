<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sunat_tabla6_codigo_unidad_medida', function (Blueprint $table) {
            $table->string('codigo', 4);
            $table->string('descripcion');
            $table->string('alias')->nullable();
            $table->primary('codigo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sunat_tabla6_codigo_unidad_medida');
    }
};
