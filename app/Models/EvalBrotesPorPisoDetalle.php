<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Una cama evaluada. Por piso (2°, 3° y 4°): brotes aptos actuales y brotes del piso para eliminar
 * (columnas "_despues_n_dias": se llamaban "después de N días", el dato siempre fue "para eliminar").
 * El 4° piso es opcional: las evaluaciones anteriores a oct-2026 no lo tienen.
 */
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

        'brotes_aptos_4p_actual',
        'brotes_aptos_4p_despues_n_dias',
    ];

    protected $appends = [
        'brotes_2p_actual_por_mt',
        'brotes_2p_despues_por_mt',
        'brotes_3p_actual_por_mt',
        'brotes_3p_despues_por_mt',
        'brotes_4p_actual_por_mt',
        'brotes_4p_despues_por_mt',
        'total_actual_por_mt',
        'total_despues_por_mt',
    ];

    // Relación inversa
    public function cabecera()
    {
        return $this->belongsTo(EvalBrotesPorPiso::class, 'brotes_x_piso_id');
    }

    // ----------------------------
    //  Cálculo base: (brotes ÷ longitud de cama) × metros de cama por ha
    // ----------------------------
    private function convertirPorMetro($valor)
    {
        if (!$this->longitud_cama || !$this->cabecera) {
            return 0;
        }

        return ($valor / $this->longitud_cama) * $this->cabecera->metros_cama_ha;
    }

    public function getBrotes2pActualPorMtAttribute()
    {
        return $this->convertirPorMetro($this->brotes_aptos_2p_actual);
    }

    /** 2° piso para eliminar, por ha */
    public function getBrotes2pDespuesPorMtAttribute()
    {
        return $this->convertirPorMetro($this->brotes_aptos_2p_despues_n_dias);
    }

    public function getBrotes3pActualPorMtAttribute()
    {
        return $this->convertirPorMetro($this->brotes_aptos_3p_actual);
    }

    /** 3° piso para eliminar, por ha */
    public function getBrotes3pDespuesPorMtAttribute()
    {
        return $this->convertirPorMetro($this->brotes_aptos_3p_despues_n_dias);
    }

    public function getBrotes4pActualPorMtAttribute()
    {
        return $this->convertirPorMetro($this->brotes_aptos_4p_actual);
    }

    /** 4° piso para eliminar, por ha */
    public function getBrotes4pDespuesPorMtAttribute()
    {
        return $this->convertirPorMetro($this->brotes_aptos_4p_despues_n_dias);
    }

    // ----------------------------
    // Totales del detalle (2° + 3° + 4°; sin 4° piso es igual que antes)
    // ----------------------------
    /** Total de brotes aptos actuales por ha: el que sirve (pencas, proyección) */
    public function getTotalActualPorMtAttribute()
    {
        return $this->brotes_2p_actual_por_mt + $this->brotes_3p_actual_por_mt + $this->brotes_4p_actual_por_mt;
    }

    /** Total para eliminar por ha: solo informativo */
    public function getTotalDespuesPorMtAttribute()
    {
        return $this->brotes_2p_despues_por_mt + $this->brotes_3p_despues_por_mt + $this->brotes_4p_despues_por_mt;
    }
}
