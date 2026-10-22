<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ficha de maquinaria:
 * - combustible_producto_id: el combustible que usa (gasolina / petróleo). Una salida de otro
 *   combustible para esta máquina se avisa como tarea pendiente.
 * - usa_distribucion: si reparte su trabajo por campo (distribución de combustible). Si no
 *   (ej. motos de los trabajadores) su combustible va directo a FDM y no se exige distribución.
 * - consumo_modo / consumo_estimado: 'km' → km por unidad de combustible; 'hora' → unidades por
 *   hora de encendido (podadora, fumigadora…). Estimado de referencia.
 * - placa (opcional) y foto.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('maquinarias', function (Blueprint $table) {
            $table->foreignId('combustible_producto_id')->nullable()->after('alias_blanco')
                ->constrained('productos')->nullOnDelete();
            $table->boolean('usa_distribucion')->default(true)->after('combustible_producto_id');
            $table->string('consumo_modo', 10)->nullable()->after('usa_distribucion'); // km | hora
            $table->decimal('consumo_estimado', 10, 3)->nullable()->after('consumo_modo');
            $table->string('placa', 20)->nullable()->after('consumo_estimado');
            $table->string('foto')->nullable()->after('placa');
        });

        // Valores iniciales desde el historial: el combustible que siempre usó cada máquina.
        // Las de gasolina (motos, podadora, fumigadora) no reparten su trabajo por campo.
        $historial = DB::table('almacen_producto_salidas as s')
            ->join('productos as p', 'p.id', '=', 's.producto_id')
            ->where('p.categoria_codigo', 'combustible')
            ->whereNotNull('s.maquinaria_id')
            ->selectRaw('s.maquinaria_id, s.producto_id, UPPER(p.nombre_comercial) as nombre, COUNT(*) as n')
            ->groupBy('s.maquinaria_id', 's.producto_id', 'p.nombre_comercial')
            ->orderByDesc('n')
            ->get()
            ->groupBy('maquinaria_id');

        foreach ($historial as $maquinariaId => $usos) {
            $principal = $usos->first();
            DB::table('maquinarias')->where('id', $maquinariaId)->update([
                'combustible_producto_id' => $principal->producto_id,
                'usa_distribucion' => !str_contains($principal->nombre, 'GASOLINA'),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('maquinarias', function (Blueprint $table) {
            $table->dropConstrainedForeignId('combustible_producto_id');
            $table->dropColumn(['usa_distribucion', 'consumo_modo', 'consumo_estimado', 'placa', 'foto']);
        });
    }
};
