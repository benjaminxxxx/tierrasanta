<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuadrillaAsistenciaGrupo extends Model
{
    use HasFactory;
    protected $fillable = [
        'cuadrilla_asistencia_id',
        'codigo',
        'color',
        'nombre',
        'costo_dia',
        'modalidad_pago',
        'total_costo',
        'numero_recibo',
        'fecha_pagado',
        'condicion',
        'dinero_recibido',
        'saldo',
        'adelanto',
        'total'
    ];

    public function asistencia()
    {
        return $this->belongsTo(CuadrillaAsistencia::class);
    }
}
