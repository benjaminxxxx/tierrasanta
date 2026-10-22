<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja_clasificadores', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['INGRESO', 'EGRESO']);
            $table->string('grupo', 120)->nullable()->comment('Columna TIPO de la hoja Valida (VENTA, OTROS INGRESOS…)');
            $table->string('clasificador_1', 150);
            $table->string('clasificador_2', 200);
            $table->string('color_fondo', 7)->nullable();
            $table->string('color_texto', 7)->nullable();
            $table->boolean('negrita')->default(false);
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['clasificador_1', 'clasificador_2']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_clasificadores');
    }
};
