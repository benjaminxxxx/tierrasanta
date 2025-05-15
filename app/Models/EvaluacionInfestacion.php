<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class EvaluacionInfestacion extends Model
{
    use HasFactory;
    protected $table = "evaluacion_infestaciones";
    protected $fillable = [
        'fecha',
        'campo_campania_id'
    ];
    public function detalles()
    {
        return $this->hasMany(EvaluacionInfestacionDetalle::class);
    }
    public function campoCampania()
    {
        return $this->belongsTo(CampoCampania::class, 'campo_campania_id');
    }
    public function getDiasAttribute()
    {
        // Obtener la fecha base
        $evaluacionFecha = Carbon::parse($this->fecha);

        // Obtener fechas relacionadas
        $infestacion = $this->campoCampania?->infestacion_fecha;
        $reinfestacion = $this->campoCampania?->reinfestacion_fecha;

        // Convertir a instancias de Carbon si no son null
        $fechas = collect([
            $infestacion ? Carbon::parse($infestacion) : null,
            $reinfestacion ? Carbon::parse($reinfestacion) : null,
        ])->filter(); // elimina los null

        // Si no hay fechas válidas, retorna 0
        if ($fechas->isEmpty()) {
            return 0;
        }

        // Tomar la fecha más reciente
        $fechaInicio = $fechas->max();

        // Calcular diferencia en días
        return $fechaInicio->diffInDays($evaluacionFecha);
    }
    public function getPromedioAttribute()
    {
        $total = 0;
        $count = 0;

        foreach ($this->detalles as $detalle) {
            if (!is_null($detalle->piso_2)) {
                $total += $detalle->piso_2;
                $count++;
            }
            if (!is_null($detalle->piso_3)) {
                $total += $detalle->piso_3;
                $count++;
            }
        }

        return $count > 0 ? round($total / $count, 0) : 0;
    }


}
