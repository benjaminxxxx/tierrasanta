<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cuadrilla: lo que dejó la versión anterior y ya no se usa.
 * - cuad_resumen_tramos: copia del cuadro resumen; ahora se calcula al abrirlo (ResumenTramoInformativoServicio).
 * - cuad_registros_diarios:
 *   - tramo_pagado_jornal_id, tramo_pagado_bono_id: siempre vacías (el pago se marca con desglose_detalle_id).
 *   - bono_esta_pagado: siempre 0; el pago del bono está en cada bono (cuad_bonos_actividades.esta_pagado).
 *   - costo_dia: el costo del día se calcula (jornal por las horas no a destajo); la columna no se leía.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('cuad_resumen_tramos');

        foreach (['tramo_pagado_jornal_id', 'tramo_pagado_bono_id'] as $columna) {
            $fk = DB::selectOne('SELECT CONSTRAINT_NAME n FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL', ['cuad_registros_diarios', $columna])?->n;
            if ($fk) {
                DB::statement("ALTER TABLE cuad_registros_diarios DROP FOREIGN KEY {$fk}");
            }
        }
        Schema::table('cuad_registros_diarios', function (Blueprint $table) {
            $table->dropColumn(['tramo_pagado_jornal_id', 'tramo_pagado_bono_id', 'bono_esta_pagado', 'costo_dia']);
        });
    }

    public function down(): void
    {
        Schema::table('cuad_registros_diarios', function (Blueprint $table) {
            $table->decimal('costo_dia', 10, 2)->default(0);
            $table->boolean('bono_esta_pagado')->default(false);
            $table->foreignId('tramo_pagado_jornal_id')->nullable()->constrained('cuad_tramos_laborales')->nullOnDelete();
            $table->foreignId('tramo_pagado_bono_id')->nullable()->constrained('cuad_tramos_laborales')->nullOnDelete();
        });
        // La tabla cuad_resumen_tramos no se recrea: su contenido se calcula en el momento
    }
};
