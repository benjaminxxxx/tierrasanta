<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuad_registros_diarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuadrillero_id')->constrained('cuad_cuadrilleros')->restrictOnDelete();
            $table->date('fecha');
            $table->decimal('costo_personalizado_dia', 10, 2)->nullable();
            $table->decimal('total_bono', 10, 2)->default(0.00);
            $table->decimal('total_horas', 5, 2)->nullable();
            $table->boolean('esta_pagado')->default(false);
            $table->string('codigo_grupo');
            $table->foreignId('desglose_detalle_id')->nullable()->constrained('desglose_detalles')->nullOnDelete();
            $table->foreignId('tramo_laboral_id')->nullable()->constrained('cuad_tramos_laborales')->restrictOnDelete();
            $table->foreignId('tramo_cuadrillero_id')->constrained('cuad_tramo_cuadrilleros')->restrictOnDelete();
            $table->timestamps();
            $table->decimal('jornal_aplicado', 10, 2)->default(0.00);
            $table->decimal('horas_destajo', 5, 2)->nullable()->default(0.00);
        });

        // Triggers (las tablas que usan se resuelven al ejecutarse, no al crearse)
        DB::unprepared(<<<'SQL'
CREATE TRIGGER `tr_registros_insert_fetch` BEFORE INSERT ON `cuad_registros_diarios` FOR EACH ROW
BEGIN
                SET NEW.jornal_aplicado = COALESCE((
                    SELECT jornal FROM cuad_costos_diarios_grupos
                    WHERE codigo_grupo = NEW.codigo_grupo AND fecha = NEW.fecha LIMIT 1
                ), 0);
            END
SQL);
        DB::unprepared(<<<'SQL'
CREATE TRIGGER `tr_registros_update_fetch` BEFORE UPDATE ON `cuad_registros_diarios` FOR EACH ROW
BEGIN
                IF (OLD.codigo_grupo <> NEW.codigo_grupo OR OLD.fecha <> NEW.fecha) THEN
                    SET NEW.jornal_aplicado = COALESCE((
                        SELECT jornal FROM cuad_costos_diarios_grupos
                        WHERE codigo_grupo = NEW.codigo_grupo AND fecha = NEW.fecha LIMIT 1
                    ), 0);
                END IF;
            END
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS `tr_registros_insert_fetch`');
        DB::unprepared('DROP TRIGGER IF EXISTS `tr_registros_update_fetch`');
        Schema::dropIfExists('cuad_registros_diarios');
    }
};
