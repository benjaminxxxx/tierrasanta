<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * compra_detalles.costo_total_kardex se creó con default 0 y nunca se llenó en las compras existentes
 * (751 de 753). La migración 2026_09_28_180000 copió ese 0 a movimientos_stock.costo_total, así que el kardex
 * valorizaba esas entradas en 0 aunque costo_unitario estuviera bien.
 *
 * Regla de CompraService::calcularLinea: costo_total_kardex = total_linea (con IGV, sin crédito fiscal).
 * Se marca updated_at en los movimientos corregidos para que los kardex afectados queden desactualizados
 * (KardexActualizacionServicio) y se regeneren con el costo correcto.
 */
return new class extends Migration {
    public function up(): void
    {
        DB::table('compra_detalles')
            ->where('costo_total_kardex', 0)
            ->where('total_linea', '>', 0)
            ->update(['costo_total_kardex' => DB::raw('total_linea')]);

        DB::table('movimientos_stock as m')
            ->join('compra_detalles as d', 'd.id', '=', 'm.origen_id')
            ->where('m.origen_type', 'App\\Models\\CompraDetalle')
            ->where(fn($q) => $q->whereNull('m.costo_total')->orWhere('m.costo_total', 0))
            ->where('d.costo_total_kardex', '>', 0)
            ->update([
                'm.costo_total' => DB::raw('d.costo_total_kardex'),
                'm.costo_unitario' => DB::raw('d.costo_unitario_base'),
                'm.updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Corrección de datos: no se revierte (volver a 0 dejaría el kardex otra vez sin costo).
    }
};
