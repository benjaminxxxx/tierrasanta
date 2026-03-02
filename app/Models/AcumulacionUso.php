<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcumulacionUso extends Model
{
    use HasFactory;

    protected $table = 'reg_acumulacion_usos';

    protected $fillable = [
        'consolidado_destino_id',
        'consolidado_origen_id',
        'minutos_consumidos',
    ];

    public function consolidadoDestino()
    {
        return $this->belongsTo(ConsolidadoRiego::class, 'consolidado_destino_id');
    }

    public function consolidadoOrigen()
    {
        return $this->belongsTo(ConsolidadoRiego::class, 'consolidado_origen_id');
    }
}
