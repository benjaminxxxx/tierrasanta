<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KardexConsolidado extends Model
{
    protected $fillable = [
        'kardex_id',
        'codigo_existencia',
        'producto_id',
        'producto_nombre',
        'tipo_kardex',
        'categoria_producto',
        'condicion',
        'unidad_medida',
        'total_entradas_unidades',
        'total_entradas_importe',
        'total_salidas_unidades',
        'total_salidas_importe',
        'saldo_unidades',
        'saldo_importe',
    ];
}
