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
        Schema::create('actividades', function (Blueprint $table) {
            $table->id();

            $table->date('fecha');
            $table->string('campo');
            $table->foreignId('labor_id')
                ->constrained('labores')
                ->onDelete('cascade')
                ->comment('Clave foránea hacia la tabla labores');

            $table->decimal('horas_trabajadas', 8, 2)->nullable();
            $table->unsignedBigInteger('labor_valoracion_id')->nullable();
            $table->foreign('labor_valoracion_id','fk_labor_vi')
            ->references('id')->on('labor_valoracions')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actividades');
    }
};
