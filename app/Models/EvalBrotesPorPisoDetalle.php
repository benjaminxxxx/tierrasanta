<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvalBrotesPorPisoDetalle extends Model
{
    protected $table = 'eval_brotes_por_piso_detalles';

    protected $fillable = [
        'brotes_x_piso_id',
        'numero_cama',
        'longitud_cama',

        'brotes_aptos_2p_actual',
        'brotes_aptos_2p_despues_n_dias',

        'brotes_aptos_3p_actual',
        'brotes_aptos_3p_despues_n_dias',
    ];

    protected $appends = [
        'brotes_2p_actual_por_mt',
        'brotes_2p_despues_por_mt',
        'brotes_3p_actual_por_mt',
        'brotes_3p_despues_por_mt',
        'total_actual_por_mt',
        'total_despues_por_mt',
    ];

    // Relación inversa
    public function cabecera()
    {
        return $this->belongsTo(EvalBrotesPorPiso::class, 'brotes_x_piso_id');
    }

    // ----------------------------
    //  Cálculo base
    // ----------------------------
    private function convertirPorMetro($valor)
    {
        if (!$this->longitud_cama || !$this->cabecera) {
            return 0;
        }

        return ($valor / $this->longitud_cama) * $this->cabecera->metros_cama_ha;
    }

    // ----------------------------
    //  Atributos calculados 2° piso
    // ----------------------------
    public function getBrotes2pActualPorMtAttribute()
    {
        return $this->convertirPorMetro($this->brotes_aptos_2p_actual);
    }

    public function getBrotes2pDespuesPorMtAttribute()
    {
        return $this->convertirPorMetro($this->brotes_aptos_2p_despues_n_dias);
    }

    // ----------------------------
    //  Atributos calculados 3° piso
    // ----------------------------
    public function getBrotes3pActualPorMtAttribute()
    {
        return $this->convertirPorMetro($this->brotes_aptos_3p_actual);
    }

    public function getBrotes3pDespuesPorMtAttribute()
    {
        return $this->convertirPorMetro($this->brotes_aptos_3p_despues_n_dias);
    }

    // ----------------------------
    // Totales del detalle
    // ----------------------------
    public function getTotalActualPorMtAttribute()
    {
        return $this->brotes_2p_actual_por_mt + $this->brotes_3p_actual_por_mt;
    }

    public function getTotalDespuesPorMtAttribute()
    {
        return $this->brotes_2p_despues_por_mt + $this->brotes_3p_despues_por_mt;
    }
}
