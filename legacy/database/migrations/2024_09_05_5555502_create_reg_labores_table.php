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
        Schema::create('reg_labores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_labor');
            $table->boolean('es_riego')->default(false);
            $table->boolean('es_apoyo_riego')->default(false);
            $table->decimal('consumo_m3_hora', 8, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reg_labores');
    }
};
