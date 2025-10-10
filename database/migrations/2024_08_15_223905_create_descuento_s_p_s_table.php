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
        Schema::create('plan_sp_desc', function (Blueprint $table) {
            $table->string('codigo')->primary();
            $table->string('referencia');
            $table->string('orden');
            $table->string('descripcion');
            $table->string('tipo')->nullable();
            $table->decimal('porcentaje', 5, 2);
            $table->decimal('porcentaje_65', 5, 2);
            $table->string('color')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_sp_desc');
    }
};
