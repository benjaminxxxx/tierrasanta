<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Un cierre o una reapertura de un mes de caja (historial; nunca se edita ni se borra). */
class CajaCierreEvento extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'caja_cierre_eventos';

    protected $fillable = [
        'caja_cierre_id', 'anio', 'mes', 'accion', 'saldo', 'movimientos', 'motivo', 'usuario_id', 'usuario_nombre',
    ];

    protected $casts = [
        'saldo' => 'decimal:2',
        'created_at' => 'datetime',
    ];
}
