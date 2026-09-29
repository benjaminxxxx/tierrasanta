<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * El IGV pasa a ser gasto también en factura (no hay crédito fiscal): el costo de cada
     * detalle debe ser la parte proporcional del TOTAL pagado, no del subtotal.
     * Recalcula los servicios registrados con la regla anterior.
     */
    public function up(): void
    {
        DB::transaction(function () {
            $servicios = DB::table('servicios_campo')->whereColumn('costo_total', '<>', 'total')->get();

            foreach ($servicios as $servicio) {
                $detalles = DB::table('servicios_campo_detalles')
                    ->where('servicio_campo_id', $servicio->id)
                    ->orderBy('id')
                    ->get();

                $cantidadTotal = (float) $detalles->sum('cantidad');
                if ($cantidadTotal <= 0) {
                    continue;
                }

                // Prorrateo exacto: el redondeo se asigna a la última fila.
                $total = (float) $servicio->total;
                $acumulado = 0;
                $ultimo = $detalles->count() - 1;
                foreach ($detalles->values() as $i => $detalle) {
                    $costo = $i === $ultimo
                        ? round($total - $acumulado, 2)
                        : round($total * (float) $detalle->cantidad / $cantidadTotal, 2);
                    $acumulado += $costo;

                    DB::table('servicios_campo_detalles')->where('id', $detalle->id)->update(['costo' => $costo]);
                }

                DB::table('servicios_campo')->where('id', $servicio->id)->update(['costo_total' => $servicio->total]);
            }
        });
    }

    /**
     * No se revierte: la regla anterior (factura sin IGV) ya no aplica.
     */
    public function down(): void
    {
    }
};
