<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReporteCostoPlanilla extends Model
{
    protected $fillable = [
        'campos_campanias_id',
        'fecha',
        'documento',
        'empleado_nombre',
        'campo',
        'horas_totales',
        'hora_inicio',
        'hora_salida',
        'factor',
        'hora_diferencia',
        'hora_diferencia_entero',
        'costo_hora',
        'gasto',
        'gasto_bono',
    ];
    /**
     *  "campania_id" => 8
     *  "fecha" => "2025-01-22"
     * "documento" => "43382707"
     * "empleado_nombre" => "CHOQUEHUAYTA HUALLPA, ESTEFANIA"
     * "campo" => "1"
     * "horas_totales" => "08:00"
     * "hora_inicio" => "07:00"
     *  "hora_salida" => "16:00"
     * "factor" => 0.88888888888889
     * "hora_diferencia" => "09:00"
     * "hora_diferencia_entero" => 9.0
     * "costo_hora" => "9.37463"
     * "gasto" => 74.99704
     * "gasto_bono" => 0
     */
}
