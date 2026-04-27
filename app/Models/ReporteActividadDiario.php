<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReporteActividadDiario extends Model
{
    protected $table    = 'v_reporte_actividades_diario';
    protected $primaryKey = 'actividad_id';
    public    $timestamps = false;

    protected $casts = [
        'fecha'           => 'date',
        'total_metodos'   => 'integer',
        'total_planilla'  => 'integer',
        'total_cuadrilla' => 'integer',
        'unidades'        => 'integer',
        'recojos'         => 'integer',
    ];

    // Solo lectura
    public static function porFecha(string $fecha)
    {
        return static::whereDate('fecha', $fecha)
            ->orderByDesc('total_cuadrilla')
            ->orderByDesc('total_planilla')
            ->get();
    }
}