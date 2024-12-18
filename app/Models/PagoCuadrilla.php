<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoCuadrilla extends Model
{
    protected $table = 'pago_cuadrillas';

    protected $fillable = [
        'cuadrillero_id',
        'monto_trabajado',
        'monto_pagado',
        'saldo_pendiente',
        'fecha_inicio',
        'fecha_fin',
        'fecha_pago',
        'anio_contable',
        'mes_contable',
        'estado',
        'pago_referencia_id',
        'creado_por',
        'actualizado_por'
    ];
    public function getEstadoDetalleAttribute()
    {
        $nombreDetalle = '';
        switch ($this->estado) {
            case 'pago_parcial':
                $nombreDetalle = 'Adelanto';
                break;
            case 'pago_completo':
                $nombreDetalle = 'Pago Completo';
                break;
            default:
                $nombreDetalle = '-';
                break;
        }
        return $nombreDetalle;
    }
    public function getFechaContableAttribute()
    {
        return $this->mes_contable . '-' . $this->anio_contable;
    }
    public function cuadrillero()
    {
        return $this->belongsTo(Cuadrillero::class);
    }

    public function pagoReferencia()
    {
        return $this->belongsTo(PagoCuadrilla::class, 'pago_referencia_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function actualizador()
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }
}
