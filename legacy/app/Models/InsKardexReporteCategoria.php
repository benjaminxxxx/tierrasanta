<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsKardexReporteCategoria extends Model
{
    protected $table = 'ins_kardex_reporte_categorias';
    protected $fillable = [
        'reporte_id',
        'categoria_codigo',
    ];
}
