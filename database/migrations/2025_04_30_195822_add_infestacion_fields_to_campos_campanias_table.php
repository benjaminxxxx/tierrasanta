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
            $table->date('infestacion_fecha')->nullable();
            $table->string('infestacion_duracion_desde_campania',255)->nullable();
            $table->integer('infestacion_numero_pencas')->nullable();
            $table->decimal('infestacion_kg_totales_madre', 8, 2)->nullable();
            $table->decimal('infestacion_kg_madre_infestador_carton', 8, 2)->nullable();
            $table->decimal('infestacion_kg_madre_infestador_tubos', 8, 2)->nullable();
            $table->decimal('infestacion_kg_madre_infestador_mallita', 8, 2)->nullable();
            $table->text('infestacion_procedencia_madres')->nullable();
            $table->double('infestacion_cantidad_madres_por_infestador_carton')->nullable();
            $table->double('infestacion_cantidad_madres_por_infestador_tubos')->nullable();
            $table->double('infestacion_cantidad_madres_por_infestador_mallita')->nullable();
            $table->integer('infestacion_cantidad_infestadores_carton')->nullable();
            $table->integer('infestacion_cantidad_infestadores_tubos')->nullable();
            $table->integer('infestacion_cantidad_infestadores_mallita')->nullable();
            $table->date('infestacion_fecha_recojo_vaciado_infestadores')->nullable();
            $table->integer('infestacion_permanencia_infestadores')->nullable();
            $table->date('infestacion_fecha_colocacion_malla')->nullable();
            $table->date('infestacion_fecha_retiro_malla')->nullable();
            $table->integer('infestacion_permanencia_malla')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campos_campanias', function (Blueprint $table) {
            $table->dropColumn([
                'infestacion_fecha',
                'infestacion_duracion_desde_campania',
                'infestacion_numero_pencas',
                'infestacion_kg_totales_madre',
                'infestacion_kg_madre_infestador_carton',
                'infestacion_kg_madre_infestador_tubos',
                'infestacion_kg_madre_infestador_mallita',
                'infestacion_procedencia_madres',
                'infestacion_cantidad_madres_por_infestador_carton',
                'infestacion_cantidad_madres_por_infestador_tubos',
                'infestacion_cantidad_madres_por_infestador_mallita',
                'infestacion_cantidad_infestadores_carton',
                'infestacion_cantidad_infestadores_tubos',
                'infestacion_cantidad_infestadores_mallita',
                'infestacion_fecha_recojo_vaciado_infestadores',
                'infestacion_permanencia_infestadores',
                'infestacion_fecha_colocacion_malla',
                'infestacion_fecha_retiro_malla',
                'infestacion_permanencia_malla',
            ]);
        });
    }
};
