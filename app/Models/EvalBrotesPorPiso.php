<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvalBrotesPorPiso extends Model
{
    protected $table = 'eval_brotes_por_pisos';

    protected $fillable = [
        'campania_id',
        'fecha',
        'metros_cama_ha',
        'evaluador',
    ];

    protected $appends = [
        'promedio_actual_brotes_2piso',
        'promedio_brotes_2piso_n_dias',
        'promedio_actual_brotes_3piso',
        'promedio_brotes_3piso_n_dias',
        'promedio_actual_total_brotes_2y3piso',
        'promedio_total_brotes_2y3piso_n_dias',
    ];
    public function campania()
    {
        return $this->belongsTo(CampoCampania::class, 'campania_id');
    }
    public function detalles()
    {
        return $this->hasMany(EvalBrotesPorPisoDetalle::class, 'brotes_x_piso_id');
    }

    private function avgOrZero($collection, $key)
    {
        $v = $collection->avg($key);
        return $v ? round($v, 0) : 0;
    }

    // ----------------------------
    //  Promedios 2° piso
    // ----------------------------
    public function getPromedioActualBrotes2pisoAttribute()
    {
        return $this->avgOrZero($this->detalles, 'brotes_2p_actual_por_mt');
    }

    public function getPromedioBrotes2pisoNDiasAttribute()
    {
        return $this->avgOrZero($this->detalles, 'brotes_2p_despues_por_mt');
    }

    // ----------------------------
    //  Promedios 3° piso
    // ----------------------------
    public function getPromedioActualBrotes3pisoAttribute()
    {
        return $this->avgOrZero($this->detalles, 'brotes_3p_actual_por_mt');
    }

    public function getPromedioBrotes3pisoNDiasAttribute()
    {
        return $this->avgOrZero($this->detalles, 'brotes_3p_despues_por_mt');
    }

    // ----------------------------
    // Totales
    // ----------------------------
    public function getPromedioActualTotalBrotes2y3pisoAttribute()
    {
        return $this->avgOrZero($this->detalles, 'total_actual_por_mt');
    }

    public function getPromedioTotalBrotes2y3pisoNDiasAttribute()
    {
        return $this->avgOrZero($this->detalles, 'total_despues_por_mt');
    }
}
