<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Una fila de un envío: nueva, modificada o eliminada en la caja de oficina, y qué pasó al anexarla. */
class CajaOficinaEnvioDetalle extends Model
{
    protected $table = 'caja_oficina_envio_detalles';

    protected $fillable = [
        'caja_oficina_envio_id', 'caja_oficina_movimiento_id', 'accion', 'datos', 'datos_antes',
        'resultado', 'resultado_nota', 'caja_movimiento_id',
    ];

    protected $casts = ['datos' => 'array', 'datos_antes' => 'array'];
}
