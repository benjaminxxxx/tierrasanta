<?php

namespace App\Services\Costos\Campania;

use App\Models\CampoCampania;
use App\Models\CostoMensual;
use App\Services\Campania\CostoProduccion\CampaniaCostoProduccionReglas;
use App\Services\Costos\Consolidacion\ConsolidarCostoManoObraServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Costos de una campaña leídos SOLO de la BDD (resumen_costo_diarios), mes a mes y por sección (mano de obra,
 * maquinaria, fertilizantes, pesticidas, costo operativo, costo fijo). La BDD es la representación real del costo:
 * ahí llegan también la mano de obra indirecta (FDM) y los costos externos al distribuirse cada mes entre los campos
 * (origen costo_operativo / costo_fijo, con el último día activo del mes).
 *
 * Por cada mes indica si ya se distribuyeron los costos fijos/operativos y si la mano de obra indirecta del mes está
 * calculada: sin eso el costo del mes está incompleto.
 */
class CostosCampaniaConsulta
{
    public function consultar(CampoCampania $campania): array
    {
        $hoy = Carbon::today()->toDateString();
        $inicio = $campania->fecha_inicio->toDateString();
        $finEfectivo = ConsolidarCostoManoObraServicio::finEfectivoCampania($campania);
        $fin = $finEfectivo ? min($finEfectivo, $hoy) : $hoy;

        $filas = DB::table('resumen_costo_diarios')
            ->where('campania', $campania->nombre_campania)->where('campo', $campania->campo)
            ->selectRaw("DATE_FORMAT(fecha, '%Y-%m') AS mes, origen_tipo, SUM(costo_total) AS costo, SUM(cantidad_jornales) AS jornales, COUNT(*) AS registros")
            ->groupBy('mes', 'origen_tipo')->get();

        $secciones = array_keys(CampaniaCostoProduccionReglas::SECCIONES);
        $vacio = array_fill_keys($secciones, 0.0);

        // Meses de la campaña (y cualquier mes con costos fuera del rango, para que el total cuadre)
        $meses = [];
        for ($m = Carbon::parse($inicio)->startOfMonth(); $m->toDateString() <= $fin; $m->addMonth()) {
            $meses[$m->format('Y-m')] = null;
        }
        foreach ($filas->pluck('mes')->unique() as $m) {
            $meses[$m] ??= null;
        }
        ksort($meses);

        $guardados = CostoMensual::get(['anio', 'mes', 'moi_calculado_en', 'moi_concluyente'])
            ->keyBy(fn($c) => sprintf('%04d-%02d', $c->anio, $c->mes));

        $porMes = [];
        foreach (array_keys($meses) as $clave) {
            $delMes = $filas->where('mes', $clave);
            $montos = $vacio;
            foreach ($delMes as $f) {
                $montos[CampaniaCostoProduccionReglas::seccionDeOrigen($f->origen_tipo)] += (float) $f->costo;
            }
            $fecha = Carbon::parse("{$clave}-01");
            $terminado = $fecha->copy()->endOfMonth()->toDateString() < $hoy;
            $distribuido = $delMes->whereIn('origen_tipo', ['costo_fijo', 'costo_operativo'])->isNotEmpty();
            $moi = $guardados->get($clave);
            $avisos = [];
            if ($terminado && !$distribuido) {
                $avisos[] = 'Sin costos fijos/operativos distribuidos';
            }
            if ($terminado && !$moi?->moi_calculado_en) {
                $avisos[] = 'Mano de obra indirecta sin calcular';
            } elseif ($moi?->moi_calculado_en && !$moi->moi_concluyente) {
                $avisos[] = 'Mano de obra indirecta no concluyente';
            }
            $porMes[] = [
                'clave' => $clave,
                'anio' => $fecha->year,
                'mes' => $fecha->month,
                'nombre' => ucfirst($fecha->translatedFormat('F Y')),
                'fuera_de_rango' => $clave < substr($inicio, 0, 7) || $clave > substr($fin, 0, 7),
                'en_curso' => !$terminado,
                'montos' => array_map(fn($v) => round($v, 2), $montos),
                'total' => round(array_sum($montos), 2),
                'jornales' => round((float) $delMes->sum('jornales'), 2),
                'registros' => (int) $delMes->sum('registros'),
                'distribuido' => $distribuido,
                'avisos' => $avisos,
            ];
        }

        $totales = $vacio;
        foreach ($porMes as $m) {
            foreach ($m['montos'] as $s => $v) {
                $totales[$s] += $v;
            }
        }
        $total = round(array_sum($totales), 2);
        $area = (float) $campania->area;

        return [
            'inicio' => $inicio,
            'fin' => $fin,
            'cerrada' => (bool) $campania->fecha_fin,
            'area' => $area,
            'secciones' => collect(CampaniaCostoProduccionReglas::SECCIONES)
                ->filter(fn($s, $clave) => $clave !== 'otros' || $totales['otros'] > 0)
                ->map(fn($s) => $s[1])->all(),
            'meses' => $porMes,
            'totales' => array_map(fn($v) => round($v, 2), $totales),
            'total' => $total,
            'por_ha' => $area > 0 ? round($total / $area, 2) : null,
            'meses_con_avisos' => collect($porMes)->filter(fn($m) => $m['avisos'])->count(),
        ];
    }
}
