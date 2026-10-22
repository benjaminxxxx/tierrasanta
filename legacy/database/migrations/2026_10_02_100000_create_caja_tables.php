<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Caja: registro real de ingresos y salidas de dinero (antes el Excel "Bancos TSH", hoja BASE).
 *
 * Es una fuente de verdad propia: compras, ventas o pagos registrados en otros módulos NO escriben aquí.
 * Solo se cuadra contra ellos. Cada mes se cierra; cerrado, no se modifica nada hasta reabrirlo.
 */
return new class extends Migration {
    public function up(): void
    {
        // Hoja "Valida": tipo → clasificador 1 → clasificador 2, con el color con que se pinta la fila
        Schema::create('caja_clasificadores', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['INGRESO', 'EGRESO']);
            $table->string('grupo', 120)->nullable()->comment('Columna TIPO de la hoja Valida (VENTA, OTROS INGRESOS…)');
            $table->string('clasificador_1', 150);
            $table->string('clasificador_2', 200);
            $table->string('color_fondo', 7)->nullable();
            $table->string('color_texto', 7)->nullable();
            $table->boolean('negrita')->default(false);
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['clasificador_1', 'clasificador_2']);
        });

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
            $table->string('importe_detalle', 255)->nullable()->comment('Operación con que se calculó (-100-315)');
            $table->decimal('tipo_cambio', 8, 4)->nullable()->comment('TC del día para el gasto dolarizado');
            $table->string('color_fondo', 7)->nullable()->comment('Color personalizado de la fila');
            $table->string('color_texto', 7)->nullable();
            $table->boolean('negrita')->nullable();
            $table->unsignedInteger('orden')->default(0)->comment('Orden dentro del día');
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['fecha', 'orden']);
            $table->index(['clasificador_1', 'clasificador_2']);
        });

        // Tipo de cambio diario (hoja "Tipo de Cambio")
        Schema::create('caja_tipos_cambio', function (Blueprint $table) {
            $table->date('fecha')->primary();
            $table->decimal('valor', 8, 4);
            $table->timestamps();
        });

        // Dónde está repartido el dinero (AQP, Naranja, Aqp-Flavia, Cuadrillas…)
        Schema::create('caja_fuentes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Arqueo: saldo real en cada fuente en una fecha; su suma se compara con el disponible
        Schema::create('caja_arqueos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('observacion', 255)->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('fecha');
        });
        Schema::create('caja_arqueo_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caja_arqueo_id')->constrained('caja_arqueos')->cascadeOnDelete();
            $table->foreignId('caja_fuente_id')->constrained('caja_fuentes');
            $table->decimal('monto', 14, 2);
            $table->timestamps();
            $table->unique(['caja_arqueo_id', 'caja_fuente_id']);
        });

        Schema::create('caja_cierres', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('mes');
            $table->enum('estado', ['cerrado', 'abierto'])->default('cerrado');
            $table->decimal('saldo_final', 14, 2)->nullable();
            $table->unsignedInteger('movimientos')->default(0);
            $table->foreignId('cerrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cerrado_at')->nullable();
            $table->foreignId('reabierto_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reabierto_at')->nullable();
            $table->string('motivo_reapertura', 500)->nullable();
            $table->timestamps();
            $table->unique(['anio', 'mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_cierres');
        Schema::dropIfExists('caja_arqueo_detalles');
        Schema::dropIfExists('caja_arqueos');
        Schema::dropIfExists('caja_fuentes');
        Schema::dropIfExists('caja_tipos_cambio');
        Schema::dropIfExists('caja_movimientos');
        Schema::dropIfExists('caja_clasificadores');
    }
};
