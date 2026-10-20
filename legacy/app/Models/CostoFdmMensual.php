<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostoFdmMensual extends Model
{
    protected $table = 'costo_fdm_mensuals';
    protected $fillable = [
        'monto_blanco',
        'monto_negro',
        'fecha',
        'destinatario',
        'descripcion'
    ];
}
