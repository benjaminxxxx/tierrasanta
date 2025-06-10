<?php
namespace App\Services;
use App\Models\CampoCampania;
use App\Models\ReporteDiarioRiego;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class RiegoServicio
{
    public static function obtenerRiegosPorCampaniaId($campaniaId, $porPagina = 20)
    {
        return ReporteDiarioRiego::where('campo_campania_id', $campaniaId)
            ->where('tipo_labor', 'Riego')
            ->orderBy('fecha', 'asc')
            ->paginate($porPagina);
    }
    public static function procesarRiegosParaCampania($campania)
    {
        $fechaInicio = $campania->fecha_inicio;
        $fechaFin = $campania->fecha_fin;
        $campo = $campania->campo;

        $criteriosQuery = ReporteDiarioRiego::where('campo', $campo)
            ->where('tipo_labor', 'Riego');

        if ($fechaFin) {
            $criteriosQuery->whereBetween('fecha', [$fechaInicio, $fechaFin]);
        } else {
            $criteriosQuery->whereDate('fecha', '>=', $fechaInicio);
        }

        $criteriosValidos = $criteriosQuery->pluck('id');


        // 1. Desasignar riegos que estaban asociados a esta campaña pero ya no cumplen los criterios
        ReporteDiarioRiego::where('campo_campania_id', $campania->id)
            ->whereNotIn('id', $criteriosValidos)
            ->update(['campo_campania_id' => null]);

        // 2. Asignar los riegos válidos (que aún no tienen campaña asignada o tengan otra)
        ReporteDiarioRiego::whereIn('id', $criteriosValidos)
            ->update(['campo_campania_id' => $campania->id]);

        // Consolidar riegos luego de actualizar
        self::consolidarRiegosPorCampania($campania->id);
    }

    public static function consolidarRiegosPorCampania($campaniaId)
    {
        $campania = CampoCampania::find($campaniaId);
        if (!$campania) {
            Log::error("Campaña no encontrada: {$campaniaId}");
            return;
        }

        $riegos = ReporteDiarioRiego::where('campo_campania_id', $campaniaId)
            ->where('tipo_labor', 'Riego')
            ->orderBy('fecha')
            ->get();

        if ($riegos->isEmpty()) {
            Log::info("No hay registros de riego para campaña: {$campaniaId}");
            return;
        }

        $resultados = self::calcularResumenRiego($campania, $riegos);

        $campania->update($resultados);
    }
    protected static function calcularResumenRiego($campania, $riegos)
    {
        $inicio = $campania->fecha_inicio;
        $infestacion = $campania->infestacion_fecha;
        $reinfestacion = $campania->reinfestacion_fecha;
        $cosecha = $campania->cosch_fecha;
        $descargaPorHora = $campania->riego_descarga_ha_hora;

        $fechaInicioRiego = $riegos->first()->fecha;
        $fechaFinRiego = $riegos->last()->fecha;

        $entre = fn($desde, $hasta) => self::sumarHorasEntreFechas($riegos, $desde, $hasta);
        $multiplicar = fn($horas) => $horas !== null && $descargaPorHora ? $horas * $descargaPorHora : null;

        $riegoHrsIniInfest = ($inicio && $infestacion) ? $entre($inicio, $infestacion) : null;
        $riegoM3IniInfest = $multiplicar($riegoHrsIniInfest);

        $riegoHrsInfestReinf = ($infestacion && $reinfestacion) ? $entre($infestacion, $reinfestacion) : null;
        $riegoM3InfestReinf = $multiplicar($riegoHrsInfestReinf);

        if ($reinfestacion && $cosecha) {
            $riegoHrsReinfCosecha = $entre($reinfestacion, $cosecha);
        } elseif ($infestacion && $cosecha) {
            $riegoHrsReinfCosecha = $entre($infestacion, $cosecha);
        } else {
            $riegoHrsReinfCosecha = null;
        }

        $riegoM3ReinfCosecha = $multiplicar($riegoHrsReinfCosecha);

        $riegoHrsAcumuladas = self::sumarHorasTotales($riegos);
        $riegoM3AcumHa = $multiplicar($riegoHrsAcumuladas);

        return [
            'riego_inicio' => $fechaInicioRiego,
            'riego_fin' => $fechaFinRiego,
            'riego_hrs_ini_infest' => $riegoHrsIniInfest,
            'riego_m3_ini_infest' => $riegoM3IniInfest,
            'riego_hrs_infest_reinf' => $riegoHrsInfestReinf,
            'riego_m3_infest_reinf' => $riegoM3InfestReinf,
            'riego_hrs_reinf_cosecha' => $riegoHrsReinfCosecha,
            'riego_m3_reinf_cosecha' => $riegoM3ReinfCosecha,
            'riego_hrs_acumuladas' => $riegoHrsAcumuladas,
            'riego_m3_acum_ha' => $riegoM3AcumHa,
        ];
    }
    protected static function sumarHorasEntreFechas($riegos, $desde, $hasta)
    {
        return $riegos
            ->whereBetween('fecha', [$desde, $hasta])
            ->sum(function ($riego) {
                if (!$riego->total_horas)
                    return 0;

                [$h, $m, $s] = explode(':', $riego->total_horas);
                return (int) $h + ((int) $m / 60) + ((int) $s / 3600);
            });
    }
    protected static function sumarHorasTotales($riegos)
    {
        return $riegos->sum(function ($riego) {
            return CarbonInterval::createFromFormat('H:i:s', $riego->total_horas)->totalHours;
        });
    }


}