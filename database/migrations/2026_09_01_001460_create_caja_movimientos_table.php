<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja_movimientos', function (Blueprint $table) {
            $table->id();
            $table->string('empresa', 150);
            $table->unsignedInteger('numero_caja')->nullable()->comment('Correlativo del mes; null si es contable');
            $table->boolean('es_contable')->default(false)->comment('Viene de la caja contable del contador (siempre BLA.)');
            $table->enum('condicion', ['NEG', 'BLA'])->default('NEG');
            $table->string('categoria', 100)->nullable()->comment('Comprobante (F/E001-208) o "Contable"');
            $table->string('codigo', 100)->nullable();
            $table->string('beneficiario', 200)->nullable();
            $table->text('descripcion')->nullable()->comment('GASTOS B+N');
            $table->foreignId('caja_clasificador_id')->nullable()->constrained('caja_clasificadores')->nullOnDelete();
            $table->string('clasificador_1', 150)->nullable();
            $table->string('clasificador_2', 200)->nullable();
            $table->string('subgrupo_ng', 100)->nullable();
            $table->string('subgrupo_bl', 100)->nullable();
            $table->string('moneda', 3)->default('PEN');
            $table->date('fecha');
            $table->unsignedTinyInteger('semana')->comment('Semana del mes (SEM-1…SEM-5)');
            $table->text('tipo_documento')->nullable()->comment('Texto libre: tipo de documento u observación');
            $table->string('numero_documento', 200)->nullable();
            $table->string('situacion_cheque', 150)->nullable();
            $table->decimal('importe_usd', 14, 2)->nullable()->comment('Si se pagó en dólares');
            $table->decimal('tipo_cambio_operacion', 8, 4)->nullable()->comment('TC con que se pagó en dólares');
            $table->decimal('importe', 14, 2)->comment('Soles: + ingreso, − egreso');
            $table->boolean('es_saldo_inicial')->default(false)->comment('Saldo del año anterior: no es un ingreso');
            $table->unsignedBigInteger('caja_oficina_movimiento_id')->nullable()->comment('Fila de la caja de oficina de la que viene');
            $table->boolean('editado_manual')->default(false)->comment('Se editó aquí después de anexarla');
            $table->string('importe_detalle')->nullable()->comment('Operación con que se calculó (-100-315)');
            $table->decimal('tipo_cambio', 8, 4)->nullable()->comment('TC del día para el gasto dolarizado');
            $table->string('color_fondo', 7)->nullable()->comment('Color personalizado de la fila');
            $table->string('color_texto', 7)->nullable();
            $table->boolean('negrita')->nullable();
            $table->unsignedInteger('orden')->default(0)->comment('Orden dentro del día');
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('eliminado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('motivo_eliminacion', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('caja_oficina_movimiento_id');
            $table->index(['clasificador_1', 'clasificador_2']);
            $table->index(['fecha', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_movimientos');
    }
};
