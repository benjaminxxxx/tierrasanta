<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CajaArqueoDetalle extends Model
{
    protected $table = 'caja_arqueo_detalles';

    protected $fillable = ['caja_arqueo_id', 'caja_fuente_id', 'monto'];

    protected $casts = ['monto' => 'decimal:2'];

    public function fuente()
    {
        return $this->belongsTo(CajaFuente::class, 'caja_fuente_id');
    }
}
