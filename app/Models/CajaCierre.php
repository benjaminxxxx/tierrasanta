<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Cierre mensual de caja: cerrado, sus movimientos no se pueden crear, editar ni eliminar. */
class CajaCierre extends Model
{
    public const CERRADO = 'cerrado';
    public const ABIERTO = 'abierto';

    protected $table = 'caja_cierres';

    protected $fillable = [
        'anio', 'mes', 'estado', 'saldo_final', 'movimientos', 'cerrado_por', 'cerrado_at',
        'reabierto_por', 'reabierto_at', 'motivo_reapertura',
    ];

    protected $casts = [
        'cerrado_at' => 'datetime',
        'reabierto_at' => 'datetime',
        'saldo_final' => 'decimal:2',
    ];

    public function eventos()
    {
        return $this->hasMany(CajaCierreEvento::class)->orderBy('created_at')->orderBy('id');
    }

    public function cerradoPor()
    {
        return $this->belongsTo(User::class, 'cerrado_por');
    }
}
