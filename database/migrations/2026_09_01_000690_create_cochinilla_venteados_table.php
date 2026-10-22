<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cochinilla_venteados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cochinilla_ingreso_id')->nullable()->constrained('cochinilla_ingresos')->nullOnDelete();
            $table->unsignedInteger('lote');
            $table->date('fecha_proceso');
            $table->decimal('kilos_ingresado', 10, 2);
            $table->decimal('limpia', 10, 2);
            $table->decimal('polvillo', 10, 2);
            $table->timestamps();
        });

        // Triggers (las tablas que usan se resuelven al ejecutarse, no al crearse)
        DB::unprepared(<<<'SQL'
CREATE TRIGGER `trg_cochinilla_venteados_bi` BEFORE INSERT ON `cochinilla_venteados` FOR EACH ROW
BEGIN
            DECLARE v_ingreso_id BIGINT;

            SELECT id
            INTO v_ingreso_id
            FROM cochinilla_ingresos
            WHERE lote = NEW.lote
            LIMIT 1;

            SET NEW.cochinilla_ingreso_id = v_ingreso_id;
        END
SQL);
        DB::unprepared(<<<'SQL'
CREATE TRIGGER `trg_cochinilla_venteados_bu` BEFORE UPDATE ON `cochinilla_venteados` FOR EACH ROW
BEGIN
            DECLARE v_ingreso_id BIGINT;

            IF NEW.lote <> OLD.lote THEN

                SELECT id
                INTO v_ingreso_id
                FROM cochinilla_ingresos
                WHERE lote = NEW.lote
                LIMIT 1;

                SET NEW.cochinilla_ingreso_id = v_ingreso_id;

            END IF;
        END
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS `trg_cochinilla_venteados_bi`');
        DB::unprepared('DROP TRIGGER IF EXISTS `trg_cochinilla_venteados_bu`');
        Schema::dropIfExists('cochinilla_venteados');
    }
};
