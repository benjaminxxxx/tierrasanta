<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuadTrabajoExtra extends Model
{
    use HasFactory;

    // Si tu migración creó la tabla 'cuad_trabajos_extra' explícitamente,
    // forzamos el nombre aquí para evitar problemas de pluralización.
    protected $table = 'cuad_trabajos_extra';

    /**
     * Campos asignables en masa.
     */
    protected $fillable = [
        'cuadrillero_id',
        'fecha',
        'horas',
        'costo_x_hora',
        'monto_total',
        'esta_pagado',
        'orden'
    ];
    public function cuadrillero(){
        return $this->belongsTo(Cuadrillero::class,'cuadrillero_id');
    }
}
