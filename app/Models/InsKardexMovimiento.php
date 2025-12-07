<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsKardexMovimiento extends Model
{
    protected $table = 'ins_kardex_movimientos';

    protected $fillable = [
        'kardex_id',
        'fecha',
        'tipo_mov',

        // Referencias de documentos
        'tipo_documento',
        'serie',
        'numero',
        'tipo_operacion',

        // Entradas
        'entrada_cantidad',
        'entrada_costo_unitario',
        'entrada_costo_total',

        // Salidas
        'salida_cantidad',
        'salida_lote',
        'salida_maquinaria',

        // Costos de salida
        'salida_costo_unitario',
        'salida_costo_total',

        // Distribución (JSON)
        'detalle_distribucion',

        // Estado
        'estado',
    ];
}
