<?php

namespace App\Services\Campania\Registro;

use App\Models\CampoCampania;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Qué cambia si se modifican las fechas de una campaña (solo lectura).
 *
 * Los costos (resumen_costo_diarios) y las labores se asignan a la campaña por su rango de fechas, así que al
 * mover las fechas pasan de una campaña a otra. Otros registros guardan la campaña de forma fija:
 * - TABLAS_REASIGNADAS: se reasignan solas (triggers de campos_campanias, o CampaniaRegistroProceso para el riego).
 * - TABLAS_CON_CAMPANIA: si su fecha queda fuera del nuevo rango siguen apuntando a esta campaña; hay que revisarlos.
 */
class CampaniaRegistroImpactoConsulta
{
    /** tabla => [columna de campaña, columna de fecha, descripción] */
    private const TABLAS_REASIGNADAS = [
        'cochinilla_ingresos' => ['campo_campania_id', 'fecha', 'ingresos de cochinilla'],
        'cochinilla_infestaciones' => ['campo_campania_id', 'fecha', 'infestaciones'],
        'reg_registro_diario' => ['campo_campania_id', 'fecha', 'registros de riego'],
    ];

    /** tabla => [columna de campaña, columna de fecha, descripción] */
    private const TABLAS_CON_CAMPANIA = [
        'ins_res_fertilizante_campanias' => ['campo_campania_id', 'fecha', 'fertilizaciones'],
        'pesticidas_campanias' => ['campo_campania_id', 'fecha', 'aplicaciones de pesticidas'],
        'eval_brotes_por_pisos' => ['campania_id', 'fecha', 'evaluaciones de brotes'],
        'venta_facturada_cochinillas' => ['campo_campania_id', 'fecha', 'ventas facturadas'],
        'resumen_consumo_productos' => ['campos_campanias_id', 'fecha', 'consumos de productos'],
    ];

    /**
     * @return array{
     *   hay_cambios: bool,
     *   salen: array<int, array{0: string, 1: string}>,
     *   entran: array<int, array{0: string, 1: string}>,
     *   avisos: string[],
     *   rango_regenerar: ?array{0: string, 1: string},
     * }
     */
    public function analizar(CampoCampania $campania, string $nuevoInicio, ?string $nuevoFin): array
    {
        $hoy = Carbon::today()->toDateString();
        $viejoInicio = $campania->fecha_inicio->toDateString();
        $viejoFin = $campania->fecha_fin?->toDateString();
        $nuevoInicio = Carbon::parse($nuevoInicio)->toDateString();
        $nuevoFin = $nuevoFin ? Carbon::parse($nuevoFin)->toDateString() : null;

        // Rango EFECTIVO, como en el consolidado de costos (ConsolidarCostoManoObraServicio::finEfectivoCampania):
        // manda la campaña más reciente, así que una campaña abierta termina el día antes de la siguiente.
        // Sin esto, cerrar una campaña vieja que ya tenía otra posterior "movía" meses que no cambian de dueño.
        $viejoFinEfectivo = $this->finEfectivo($campania, $viejoInicio, $viejoFin, $hoy);
        $nuevoFinEfectivo = $this->finEfectivo($campania, $nuevoInicio, $nuevoFin, $hoy);

        [$salen, $entran] = $this->tramosCambiados($viejoInicio, $viejoFinEfectivo, $nuevoInicio, $nuevoFinEfectivo);
        $avisos = [];

        foreach ($salen as [$desde, $hasta]) {
            $rango = $this->textoRango($desde, $hasta);
            $costo = $this->costoRegistrado($campania->campo, $desde, $hasta);
            if ($costo['total'] > 0) {
                $avisos[] = "Del {$rango} hay S/ " . number_format($costo['total'], 2)
                    . " en costos del campo {$campania->campo} que dejarán de ser de esta campaña.";
            }
            foreach ($this->registrosFijosEnRango(self::TABLAS_REASIGNADAS, $campania->id, $desde, $hasta) as $descripcion => $cantidad) {
                $avisos[] = "{$cantidad} {$descripcion} del {$rango} saldrán de esta campaña (se reasignan solos a la que corresponda por fecha).";
            }
            foreach ($this->registrosFijosEnRango(self::TABLAS_CON_CAMPANIA, $campania->id, $desde, $hasta) as $descripcion => $cantidad) {
                $avisos[] = "{$cantidad} {$descripcion} del {$rango} están asignados a esta campaña y quedarán fuera de su rango: revísalos.";
            }
        }

        foreach ($entran as [$desde, $hasta]) {
            $rango = $this->textoRango($desde, $hasta);
            $costo = $this->costoRegistrado($campania->campo, $desde, $hasta);
            if ($costo['total'] > 0) {
                $de = $costo['campanias'] ? ' (hoy de ' . implode(', ', $costo['campanias']) . ')' : '';
                $avisos[] = "Del {$rango} hay S/ " . number_format($costo['total'], 2)
                    . " en costos del campo {$campania->campo}{$de} que pasarán a esta campaña.";
            }
        }

        if ($viejoFin === null && $nuevoFin !== null && $viejoFinEfectivo !== $nuevoFinEfectivo) {
            $avisos[] = 'La campaña quedará cerrada el ' . formatear_fecha($nuevoFin)
                . '. Lo que se registre después en el campo irá a la siguiente campaña.';
        }

        $tramos = array_merge($salen, $entran);
        $rangoRegenerar = $tramos
            ? [min(array_column($tramos, 0)), min($hoy, max(array_column($tramos, 1)))]
            : null;
        if ($rangoRegenerar && $rangoRegenerar[0] > $rangoRegenerar[1]) {
            $rangoRegenerar = null;
        }

        if ($rangoRegenerar) {
            $avisos[] = 'Se regenerará la mano de obra del ' . $this->textoRango(...$rangoRegenerar)
                . '. Los demás costos (insumos, maquinaria, servicios…) se reasignan al volver a consolidar esos meses.';
        }

        return [
            'hay_cambios' => $viejoInicio !== $nuevoInicio || $viejoFin !== $nuevoFin,
            'salen' => $salen,
            'entran' => $entran,
            'avisos' => $avisos,
            'rango_regenerar' => $rangoRegenerar,
        ];
    }

    /** Fin efectivo: el cierre (u hoy si está abierta), pero nunca después del día anterior a la siguiente campaña. */
    private function finEfectivo(CampoCampania $campania, string $inicio, ?string $fin, string $hoy): string
    {
        $inicioSiguiente = CampoCampania::where('campo', $campania->campo)
            ->where('id', '<>', $campania->id)
            ->whereDate('fecha_inicio', '>', $inicio)
            ->min('fecha_inicio');

        $efectivo = $fin ?? $hoy;
        if ($inicioSiguiente) {
            $efectivo = min($efectivo, Carbon::parse($inicioSiguiente)->subDay()->toDateString());
        }
        return $efectivo;
    }

    /**
     * Días que salen del rango y días que entran. Los rangos abiertos se cortan en hoy.
     *
     * @return array{0: array, 1: array} [salen, entran], cada uno lista de [desde, hasta]
     */
    private function tramosCambiados(string $vi, string $vf, string $ni, string $nf): array
    {
        $diaAntes = fn($d) => Carbon::parse($d)->subDay()->toDateString();
        $diaDespues = fn($d) => Carbon::parse($d)->addDay()->toDateString();
        $salen = [];
        $entran = [];

        if ($ni > $vi) {
            $salen[] = [$vi, min($diaAntes($ni), $vf)];
        } elseif ($ni < $vi) {
            $entran[] = [$ni, min($diaAntes($vi), $nf)];
        }
        if ($nf < $vf) {
            $salen[] = [max($diaDespues($nf), $vi), $vf];
        } elseif ($nf > $vf) {
            $entran[] = [max($diaDespues($vf), $ni), $nf];
        }

        $validos = fn($tramos) => array_values(array_filter($tramos, fn($t) => $t[0] <= $t[1]));
        return [$validos($salen), $validos($entran)];
    }

    /** @return array{total: float, campanias: string[]} */
    private function costoRegistrado(string $campo, string $desde, string $hasta): array
    {
        $filas = DB::table('resumen_costo_diarios')
            ->where('campo', $campo)
            ->whereBetween('fecha', [$desde, $hasta])
            ->groupBy('campania')
            ->selectRaw('campania, SUM(costo_total) as total')
            ->get();

        return [
            'total' => (float) $filas->sum('total'),
            'campanias' => $filas->pluck('campania')->filter()->values()->all(),
        ];
    }

    /** @return array<string, int> descripción => cantidad */
    private function registrosFijosEnRango(array $tablas, int $campaniaId, string $desde, string $hasta): array
    {
        $resultado = [];
        foreach ($tablas as $tabla => [$columna, $fecha, $descripcion]) {
            $cantidad = DB::table($tabla)
                ->where($columna, $campaniaId)
                ->whereBetween($fecha, [$desde, $hasta])
                ->count();
            if ($cantidad > 0) {
                $resultado[$descripcion] = $cantidad;
            }
        }
        return $resultado;
    }

    private function textoRango(string $desde, string $hasta): string
    {
        return $desde === $hasta
            ? formatear_fecha($desde)
            : formatear_fecha($desde) . ' al ' . formatear_fecha($hasta);
    }
}
