<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CostoMensual extends Model
{
    use HasFactory;

    protected $table = 'costos_mensuales';

    protected $fillable = [
        'anio',
        'mes',

        // Costos fijos
        'fijo_administrativo_blanco',
        'fijo_administrativo_negro',
        'fijo_financiero_blanco',
        'fijo_financiero_negro',
        'fijo_gastos_oficina_blanco',
        'fijo_gastos_oficina_negro',
        'fijo_depreciaciones_blanco',
        'fijo_depreciaciones_negro',
        'fijo_costo_terreno_blanco',
        'fijo_costo_terreno_negro',

        // Costos operativos
        'operativo_servicios_fundo_blanco',
        'operativo_servicios_fundo_negro',
        'operativo_mano_obra_indirecta_blanco',
        'operativo_mano_obra_indirecta_negro',
    ];
    /* =======================
     |  ATTRIBUTES TOTALES
     ======================= */

    public function getFijoAdministrativoAttribute(): float
    {
        return (float) $this->fijo_administrativo_blanco
            + (float) $this->fijo_administrativo_negro;
    }

    public function getFijoFinancieroAttribute(): float
    {
        return (float) $this->fijo_financiero_blanco
            + (float) $this->fijo_financiero_negro;
    }

    public function getFijoGastosOficinaAttribute(): float
    {
        return (float) $this->fijo_gastos_oficina_blanco
            + (float) $this->fijo_gastos_oficina_negro;
    }

    public function getFijoDepreciacionesAttribute(): float
    {
        return (float) $this->fijo_depreciaciones_blanco
            + (float) $this->fijo_depreciaciones_negro;
    }

    public function getFijoCostoTerrenoAttribute(): float
    {
        return (float) $this->fijo_costo_terreno_blanco
            + (float) $this->fijo_costo_terreno_negro;
    }

    public function getOperativoServiciosFundoAttribute(): float
    {
        return (float) $this->operativo_servicios_fundo_blanco
            + (float) $this->operativo_servicios_fundo_negro;
    }

    public function getOperativoManoObraIndirectaAttribute(): float
    {
        return (float) $this->operativo_mano_obra_indirecta_blanco
            + (float) $this->operativo_mano_obra_indirecta_negro;
    }
}
