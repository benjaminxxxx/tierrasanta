<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampoCampania extends Model
{
    use HasFactory;
    protected $table = 'campos_campanias';
    protected $fillable = [
        'nombre_campania',
        'campo',
        'area',
        'gasto_fdm',
        'gasto_agua',
        'gasto_planilla',
        'gasto_cuadrilla',
        'fecha_inicio',
        'fecha_fin',
        'usuario_modificador',

        'gasto_planilla_file',
        'gasto_cuadrilla_file',
        'gasto_resumen_bdd_file',

        'costo_fertilizantes',
        'costo_pesticidas',
        'costo_combustibles',
        'costo_fertilizantes_file',
        'costo_pesticidas_file',
        'costo_combustibles_file',

        'variedad_tuna',
        'sistema_cultivo',
        'tipo_cambio',

        'pencas_x_hectarea',
        'pp_dia_cero_fecha_evaluacion',
        'pp_dia_cero_numero_pencas_madre',
        'pp_resiembra_fecha_evaluacion',
        'pp_resiembra_numero_pencas_madre',

        'brotexpiso_fecha_evaluacion',
        'brotexpiso_actual_brotes_2piso',
        'brotexpiso_brotes_2piso_n_dias',
        'brotexpiso_actual_brotes_3piso',
        'brotexpiso_brotes_3piso_n_dias',
        'brotexpiso_actual_total_brotes_2y3piso',
        'brotexpiso_total_brotes_2y3piso_n_dias',

        'infestacion_fecha',
        'infestacion_duracion_desde_campania',
        'infestacion_numero_pencas',
        'infestacion_kg_totales_madre',
        'infestacion_kg_madre_infestador_carton',
        'infestacion_kg_madre_infestador_tubos',
        'infestacion_kg_madre_infestador_mallita',
        'infestacion_procedencia_madres',
        'infestacion_cantidad_madres_por_infestador_carton',
        'infestacion_cantidad_madres_por_infestador_tubos',
        'infestacion_cantidad_madres_por_infestador_mallita',
        'infestacion_cantidad_infestadores_carton',
        'infestacion_cantidad_infestadores_tubos',
        'infestacion_cantidad_infestadores_mallita',
        'infestacion_fecha_recojo_vaciado_infestadores',
        'infestacion_permanencia_infestadores',//dias
        'infestacion_fecha_colocacion_malla',
        'infestacion_fecha_retiro_malla',
        'infestacion_permanencia_malla',//dias

        'reinfestacion_fecha',
        'reinfestacion_duracion_desde_infestacion',
        'reinfestacion_numero_pencas',
        'reinfestacion_kg_totales_madre',
        'reinfestacion_kg_madre_infestador_carton',
        'reinfestacion_kg_madre_infestador_tubos',
        'reinfestacion_kg_madre_infestador_mallita',
        'reinfestacion_procedencia_madres',
        'reinfestacion_cantidad_madres_por_infestador_carton',
        'reinfestacion_cantidad_madres_por_infestador_tubos',
        'reinfestacion_cantidad_madres_por_infestador_mallita',
        'reinfestacion_cantidad_infestadores_carton',
        'reinfestacion_cantidad_infestadores_tubos',
        'reinfestacion_cantidad_infestadores_mallita',
        'reinfestacion_fecha_recojo_vaciado_infestadores',
        'reinfestacion_permanencia_infestadores',//dias
        'reinfestacion_fecha_colocacion_malla',
        'reinfestacion_fecha_retiro_malla',
        'reinfestacion_permanencia_malla',//dias

        'cosechamadres_fecha_cosecha',
        'cosechamadres_tiempo_infestacion_a_cosecha',
        'cosechamadres_destino_madres_fresco',
        'cosechamadres_infestador_carton_campos',
        'cosechamadres_infestador_tubo_campos',
        'cosechamadres_infestador_mallita_campos',
        'cosechamadres_para_secado',
        'cosechamadres_para_venta_fresco',
        'cosechamadres_recuperacion_madres_seco_carton',
        'cosechamadres_recuperacion_madres_seco_tubo',
        'cosechamadres_recuperacion_madres_seco_mallita',
        'cosechamadres_recuperacion_madres_seco_secado',
        'cosechamadres_recuperacion_madres_seco_fresco',
        'cosechamadres_conversion_fresco_seco_carton',
        'cosechamadres_conversion_fresco_seco_tubo',
        'cosechamadres_conversion_fresco_seco_mallita',
        'cosechamadres_conversion_fresco_seco_secado',
        'cosechamadres_conversion_fresco_seco_fresco',

        'eval_cosch_conteo_individuos',
        'eval_cosch_proj_1',
        'eval_cosch_proj_2',
        'eval_cosch_proj_coch_x_gramo',
        'eval_cosch_proj_gramos_x_penca',
        'eval_cosch_proj_penca_inf',
        'eval_cosch_proj_rdto_ha',

        'proj_rdto_poda_muestra',
        'proj_rdto_metros_cama_ha',
        'proj_rdto_prom_rdto_ha',
        'proj_rdto_rel_fs',

        'cosch_fecha',
        'cosch_tiempo_inf_cosch',
        'cosch_tiempo_reinf_cosch',
        'cosch_tiempo_ini_cosch',
        'cosch_destino_carton',
        'cosch_destino_tubo',
        'cosch_destino_malla',
        'cosch_kg_fresca_carton',
        'cosch_kg_fresca_tubo',
        'cosch_kg_fresca_malla',
        'cosch_kg_fresca_losa',
        'cosch_kg_seca_carton',
        'cosch_kg_seca_tubo',
        'cosch_kg_seca_malla',
        'cosch_kg_seca_losa',
        'cosch_kg_seca_venta_madre',
        'cosch_factor_fs_carton',
        'cosch_factor_fs_tubo',
        'cosch_factor_fs_malla',
        'cosch_factor_fs_losa',
        'cosch_total_cosecha',
        'cosch_total_campania',

        'acid_prom',
        'acid_infest',
        'acid_secado',
        'acid_poda_infest',
        'acid_poda_losa',
        'acid_tam',
    ];

    public function fertilizaciones()
    {
        return $this->hasMany(FertilizacionCampania::class, 'campo_campania_id');
    }
    public function infestaciones()
    {
        return $this->hasMany(CochinillaInfestacion::class, 'campo_campania_id');
    }
    public function evaluacionInfestaciones()
    {
        return $this->hasMany(EvaluacionInfestacion::class, 'campo_campania_id');
    }
    public function camposCampaniasConsumo()
    {
        return $this->hasMany(CamposCampaniasConsumo::class, 'campos_campanias_id');
    }
    public function reporteCostoPlanilla()
    {
        return $this->hasMany(ReporteCostoPlanilla::class, 'campos_campanias_id');
    }
    public function poblacionPlantas()
    {
        return $this->hasMany(PoblacionPlantas::class, 'campania_id');
    }
    public function evaluacionBrotesXPiso()
    {
        return $this->hasMany(EvaluacionBrotesXPiso::class, 'campania_id');
    }
    public function proyeccionesRendimientosPoda()
    {
        return $this->hasMany(ProyeccionRendimientoPoda::class, 'campo_campania_id');
    }
    //consumos()
    public function resumenConsumoProductos()
    {
        return $this->hasMany(ResumenConsumoProductos::class, 'campos_campanias_id');
    }
    public function cochinillaIngreso()
    {
        return $this->hasMany(CochinillaIngreso::class, 'campo_campania_id');
    }
    public function campo_model()
    {
        return $this->belongsTo(Campo::class, 'campo', 'nombre');
    }
    public function getTotalHectareaBrotesAttribute()
    {
        $ultimoRegistro = $this->evaluacionBrotesXPiso()
            ->orderByDesc('fecha')
            ->first();

        return $ultimoRegistro?->total_hectarea ?? null;
    }
    public function getPromedioIndividuosMitadDiasAttribute()
    {
        $evaluaciones = $this->evaluacionInfestaciones()->orderByDesc('fecha')->get();

        $total = $evaluaciones->count();

        if ($total === 0) {
            return null;
        }

        $index = (int) floor($total / 2); // para impar y par toma el centro superior

        return $evaluaciones[$index]?->promedio ?? null;
    }

    public function getFechaVigenciaAttribute()
    {
        $date = Carbon::parse($this->fecha_inicio);
        $date->locale('es');

        return $date->translatedFormat('j \d\e F \d\e\l\ Y');
    }
    //atributos para el detalle final de la campaña
    public function getFechaSiembraAttribute()
    {
        $date = Carbon::parse($this->fecha_inicio);

        return Siembra::where('fecha_siembra', '<=', $date)
            ->where('campo_nombre', $this->campo)
            ->latest('fecha_siembra') // Obtiene la siembra más reciente antes de fecha_inicio
            ->value('fecha_siembra') ?? null; // Devuelve solo la fecha o una cadena vacía si no hay resultados
    }
    public static function masProximaAntesDe($fecha, $campo)
    {
        return self::where('fecha_inicio', '<=', $fecha)
            ->where('campo', $campo)
            ->orderByDesc('fecha_inicio')
            ->first();
    }
    public function cochinillaMadres()
    {
        return $this->cochinillaIngreso()
            ->with('detalles.observacionRelacionada')
            ->whereHas('observacionRelacionada', fn($q) => $q->where('es_cosecha_mama', true))
            ->orWhereHas(
                'detalles',
                fn($q) =>
                $q->whereHas(
                    'observacionRelacionada',
                    fn($q2) =>
                    $q2->where('es_cosecha_mama', true)
                )
            );

    }
    #region Alias
    // Aliases para infestación
    public function getInfestacionCantidadMadresPorInfestadorCartonAliasAttribute()
    {
        return $this->formatearMadresPorInfestador($this->infestacion_cantidad_madres_por_infestador_carton);
    }

    public function getInfestacionCantidadMadresPorInfestadorTubosAliasAttribute()
    {
        return $this->formatearMadresPorInfestador($this->infestacion_cantidad_madres_por_infestador_tubos);
    }

    public function getInfestacionCantidadMadresPorInfestadorMallitaAliasAttribute()
    {
        return $this->formatearMadresPorInfestador($this->infestacion_cantidad_madres_por_infestador_mallita);
    }

    // Aliases para reinfestación
    public function getReinfestacionCantidadMadresPorInfestadorCartonAliasAttribute()
    {
        return $this->formatearMadresPorInfestador($this->reinfestacion_cantidad_madres_por_infestador_carton);
    }

    public function getReinfestacionCantidadMadresPorInfestadorTubosAliasAttribute()
    {
        return $this->formatearMadresPorInfestador($this->reinfestacion_cantidad_madres_por_infestador_tubos);
    }

    public function getReinfestacionCantidadMadresPorInfestadorMallitaAliasAttribute()
    {
        return $this->formatearMadresPorInfestador($this->reinfestacion_cantidad_madres_por_infestador_mallita);
    }

    // Método reutilizable para el formateo seguro
    protected function formatearMadresPorInfestador($valor)
    {
        if (!is_numeric($valor)) {
            return '0gr.';
        }

        return number_format($valor * 10000, 0) . 'gr.';
    }

    #endregion
    #region CosechaMadresCalculado
    public function getCosechamadresDestinoMadresFrescoAttribute()
    {
        return
            ($this->cosechamadres_infestador_carton_campos ?? 0) +
            ($this->cosechamadres_infestador_tubo_campos ?? 0) +
            ($this->cosechamadres_infestador_mallita_campos ?? 0) +
            ($this->cosechamadres_para_secado ?? 0) +
            ($this->cosechamadres_para_venta_fresco ?? 0);
    }

    public function getCosechamadresRecuperacionMadresAttribute()
    {
        return
            ($this->cosechamadres_recuperacion_madres_seco_carton ?? 0) +
            ($this->cosechamadres_recuperacion_madres_seco_tubo ?? 0) +
            ($this->cosechamadres_recuperacion_madres_seco_mallita ?? 0) +
            ($this->cosechamadres_recuperacion_madres_seco_secado ?? 0) +
            ($this->cosechamadres_recuperacion_madres_seco_fresco ?? 0);
    }

    public function getCosechamadresConversionFrescoSecoAttribute()
    {
        return
            ($this->cosechamadres_conversion_fresco_seco_carton ?? 0) +
            ($this->cosechamadres_conversion_fresco_seco_tubo ?? 0) +
            ($this->cosechamadres_conversion_fresco_seco_mallita ?? 0) +
            ($this->cosechamadres_conversion_fresco_seco_secado ?? 0) +
            ($this->cosechamadres_conversion_fresco_seco_fresco ?? 0);
    }

    public function getCosechamadresInfestadorCartonCamposHaAttribute()
    {
        $area = optional($this->campo_model)->area;
        return $area > 0 ? $this->cosechamadres_infestador_carton_campos / $area : null;
    }
    public function getCosechamadresInfestadorTuboCamposHaAttribute()
    {
        $area = optional($this->campo_model)->area;
        return $area > 0 ? $this->cosechamadres_infestador_tubo_campos / $area : null;
    }

    public function getCosechamadresInfestadorMallitaCamposHaAttribute()
    {
        $area = optional($this->campo_model)->area;
        return $area > 0 ? $this->cosechamadres_infestador_mallita_campos / $area : null;
    }

    public function getCosechamadresParaSecadoHaAttribute()
    {
        $area = optional($this->campo_model)->area;
        return $area > 0 ? $this->cosechamadres_para_secado / $area : null;
    }

    public function getCosechamadresParaVentaFrescoHaAttribute()
    {
        $area = optional($this->campo_model)->area;
        return $area > 0 ? $this->cosechamadres_para_venta_fresco / $area : null;
    }
    public function getCosechamadresRecuperacionMadresSecoCartonHaAttribute()
    {
        $area = optional($this->campo_model)->area;
        return $area > 0 ? $this->cosechamadres_recuperacion_madres_seco_carton / $area : null;
    }

    public function getCosechamadresRecuperacionMadresSecoTuboHaAttribute()
    {
        $area = optional($this->campo_model)->area;
        return $area > 0 ? $this->cosechamadres_recuperacion_madres_seco_tubo / $area : null;
    }

    public function getCosechamadresRecuperacionMadresSecoMallitaHaAttribute()
    {
        $area = optional($this->campo_model)->area;
        return $area > 0 ? $this->cosechamadres_recuperacion_madres_seco_mallita / $area : null;
    }

    public function getCosechamadresRecuperacionMadresSecoSecadoHaAttribute()
    {
        $area = optional($this->campo_model)->area;
        return $area > 0 ? $this->cosechamadres_recuperacion_madres_seco_secado / $area : null;
    }

    public function getCosechamadresRecuperacionMadresSecoFrescoHaAttribute()
    {
        $area = optional($this->campo_model)->area;
        return $area > 0 ? $this->cosechamadres_recuperacion_madres_seco_fresco / $area : null;
    }

    #endregion
    #region Nutrientes x Ha
    public function getNutrienteNitrogenoKgXHaAttribute()
    {
        return $this->fertilizaciones->sum('n_ha');
    }

    public function getNutrienteFosforoKgXHaAttribute()
    {
        return $this->fertilizaciones->sum('p_ha');
    }

    public function getNutrientePotasioKgXHaAttribute()
    {
        return $this->fertilizaciones->sum('k_ha');
    }

    public function getNutrienteCalcioKgXHaAttribute()
    {
        return $this->fertilizaciones->sum('ca_ha');
    }

    public function getNutrienteMagnesioKgXHaAttribute()
    {
        return $this->fertilizaciones->sum('mg_ha');
    }

    public function getNutrienteZincKgXHaAttribute()
    {
        return $this->fertilizaciones->sum('zn_ha');
    }

    public function getNutrienteManganesoKgXHaAttribute()
    {
        return $this->fertilizaciones->sum('mn_ha');
    }

    public function getNutrienteFierroKgXHaAttribute()
    {
        return $this->fertilizaciones->sum('fe_ha');
    }
    #endregion

}
