<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cuad_gastos_grupos', function (Blueprint $table) {
            $table->id(); // Clave primaria
            $table->decimal('monto', 10, 2); // Campo para precios con hasta 10 dígitos, 2 decimales
            $table->string('descripcion');
            $table->year('anio_contable')->nullable();  // Para el año contable
            $table->tinyInteger('mes_contable')->nullable();  // Para el mes contable (1-12)
            $table->string('codigo_grupo');
            $table->timestamp('fecha_gasto')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->unsignedBigInteger('cuad_tramo_laboral_id')->nullable();

            $table->enum('estado', ['pendiente', 'aprobado', 'en_correccion'])
                ->default('pendiente');
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->unsignedBigInteger('aprobado_por')->nullable();
            $table->timestamp('aprobado_en')->nullable();
            $table->unsignedBigInteger('habilitado_por')->nullable();
            $table->timestamp('habilitado_en')->nullable();

            $table->foreign('creado_por')->references('id');
            $table->foreign('aprobado_por')->references('id');
            $table->foreign('habilitado_por')->references('id');

            $table->foreign('cuad_tramo_laboral_id', 'fk_gasto_tra_lab1')
                ->references('id')
                ->on('cuad_tramos_laborales')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuad_gastos_grupos');
    }
};
