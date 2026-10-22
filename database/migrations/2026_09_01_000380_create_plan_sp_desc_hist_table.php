<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_sp_desc_hist', function (Blueprint $table) {
            $table->id();
            $table->string('descuento_codigo');
            $table->decimal('porcentaje', 5, 2);
            $table->decimal('porcentaje_65', 5, 2);
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->timestamps();

            $table->foreign('descuento_codigo')->references('codigo')->on('plan_sp_desc')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_sp_desc_hist');
    }
};
