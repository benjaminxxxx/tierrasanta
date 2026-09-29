<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResumenCostoDiario extends Model
{
    use HasFactory;

    protected $table = 'resumen_costo_diarios';

    /**
     * Catálogo de origen_tipo que generan los procesos de consolidación => etiqueta visible.
     * Al agregar un nuevo proceso de consolidación, registrar aquí su tipo.
     */
    public const TIPOS_ORIGEN = [
        'planilla' => 'Planilla',
        'planilla_bono_productividad' => 'Planilla bono productividad',
        // Pagos de planilla sin trabajo en campo: feriado, descanso médico, licencias con goce,
        // vacaciones pagadas, bono de asistencia… (campo vacío; cuadran lo pagado con lo de campo)
        'mano_obra_indirecta' => 'Mano de obra indirecta',
        'cuadrilla' => 'Cuadrilla',                 // jornal por detalle de horas + bonos que se pagan con el jornal
        'cuadrilla_bono' => 'Cuadrilla bono',       // bonos que se pagan aparte (se acumulan)
        'riego' => 'Riego',
        'maquinaria' => 'Maquinaria',
        'fertilizante' => 'Fertilizante',
        'pesticida' => 'Pesticida',
        'servicio_campo' => 'Servicio Campo',
        'costo_fijo' => 'Costo fijo',
        'costo_operativo' => 'Costo operativo',
    ];

    /**
     * Tipos del catálogo más cualquier otro que ya exista en la tabla,
     * para que el filtro nunca se quede corto.
     */
    public static function tiposOrigenDisponibles(): array
    {
        $tipos = self::TIPOS_ORIGEN;
        foreach (self::query()->distinct()->pluck('origen_tipo') as $tipo) {
            $tipos[$tipo] ??= ucfirst(str_replace('_', ' ', $tipo));
        }
        return $tipos;
    }

    public static function etiquetaOrigen(?string $tipo): string
    {
        return self::TIPOS_ORIGEN[$tipo] ?? ucfirst(str_replace('_', ' ', (string) $tipo));
    }

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
        'minutos',
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
        'minutos' => 'integer',
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
    public function getHorasAttribute()
    {
        return $this->minutos / 60;
    }
}