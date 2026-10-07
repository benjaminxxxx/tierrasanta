<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Lo que significó un código de labor en un rango de fechas antes de reasignarse (ver CampoLaborVigenciaConsulta). */
class LaborVigencia extends Model
{
    protected $table = 'labor_vigencias';

    protected $fillable = ['codigo', 'nombre_labor', 'codigo_mano_obra', 'tipo_asistencia_codigo', 'unidades', 'desde', 'hasta', 'motivo', 'creado_por'];

    protected $casts = ['desde' => 'date', 'hasta' => 'date'];

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }
}
