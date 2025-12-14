<?php

namespace App\Models;

use App\Support\CalculoHelper;
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
        'infestacion_permanencia_infestadores', // dias
        'infestacion_fecha_colocacion_malla',
        'infestacion_fecha_retiro_malla',
        'infestacion_permanencia_malla', // dias

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
        'reinfestacion_permanencia_infestadores', // dias
        'reinfestacion_fecha_colocacion_malla',
        'reinfestacion_fecha_retiro_malla',
        'reinfestacion_permanencia_malla', // dias

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

        'riego_inicio',
        'riego_fin',
        'riego_descarga_ha_hora',
        'riego_hrs_ini_infest',
        'riego_m3_ini_infest',
        'riego_hrs_infest_reinf',
        'riego_m3_infest_reinf',
        'riego_hrs_reinf_cosecha',
        'riego_m3_reinf_cosecha',
        'riego_hrs_acumuladas',
        'riego_m3_acum_ha',
    ];

    public function fertilizaciones()
    {
        return $this->hasMany(InsResFertilizanteCampania::class, 'campo_campania_id');
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

    public function evaluacionPoblacionPlantas()
    {
        return $this->hasOne(EvalPoblacionPlanta::class, 'campania_id');
    }

    public function evaluacionBrotesXPiso()
    {
        return $this->hasOne(EvalBrotesPorPiso::class, 'campania_id');
    }

    public function proyeccionesRendimientosPoda()
    {
        return $this->hasMany(ProyeccionRendimientoPoda::class, 'campo_campania_id');
    }

    // consumos()
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

    public function getNumeroInfestadoresAttribute()
    {
        return $this->infestacion_cantidad_infestadores_carton +
            $this->infestacion_cantidad_infestadores_tubos +
            $this->infestacion_cantidad_infestadores_mallita;
    }

    public function getNumeroReinfestadoresAttribute()
    {
        return $this->reinfestacion_cantidad_infestadores_carton +
            $this->reinfestacion_cantidad_infestadores_tubos +
            $this->reinfestacion_cantidad_infestadores_mallita;
    }

    public function getTipoInfestadorAttribute()
    {
        $tipoInfestador = '-';
        if ($this->infestacion_cantidad_madres_por_infestador_carton > 0) {
            $tipoInfestador = 'Cartón ';
        }
        if ($this->infestacion_cantidad_madres_por_infestador_tubos > 0) {
            $tipoInfestador = 'Tubos ';
        }
        if ($this->infestacion_cantidad_madres_por_infestador_mallita > 0) {
            $tipoInfestador = 'Mallita ';
        }

        return trim($tipoInfestador);
    }

    public function getTipoReinfestadorAttribute()
    {
        $tipoInfestador = '-';
        if ($this->reinfestacion_cantidad_madres_por_infestador_carton > 0) {
            $tipoInfestador = 'Cartón ';
        }
        if ($this->reinfestacion_cantidad_madres_por_infestador_tubos > 0) {
            $tipoInfestador = 'Tubos ';
        }
        if ($this->reinfestacion_cantidad_madres_por_infestador_mallita > 0) {
            $tipoInfestador = 'Mallita ';
        }

        return trim($tipoInfestador);
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

    // atributos para el detalle final de la campaña
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
                fn($q) => $q->whereHas(
                    'observacionRelacionada',
                    fn($q2) => $q2->where('es_cosecha_mama', true)
                )
            );

    }

    // region Infestacion
    public function getRiegoM3IniInfestPorPencaAttribute()
    {
        if ($this->infestacion_numero_pencas == 0 || $this->infestacion_numero_pencas === null) {
            return null; // o 0 según lo que debas mostrar
        }

        return $this->riego_m3_ini_infest / $this->infestacion_numero_pencas * 1000;
    }

    public function getRiegoM3InfestReinfestPorPencaAttribute()
    {
        if ($this->reinfestacion_numero_pencas == 0 || $this->reinfestacion_numero_pencas === null) {
            return null; // o 0 según lo que debas mostrar
        }

        return $this->riego_m3_infest_reinf / $this->reinfestacion_numero_pencas * 1000;
    }

    public function getRiegoM3InicioAReinfestacionPorPencaAttribute()
    {
        $totalPencas =
            ($this->infestacion_numero_pencas ?? 0) +
            ($this->reinfestacion_numero_pencas ?? 0);

        if ($totalPencas <= 0 || $this->riego_hrs_acumuladas <= 0) {
            return 0;
        }

        return ($this->riego_hrs_acumuladas / $totalPencas) * 1000;
    }

    // endregion
    // region Alias
    // Aliases para infestación
    public function getNumeroInfestadoresPorPencaAttribute()
    {
        if ($this->infestacion_numero_pencas == 0 || $this->infestacion_numero_pencas === null) {
            return null; // o 0 según lo que debas mostrar
        }

        return $this->numero_infestadores / $this->infestacion_numero_pencas;
    }

    public function getNumeroReinfestadoresPorPencaAttribute()
    {
        if ($this->reinfestacion_numero_pencas == 0 || $this->reinfestacion_numero_pencas === null) {
            return null; // o 0 según lo que debas mostrar
        }

        return $this->numero_reinfestadores / $this->reinfestacion_numero_pencas;
    }

    public function getGramosCochinillaMamaPorInfestadorAttribute()
    {
        if (empty($this->numero_infestadores) || $this->numero_infestadores == 0) {
            return null; // o 0 si prefieres
        }

        return ($this->infestacion_kg_totales_madre / $this->numero_infestadores) * 1000;
    }

    public function getGramosCochinillaMamaPorReinfestadorAttribute()
    {
        if (empty($this->numero_reinfestadores) || $this->numero_reinfestadores == 0) {
            return null; // o 0 si prefieres
        }

        return ($this->reinfestacion_kg_totales_madre / $this->numero_reinfestadores) * 1000;
    }

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

    // endregion
    // region CosechaMadresCalculado
    public function getProcedenciasMadresAttribute()
    {
        $valor = $this->infestacion_procedencia_madres;

        if (is_string($valor)) {
            try {
                return json_decode($valor, true) ?: [];
            } catch (\Exception $e) {
                return [];
            }
        }

        if (is_array($valor)) {
            return $valor;
        }

        return [];
    }

    public function getProcedenciasMadresReinfestacionAttribute()
    {
        $valor = $this->reinfestacion_procedencia_madres;

        if (is_string($valor)) {
            try {
                return json_decode($valor, true) ?: [];
            } catch (\Exception $e) {
                return [];
            }
        }

        if (is_array($valor)) {
            return $valor;
        }

        return [];
    }

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

    // endregion
    // region Nutrientes x Ha
    // KG totales
    public function getNitrogenoDesdeInicioInfestacionAttribute()
    {
        return $this->fertilizaciones->where('etapa', 'infestacion')->sum('n_kg');
    }

    public function getFosforoDesdeInicioInfestacionAttribute()
    {
        return $this->fertilizaciones->where('etapa', 'infestacion')->sum('p_kg');
    }

    public function getPotasioDesdeInicioInfestacionAttribute()
    {
        return $this->fertilizaciones->where('etapa', 'infestacion')->sum('k_kg');
    }

    public function getCalcioDesdeInicioInfestacionAttribute()
    {
        return $this->fertilizaciones->where('etapa', 'infestacion')->sum('ca_kg');
    }

    public function getMagnesioDesdeInicioInfestacionAttribute()
    {
        return $this->fertilizaciones->where('etapa', 'infestacion')->sum('mg_kg');
    }

    public function getZincDesdeInicioInfestacionAttribute()
    {
        return $this->fertilizaciones->where('etapa', 'infestacion')->sum('zn_kg');
    }

    public function getManganesoDesdeInicioInfestacionAttribute()
    {
        return $this->fertilizaciones->where('etapa', 'infestacion')->sum('mn_kg');
    }

    public function getFierroDesdeInicioInfestacionAttribute()
    {
        return $this->fertilizaciones->where('etapa', 'infestacion')->sum('fe_kg');
    }

    public function getCorrectorSalinidadDesdeInicioInfestacionAttribute()
    {
        return $this->fertilizaciones->where('etapa', 'infestacion')->sum('corrector_salinidad_cant');
    }

    public function getNitrogenoDesdeInfestacionReinfestacionAttribute()
    {
        return $this->fertilizaciones->where('etapa', 'reinfestacion')->sum('n_kg');
    }

    public function getFosforoDesdeInfestacionReinfestacionAttribute()
    {
        return $this->fertilizaciones->where('etapa', 'reinfestacion')->sum('p_kg');
    }

    public function getPotasioDesdeInfestacionReinfestacionAttribute()
    {
        return $this->fertilizaciones->where('etapa', 'reinfestacion')->sum('k_kg');
    }

    public function getCalcioDesdeInfestacionReinfestacionAttribute()
    {
        return $this->fertilizaciones->where('etapa', 'reinfestacion')->sum('ca_kg');
    }

    public function getMagnesioDesdeInfestacionReinfestacionAttribute()
    {
        return $this->fertilizaciones->where('etapa', 'reinfestacion')->sum('mg_kg');
    }

    public function getZincDesdeInfestacionReinfestacionAttribute()
    {
        return $this->fertilizaciones->where('etapa', 'reinfestacion')->sum('zn_kg');
    }

    public function getManganesoDesdeInfestacionReinfestacionAttribute()
    {
        return $this->fertilizaciones->where('etapa', 'reinfestacion')->sum('mn_kg');
    }

    public function getFierroDesdeInfestacionReinfestacionAttribute()
    {
        return $this->fertilizaciones->where('etapa', 'reinfestacion')->sum('fe_kg');
    }

    public function getCorrectorSalinidadDesdeInfestacionReinfestacionAttribute()
    {
        return $this->fertilizaciones->where('etapa', 'reinfestacion')->sum('corrector_salinidad_cant');
    }

    public function getNutrienteNitrogenoKgAttribute()
    {
        return $this->fertilizaciones->sum('n_kg'); // Aquí n_kg guarda el kg real
    }

    public function getNutrienteFosforoKgAttribute()
    {
        return $this->fertilizaciones->sum('p_kg');
    }

    public function getNutrientePotasioKgAttribute()
    {
        return $this->fertilizaciones->sum('k_kg');
    }

    public function getNutrienteCalcioKgAttribute()
    {
        return $this->fertilizaciones->sum('ca_kg');
    }

    public function getNutrienteMagnesioKgAttribute()
    {
        return $this->fertilizaciones->sum('mg_kg');
    }

    public function getNutrienteZincKgAttribute()
    {
        return $this->fertilizaciones->sum('zn_kg');
    }

    public function getNutrienteManganesoKgAttribute()
    {
        return $this->fertilizaciones->sum('mn_kg');
    }

    public function getNutrienteFierroKgAttribute()
    {
        return $this->fertilizaciones->sum('fe_kg');
    }

    public function getCorrectorSalinidadCantAttribute()
    {
        return $this->fertilizaciones->sum('corrector_salinidad_cant');
    }

    // KG por hectárea (calculado)
    public function getNutrienteNitrogenoKgHaAttribute()
    {
        return $this->area > 0 ? $this->nutriente_nitrogeno_kg / $this->area : 0;
    }

    public function getNutrienteFosforoKgHaAttribute()
    {
        return $this->area > 0 ? $this->nutriente_fosforo_kg / $this->area : 0;
    }

    public function getNutrientePotasioKgHaAttribute()
    {
        return $this->area > 0 ? $this->nutriente_potasio_kg / $this->area : 0;
    }

    public function getNutrienteCalcioKgHaAttribute()
    {
        return $this->area > 0 ? $this->nutriente_calcio_kg / $this->area : 0;
    }

    public function getNutrienteMagnesioKgHaAttribute()
    {
        return $this->area > 0 ? $this->nutriente_magnesio_kg / $this->area : 0;
    }

    public function getNutrienteZincKgHaAttribute()
    {
        return $this->area > 0 ? $this->nutriente_zinc_kg / $this->area : 0;
    }

    public function getNutrienteManganesoKgHaAttribute()
    {
        return $this->area > 0 ? $this->nutriente_manganeso_kg / $this->area : 0;
    }

    public function getNutrienteFierroKgHaAttribute()
    {
        return $this->area > 0 ? $this->nutriente_fierro_kg / $this->area : 0;
    }

    // endregion
    protected static function booted()
    {
        static::saving(function ($campania) {

            /*
            |--------------------------------------------------------------------------
            | DURACIONES RELACIONADAS A INFESTACIÓN / REINFESTACIÓN
            |--------------------------------------------------------------------------
            */
            if (
                $campania->isDirty('fecha_inicio') ||
                $campania->isDirty('infestacion_fecha') ||
                $campania->isDirty('reinfestacion_fecha')
            ) {
                $fechaInicio = $campania->fecha_inicio;
                $fechaInfestacion = $campania->infestacion_fecha;
                $fechaReinfestacion = $campania->reinfestacion_fecha;

                // Inicio → Infestación
                $campania->infestacion_duracion_desde_campania =
                    ($fechaInicio && $fechaInfestacion)
                    ? CalculoHelper::calcularDuracionEntreFechas($fechaInicio, $fechaInfestacion)
                    : null;

                // Infestación → Reinfestación
                $campania->reinfestacion_duracion_desde_infestacion =
                    ($fechaInfestacion && $fechaReinfestacion)
                    ? CalculoHelper::calcularDuracionEntreFechas($fechaInfestacion, $fechaReinfestacion)
                    : null;
            }

            /*
            |--------------------------------------------------------------------------
            | DURACIONES RELACIONADAS A COSECHA
            |--------------------------------------------------------------------------
            */
            if (
                $campania->isDirty('cosch_fecha') ||
                $campania->isDirty('fecha_inicio') ||
                $campania->isDirty('infestacion_fecha') ||
                $campania->isDirty('reinfestacion_fecha')
            ) {
                $fechaCosecha = $campania->cosch_fecha;
                $fechaInicio = $campania->fecha_inicio;
                $fechaInfestacion = $campania->infestacion_fecha;
                $fechaReinfestacion = $campania->reinfestacion_fecha;

                // Infestación → Cosecha
                $campania->cosch_tiempo_inf_cosch =
                    ($fechaInfestacion && $fechaCosecha)
                    ? CalculoHelper::calcularDuracionEntreFechas($fechaInfestacion, $fechaCosecha)
                    : null;

                // Reinfestación → Cosecha
                $campania->cosch_tiempo_reinf_cosch =
                    ($fechaReinfestacion && $fechaCosecha)
                    ? CalculoHelper::calcularDuracionEntreFechas($fechaReinfestacion, $fechaCosecha)
                    : null;

                // Inicio → Cosecha
                $campania->cosch_tiempo_ini_cosch =
                    ($fechaInicio && $fechaCosecha)
                    ? CalculoHelper::calcularDuracionEntreFechas($fechaInicio, $fechaCosecha)
                    : null;
            }

            /*
        |--------------------------------------------------------------------------
        | FACTORES FRESCA / SECA
        |--------------------------------------------------------------------------
        */
            foreach (['carton', 'tubo', 'malla', 'losa'] as $tipo) {
                $fresca = $campania->{"cosch_kg_fresca_$tipo"};
                $seca = $campania->{"cosch_kg_seca_$tipo"};

                $campania->{"cosch_factor_fs_$tipo"} =
                    ($seca ?? 0) > 0 ? round($fresca / $seca, 2) : null;
            }

            /*
            |--------------------------------------------------------------------------
            | TOTALES
            |--------------------------------------------------------------------------
            */
            $totalKgSeco =
                ($campania->cosch_kg_seca_carton ?? 0) +
                ($campania->cosch_kg_seca_tubo ?? 0) +
                ($campania->cosch_kg_seca_malla ?? 0) +
                ($campania->cosch_kg_seca_losa ?? 0) +
                ($campania->cosch_kg_seca_venta_madre ?? 0);

            $campania->cosch_total_cosecha =
                ($campania->area ?? 0) > 0
                ? round($totalKgSeco / $campania->area, 2)
                : null;

            $campania->cosch_total_campania =
                $campania->cosechamadres_recuperacion_madres;
        });
    }
}
