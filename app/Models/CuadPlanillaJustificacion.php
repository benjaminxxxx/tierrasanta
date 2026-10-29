<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Justificación de un trabajador de planilla que el mismo día figura como cuadrillero (ver CuadrillaPersonaReglas). */
class CuadPlanillaJustificacion extends Model
{
    protected $table = 'cuad_planilla_justificaciones';

    protected $fillable = ['cuadrillero_id', 'fecha', 'plan_empleado_id', 'motivo', 'fecha_real', 'nota', 'creado_por'];

    protected $casts = [
        'fecha' => 'date',
        'fecha_real' => 'date',
    ];

    public function cuadrillero()
    {
        return $this->belongsTo(Cuadrillero::class, 'cuadrillero_id')->withTrashed();
    }
}
