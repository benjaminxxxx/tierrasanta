<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsKardexReporteDetalle extends Model
{
    use HasFactory;

    protected $table = 'ins_kardex_reporte_detalles';

    protected $fillable = [
        'reporte_id',
        'ins_kardex_id',
        'codigo_existencia',
        'nombre_producto',
        'condicion',
        'unidad_medida',
        'total_entradas_unidades',
        'total_entradas_importe',
        'total_salidas_unidades',
        'total_salidas_importe',
        'saldo_unidades',
        'saldo_importe',
    ];

    public function reporte()
    {
        return $this->belongsTo(InsKardexReporte::class, 'reporte_id');
    }
}
