<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cochinilla_ingreso_origenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cochinilla_ingreso_id')->constrained('cochinilla_ingresos')->cascadeOnDelete();
            $table->string('campo_origen');
            $table->foreignId('campania_origen_id')->nullable()->constrained('campos_campanias')->nullOnDelete();
            $table->decimal('kg', 12, 2);
            $table->decimal('kg_madres', 12, 2)->nullable()->comment('Kg de mamá que aportó ese campo a la infestación');
            $table->decimal('porcentaje_sugerido', 7, 4)->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['cochinilla_ingreso_id', 'campo_origen'], 'coch_ing_origen_unico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cochinilla_ingreso_origenes');
    }
};
