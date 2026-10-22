<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cochinilla_recuperaciones', function (Blueprint $table) {
            $table->id();
            $table->string('campo')->comment('Campo infestado (donde se recogen los infestadores)');
            $table->date('tanda_inicio');
            $table->date('tanda_fin');
            $table->foreignId('campo_campania_id')->nullable()->constrained('campos_campanias')->nullOnDelete();
            $table->decimal('kg_madres', 12, 2)->default(0.00);
            $table->decimal('kg_recuperados', 12, 2)->default(0.00)->comment('Al cerrar');
            $table->text('nota')->nullable();
            $table->foreignId('cerrada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cerrada_en')->nullable();
            $table->timestamps();

            $table->unique(['campo', 'tanda_inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cochinilla_recuperaciones');
    }
};
