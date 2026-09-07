<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResumenCostoDiario extends Model
{
    use HasFactory;

    protected $table = 'resumen_costo_diarios';

    protected $fillable = [
        'campania',
        'fecha',
        'origen_tipo',
        'origen_id',
        'campo',
        'labor',
        'trabajador',
        'cuadrilla_grupo_id',
        'tipo_cambio',
        'horas',
        'cantidad_jornales',
        'insumo_nombre',
        'orden_compra',
        'factura',
        'tienda_comercial',
        'cantidad_insumo',
        'costo_total',
        'labor_nombre',
        'observacion',
    ];

    protected $casts = [
        'fecha' => 'date',
        'origen_id' => 'integer',
        'labor' => 'integer',
        'tipo_cambio' => 'decimal:4',
        'horas' => 'decimal:2',
        'cantidad_jornales' => 'decimal:4',
        'cantidad_insumo' => 'decimal:2',
        'costo_total' => 'decimal:14',
    ];

    /* =====================================================================
     * SCOPES PARA BÚSQUEDAS Y LIMPIEZAS MASIVAS
     * ===================================================================== */

    public function scopePorCampania($query, string $campania)
    {
        return $query->where('campania', $campania);
    }

    public function scopePorCampo($query, string $campo)
    {
        return $query->where('campo', $campo);
    }

    public function scopePorOrigen($query, string $origenTipo)
    {
        return $query->where('origen_tipo', $origenTipo);
    }

    public function scopePorRangoFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
    }

    public function scopePorGrupoCuadrilla($query, string $grupoId)
    {
        return $query->where('cuadrilla_grupo_id', $grupoId);
    }
}