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
        Schema::create('campos_campanias_consumos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campos_campanias_id')
                  ->constrained('campos_campanias')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
            $table->string('categoria');
            $table->decimal('monto', 10, 4);
            $table->text('reporte_file');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campos_campanias_consumos');
    }
};
