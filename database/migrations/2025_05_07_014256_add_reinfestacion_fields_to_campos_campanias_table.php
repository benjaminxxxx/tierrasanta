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
        Schema::table('campos_campanias', function (Blueprint $table) {
            $table->date('reinfestacion_fecha')->nullable();
            $table->string('reinfestacion_duracion_desde_infestacion', 255)->nullable();
            $table->integer('reinfestacion_numero_pencas')->nullable();
            $table->decimal('reinfestacion_kg_totales_madre', 8, 2)->nullable();
            $table->decimal('reinfestacion_kg_madre_infestador_carton', 8, 2)->nullable();
            $table->decimal('reinfestacion_kg_madre_infestador_tubos', 8, 2)->nullable();
            $table->decimal('reinfestacion_kg_madre_infestador_mallita', 8, 2)->nullable();
            $table->text('reinfestacion_procedencia_madres')->nullable();
            $table->double('reinfestacion_cantidad_madres_por_infestador_carton')->nullable();
            $table->double('reinfestacion_cantidad_madres_por_infestador_tubos')->nullable();
            $table->double('reinfestacion_cantidad_madres_por_infestador_mallita')->nullable();
            $table->integer('reinfestacion_cantidad_infestadores_carton')->nullable();
            $table->integer('reinfestacion_cantidad_infestadores_tubos')->nullable();
            $table->integer('reinfestacion_cantidad_infestadores_mallita')->nullable();
            $table->date('reinfestacion_fecha_recojo_vaciado_infestadores')->nullable();
            $table->integer('reinfestacion_permanencia_infestadores')->nullable();
            $table->date('reinfestacion_fecha_colocacion_malla')->nullable();
            $table->date('reinfestacion_fecha_retiro_malla')->nullable();
            $table->integer('reinfestacion_permanencia_malla')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campos_campanias', function (Blueprint $table) {
            $table->dropColumn([
                'reinfestacion_fecha',
                'reinfestacion_duracion_desde_infestacion',
                'reinfestacion_numero_pencas',
                'reinfestacion_kg_totales_madre',
                'reinfestacion_kg_madre_infestador_carton',
                'reinfestacion_kg_madre_infestador_tubos',
                'reinfestacion_kg_madre_infestador_mallita',
                'reinfestacion_procedencia_madres',
                'reinfestacion_cantidad_madres_por_infestador_carton',
                'reinfestacion_cantidad_madres_por_infestador_tubos',
                'reinfestacion_cantidad_madres_por_infestador_mallita',
                'reinfestacion_cantidad_infestadores_carton',
                'reinfestacion_cantidad_infestadores_tubos',
                'reinfestacion_cantidad_infestadores_mallita',
                'reinfestacion_fecha_recojo_vaciado_infestadores',
                'reinfestacion_permanencia_infestadores',
                'reinfestacion_fecha_colocacion_malla',
                'reinfestacion_fecha_retiro_malla',
                'reinfestacion_permanencia_malla',
            ]);
        });
    }
};
