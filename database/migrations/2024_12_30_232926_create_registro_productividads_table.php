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
        Schema::create('registro_productividads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('labor_valoracion_id')
                ->constrained('labor_valoracions')
                ->onDelete('cascade')
                ->onUpdate('cascade')
                ->comment('Clave foránea hacia la tabla labor_valoracions');
            
            $table->foreignId('labor_id')
                ->constrained('labores')
                ->onDelete('cascade')
                ->onUpdate('cascade')
                ->comment('Clave foránea hacia la tabla labores');

            $table->date('fecha');
            $table->string('campo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registro_productividads');
    }
};
