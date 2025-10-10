<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Observacion extends Model
{
    use HasFactory;
    protected $table = 'observaciones';

    /**
     * Atributos asignables masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'detalle_observacion',
        'horas',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'documento',
        'tipo_empleado',
    ];
    public function getNombreRegadorAttribute()
    {
        $documento = $this->documento;

        return optional(PlanEmpleado::where('documento', $documento)->first())->nombre_completo
            ?? Cuadrillero::where('dni', $documento)->value('nombre_completo')
            ?? 'NN';
    }
}
