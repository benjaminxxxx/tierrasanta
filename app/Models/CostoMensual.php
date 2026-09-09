<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

        // Costos base/declarados
        'costo_planilla',
        'costo_cuadrilla',
        'costo_maquinaria',
        'costo_pesticida',
        'costo_fertilizante',
        'costo_gastos_generales',

        // Costos calculados (consolidados por campo)
        'costo_planilla_calculado',
        'costo_cuadrilla_calculado',
        'costo_maquinaria_calculado',
        'costo_pesticida_calculado',
        'costo_fertilizante_calculado',
        'costo_gastos_generales_calculado',

        // Archivos, auditoría y control
        'reporte_file',
        'estado',
        'calculado_en',
        'calculado_por',
        'observaciones',
    ];

    protected $casts = [
        'anio' => 'integer',
        'mes' => 'integer',
        'calculado_en' => 'datetime',
        'fijo_administrativo_blanco' => 'decimal:2',
        'fijo_administrativo_negro' => 'decimal:2',
        'fijo_financiero_blanco' => 'decimal:2',
        'fijo_financiero_negro' => 'decimal:2',
        'fijo_gastos_oficina_blanco' => 'decimal:2',
        'fijo_gastos_oficina_negro' => 'decimal:2',
        'fijo_depreciaciones_blanco' => 'decimal:2',
        'fijo_depreciaciones_negro' => 'decimal:2',
        'fijo_costo_terreno_blanco' => 'decimal:2',
        'fijo_costo_terreno_negro' => 'decimal:2',
        'operativo_servicios_fundo_blanco' => 'decimal:2',
        'operativo_servicios_fundo_negro' => 'decimal:2',
        'operativo_mano_obra_indirecta_blanco' => 'decimal:2',
        'operativo_mano_obra_indirecta_negro' => 'decimal:2',
        'costo_planilla' => 'decimal:2',
        'costo_cuadrilla' => 'decimal:2',
        'costo_maquinaria' => 'decimal:2',
        'costo_pesticida' => 'decimal:2',
        'costo_fertilizante' => 'decimal:2',
        'costo_gastos_generales' => 'decimal:2',
        'costo_planilla_calculado' => 'decimal:2',
        'costo_cuadrilla_calculado' => 'decimal:2',
        'costo_maquinaria_calculado' => 'decimal:2',
        'costo_pesticida_calculado' => 'decimal:2',
        'costo_fertilizante_calculado' => 'decimal:2',
        'costo_gastos_generales_calculado' => 'decimal:2',
    ];

    /* =======================
     |  RELACIONES
     ======================= */

    public function usuarioCalculadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculado_por');
    }

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