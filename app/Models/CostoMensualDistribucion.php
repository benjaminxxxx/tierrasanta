<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostoMensualDistribucion extends Model
{
    protected $table = 'costo_mensual_distribuciones';

    protected $fillable = [
        'costo_mensual_id',
        'campo_campania_id',
        'anio',
        'mes',
        'dias_mes',
        'dias_activos',
        'porcentaje',

        'fijo_administrativo',
        'fijo_financiero',
        'fijo_gastos_oficina',
        'fijo_depreciaciones',
        'fijo_costo_terreno',

        'operativo_servicios_fundo',
        'operativo_mano_obra_indirecta',
    ];

    /* =======================
     * Relaciones
     * ======================= */

    public function costoMensual()
    {
        return $this->belongsTo(CostoMensual::class);
    }

    public function campania()
    {
        return $this->belongsTo(CampoCampania::class, 'campo_campania_id');
    }
}
