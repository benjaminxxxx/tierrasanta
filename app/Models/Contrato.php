<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contrato extends Model
{
    protected $fillable = [
        'empleado_id',
        'tipo_contrato',
        'fecha_inicio',
        'fecha_fin',
        'sueldo',
        'cargo_codigo',
        'grupo_codigo',
        'compensacion_vacacional',
        'tipo_planilla',
        'descuento_sp_id',
        'esta_jubilado',
        'modalidad_pago',
        'motivo_despido',
    ];
    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }
    public function descuento()
    {
        return $this->belongsTo(DescuentoSP::class, 'descuento_sp_id');
    }
}
