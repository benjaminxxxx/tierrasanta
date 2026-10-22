<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cochinilla_infestaciones', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo_infestacion', ['infestacion', 'reinfestacion']);
            $table->date('fecha');
            $table->string('campo_nombre');
            $table->double('area')->nullable();
            $table->foreignId('campo_campania_id')->nullable()->constrained('campos_campanias')->nullOnDelete();
            $table->double('kg_madres');
            $table->string('campo_origen_nombre');
            $table->enum('metodo', ['carton', 'tubo', 'malla']);
            $table->integer('numero_envases');
            $table->decimal('capacidad_envase', 10, 3);
            $table->decimal('porcentaje_malla_rashel', 6, 2)->nullable();
            $table->string('procedencia_infestadores', 80)->nullable();
            $table->timestamps();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('editado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->foreign('campo_nombre')->references('nombre')->on('campos');
            $table->foreign('campo_origen_nombre')->references('nombre')->on('campos');
        });

        // Triggers (las tablas que usan se resuelven al ejecutarse, no al crearse)
        DB::unprepared(<<<'SQL'
CREATE TRIGGER `trg_infestaciones_bi` BEFORE INSERT ON `cochinilla_infestaciones` FOR EACH ROW
BEGIN
            DECLARE v_campania_id BIGINT;

            SELECT id
            INTO v_campania_id
            FROM campos_campanias
            WHERE campo = NEW.campo_nombre
              AND fecha_inicio <= NEW.fecha
              AND (
                    fecha_fin IS NULL
                    OR NEW.fecha <= fecha_fin
                  )
            ORDER BY fecha_inicio DESC
            LIMIT 1;

            SET NEW.campo_campania_id = v_campania_id;
        END
SQL);
        DB::unprepared(<<<'SQL'
CREATE TRIGGER `trg_infestaciones_bu` BEFORE UPDATE ON `cochinilla_infestaciones` FOR EACH ROW
BEGIN
            DECLARE v_campania_id BIGINT;

            IF NEW.fecha <> OLD.fecha
               OR NEW.campo_nombre <> OLD.campo_nombre THEN

                SELECT id
                INTO v_campania_id
                FROM campos_campanias
                WHERE campo = NEW.campo_nombre
                  AND fecha_inicio <= NEW.fecha
                  AND (
                        fecha_fin IS NULL
                        OR NEW.fecha <= fecha_fin
                      )
                ORDER BY fecha_inicio DESC
                LIMIT 1;

                SET NEW.campo_campania_id = v_campania_id;

            END IF;
        END
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS `trg_infestaciones_bi`');
        DB::unprepared('DROP TRIGGER IF EXISTS `trg_infestaciones_bu`');
        Schema::dropIfExists('cochinilla_infestaciones');
    }
};
