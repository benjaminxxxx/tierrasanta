<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoblacionPlantas extends Model
{
    use HasFactory;

    protected $table = 'poblacion_plantas';

    protected $fillable = [

        'area_lote',
        'metros_cama',
        'evaluador',
        'empleado_id',
        'cuadrillero_id',
        'fecha',
        'campania_id',
        'tipo_evaluacion'
    ];

    public function empleado()
    {
        return $this->belongsTo(PlanEmpleado::class, 'empleado_id');
    }

    public function cuadrillero()
    {
        return $this->belongsTo(Cuadrillero::class, 'cuadrillero_id');
    }
    public function campania()
    {
        return $this->belongsTo(CampoCampania::class);
    }

    public function detalles()
    {
        return $this->hasMany(PoblacionPlantasDetalle::class);
    }

    public function getPromedioPlantasXCamaAttribute()
    {
        return $this->detalles->avg('plantas_x_cama');
    }
    //mejor presicion
    public function getPromedioPlantasXMetroAttribute()
    {
        if ($this->detalles->isEmpty()) {
            return 0;
        }

        $valores = $this->detalles->map(function ($detalle) {
            // División precisa, sin redondear
            if ($detalle->longitud_cama > 0) {
                return $detalle->plantas_x_cama / $detalle->longitud_cama;
            }
            return 0;
        });

        // Retorna el promedio de los cálculos reales
        return $valores->avg();
    }


    public function getPromedioPlantasHaAttribute()
    {
        return $this->metros_cama * $this->promedio_plantas_x_metro;
    }
    public function getTipoEvaluacionLegibleAttribute()
    {
        $tipo = '-';

        switch ($this->tipo_evaluacion) {
            case 'dia_cero':
                $tipo = 'Día Cero';
                break;
            case 'resiembra':
                $tipo = 'Resiembra';
                break;
            default:
                $tipo = 'Desconocido';
                break;
        }
        return $tipo;
    }
}
