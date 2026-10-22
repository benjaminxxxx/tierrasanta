<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Costos de producción por campaña: cada "consulta" guarda una versión (foto) de lo que había en
 * resumen_costo_diarios, con el área y el tipo de cambio de ese momento. Así queda la historia aunque la BDD de
 * costos se regenere después. La última versión es la que se muestra.
 */
return new class extends Migration {
    public function up(): void
    {
        // Tolerante: una primera ejecución creó las tablas y falló al crear el índice del detalle (nombre de 67
        // caracteres, MySQL admite 64). Si ya existen se respetan sus datos y solo se agrega el índice.
        if (Schema::hasTable('campania_costos_produccion') && Schema::hasTable('campania_costos_produccion_detalles')) {
            $indices = array_column(\Illuminate\Support\Facades\DB::select('SHOW INDEX FROM campania_costos_produccion_detalles'), 'Key_name');
            if (!in_array('ccp_detalles_produccion_orden_index', $indices, true)) {
                Schema::table('campania_costos_produccion_detalles', function (Blueprint $table) {
                    $table->index(['costo_produccion_id', 'orden'], 'ccp_detalles_produccion_orden_index');
                });
            }
            return;
        }

        Schema::create('campania_costos_produccion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campo_campania_id')->constrained('campos_campanias')->cascadeOnDelete();
            $table->decimal('area', 12, 4)->nullable();          // ha de la campaña al generar
            $table->decimal('tipo_cambio', 10, 4)->nullable();   // S/ por US$ de la campaña al generar
            $table->date('fecha_desde')->nullable();
            $table->date('fecha_hasta')->nullable();
            $table->decimal('total_soles', 24, 4)->default(0);
            $table->unsignedInteger('filas_origen')->default(0); // filas de resumen_costo_diarios leídas
            $table->foreignId('generado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['campo_campania_id', 'created_at']);
        });

        Schema::create('campania_costos_produccion_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('costo_produccion_id')->constrained('campania_costos_produccion')->cascadeOnDelete();
            $table->unsignedInteger('orden');
            $table->string('seccion', 40);       // mano_obra, maquinaria, fertilizante, pesticida, costo_operativo, costo_fijo, otros
            $table->string('grupo', 150)->nullable(); // mano de obra (Siembra, Sanidad…) en la sección de mano de obra
            $table->string('item', 255);
            $table->string('codigo', 40)->nullable(); // código de labor, si aplica
            $table->decimal('cantidad', 18, 4)->nullable(); // jornales o cantidad de insumo (total, no por ha)
            $table->decimal('costo_soles', 24, 4)->default(0);

            $table->index(['costo_produccion_id', 'orden'], 'ccp_detalles_produccion_orden_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campania_costos_produccion_detalles');
        Schema::dropIfExists('campania_costos_produccion');
    }
};
