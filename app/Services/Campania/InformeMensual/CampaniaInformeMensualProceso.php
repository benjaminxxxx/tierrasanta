<?php

namespace App\Services\Campania\InformeMensual;

use App\Models\CampaniaInformeMensual;
use App\Models\CampoCampania;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Genera y guarda (congela) los informes mensuales de campaña.
 *
 * - generar(): el informe de una campaña en un mes. Si ya existía se reemplaza y sube la versión.
 * - generarMes(): todas las campañas activas del mes (incluida la que cierra y la que empieza en el mismo campo).
 * - regenerarDesde(): corrige un mes y vuelve a generar los meses siguientes ya generados de esa campaña, para que
 *   la secuencia mensual quede coherente.
 */
class CampaniaInformeMensualProceso
{
    public function __construct(private CampaniaInformeMensualConsulta $consulta)
    {
    }

    public function generar(CampoCampania $campania, int $anio, int $mes): CampaniaInformeMensual
    {
        if (!CampaniaInformeMensualReglas::activaEnMes($campania, $anio, $mes)) {
            throw ValidationException::withMessages(['periodo' => "La campaña {$campania->nombre_campania} del campo {$campania->campo} no está activa en " . sprintf('%02d/%04d', $mes, $anio) . '.']);
        }

        $datos = $this->consulta->construir($campania, $anio, $mes);
        $advertencias = $datos['advertencias'];
        unset($datos['advertencias']);

        $area = (float) $datos['ficha']['area'];
        $tc = (float) $datos['ficha']['tipo_cambio'];
        $total = $datos['costos']['total_soles'];

        return DB::transaction(function () use ($campania, $anio, $mes, $datos, $advertencias, $area, $tc, $total) {
            $informe = CampaniaInformeMensual::lockForUpdate()->firstOrNew([
                'campo_campania_id' => $campania->id, 'anio' => $anio, 'mes' => $mes,
            ]);
            $informe->fill([
                'fecha_desde' => $datos['ficha']['fecha_inicio'],
                'fecha_corte' => $datos['ficha']['fecha_corte'],
                'es_cierre' => $datos['ficha']['es_cierre'],
                'version' => $informe->exists ? $informe->version + 1 : 1,
                'datos' => $datos,
                'advertencias' => $advertencias,
                'total_soles' => $total,
                'total_usd_ha' => $area > 0 && $tc > 0 ? round($total / $tc / $area, 2) : null,
                'generado_por' => Auth::id(),
            ])->save();
            return $informe;
        });
    }

    /**
     * Informes de todas las campañas activas del mes. Las campañas abiertas que ya fueron reemplazadas por otra
     * del mismo campo no se generan (les falta la fecha de fin): se devuelven aparte para avisar.
     *
     * @return array{generados: CampaniaInformeMensual[], omitidas: array<int, array{campania: CampoCampania, motivo: string}>, errores: array<int, string>}
     */
    public function generarMes(int $anio, int $mes, bool $soloPendientes = false): array
    {
        $resultado = ['generados' => [], 'omitidas' => [], 'errores' => []];
        $campanias = app(CampaniaInformeMensualEstadoConsulta::class)->campaniasDelMes($anio, $mes);
        $existentes = CampaniaInformeMensual::where('anio', $anio)->where('mes', $mes)->pluck('campo_campania_id')->all();

        foreach ($campanias['activas'] as $campania) {
            if ($soloPendientes && in_array($campania->id, $existentes, true)) {
                continue;
            }
            try {
                $resultado['generados'][] = $this->generar($campania, $anio, $mes);
            } catch (\Throwable $e) {
                $resultado['errores'][] = "{$campania->campo} {$campania->nombre_campania}: {$e->getMessage()}";
            }
        }
        $resultado['omitidas'] = $campanias['reemplazadas'];
        return $resultado;
    }

    /**
     * Vuelve a generar el mes indicado y todos los meses posteriores que ya estaban generados para la campaña.
     *
     * @return CampaniaInformeMensual[]
     */
    public function regenerarDesde(CampoCampania $campania, int $anio, int $mes): array
    {
        $desde = sprintf('%04d-%02d', $anio, $mes);
        $posteriores = CampaniaInformeMensual::where('campo_campania_id', $campania->id)->get()
            ->filter(fn($i) => $i->periodo > $desde)
            ->sortBy('periodo');

        $regenerados = [$this->generar($campania, $anio, $mes)];
        foreach ($posteriores as $informe) {
            $regenerados[] = $this->generar($campania, $informe->anio, $informe->mes);
        }
        return $regenerados;
    }
}
