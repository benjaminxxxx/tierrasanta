<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('venta_cochinilla_reportes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('cochinilla_ingreso_id')->nullable();

            $table->date('cosecha_fecha_ingreso')->nullable();
            $table->string('cosecha_campo')->nullable();
            $table->string('cosecha_procedencia')->nullable();
            $table->decimal('cosecha_cantidad_fresca', 15, 2)->nullable();

            $table->date('proceso_fecha_filtrado')->nullable();
            $table->decimal('proceso_cantidad_seca', 15, 2)->nullable();
            $table->string('proceso_condicion')->nullable();

            $table->date('venta_fecha_venta')->nullable();
            $table->string('venta_comprador')->nullable();
            $table->string('venta_infestadores_del_campo')->nullable();

            $table->boolean('cosecha_encontrada')->default(false);
            $table->boolean('fusionada')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venta_cochinilla_reportes');
    }
};
