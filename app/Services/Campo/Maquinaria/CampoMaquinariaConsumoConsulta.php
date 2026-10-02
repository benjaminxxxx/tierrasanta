<?php

namespace App\Services\Campo\Maquinaria;

use App\Models\Maquinaria;
use Illuminate\Support\Facades\DB;

/**
 * Consumo anual de combustible de una máquina, mes a mes: galones recibidos vs horas distribuidas.
 *
 * - Galones: salidas de combustible del kardex negro (el consumo real; las distribuciones solo se
 *   vinculan a esas salidas, el kardex blanco duplicaría cantidades), por fecha de la salida.
 * - Horas: distribuciones de combustible (campos y FDM), por fecha del trabajo.
 *
 * Mes a mes el rendimiento es ruidoso (lo que sobra de una recarga se usa al mes siguiente); el
 * acumulado del año converge al consumo real por hora, que se compara con el estimado de la ficha.
 */
class CampoMaquinariaConsumoConsulta
{
    /** Desvío del promedio anual frente al estimado de la ficha que dispara la alerta. */
    public const TOLERANCIA_ESTIMADO = 0.20;

    /** Desvío del rendimiento de un mes frente al promedio anual para marcarlo. */
    public const TOLERANCIA_MES = 0.30;

    public function reporteAnual(int $maquinariaId, int $anio): array
    {
        $maquinaria = Maquinaria::with('combustible.tabla6')->findOrFail($maquinariaId);
        $inicio = "{$anio}-01-01";
        $fin = "{$anio}-12-31";

        $salidas = DB::table('almacen_producto_salidas as s')
            ->join('productos as p', 'p.id', '=', 's.producto_id')
            ->where('p.categoria_codigo', 'combustible')
            ->where('s.maquinaria_id', $maquinariaId)
            ->where('s.tipo_kardex', 'negro')
            ->whereBetween('s.fecha_reporte', [$inicio, $fin])
            ->selectRaw('MONTH(s.fecha_reporte) as mes, COUNT(*) as salidas, SUM(s.cantidad) as galones, SUM(s.total_costo) as costo,
                SUM(NOT EXISTS(SELECT 1 FROM distribucion_combustibles d WHERE d.almacen_producto_salida_id = s.id)) as sin_distribuir,
                SUM(CASE WHEN EXISTS(SELECT 1 FROM distribucion_combustibles d WHERE d.almacen_producto_salida_id = s.id)
                    THEN 0 ELSE s.cantidad END) as galones_sin_distribuir')
            ->groupByRaw('MONTH(s.fecha_reporte)')
            ->get()->keyBy('mes');

        // Un turno que pasa la medianoche (salida < inicio) suma 24 h
        $horas = DB::table('distribucion_combustibles')
            ->where('maquinaria_id', $maquinariaId)
            ->whereBetween('fecha', [$inicio, $fin])
            ->whereNotNull('hora_inicio')->whereNotNull('hora_salida')
            ->selectRaw("MONTH(fecha) as mes, COUNT(*) as registros,
                SUM(TIME_TO_SEC(TIMEDIFF(hora_salida, hora_inicio)) + IF(hora_salida < hora_inicio, 86400, 0)) / 3600 as horas,
                SUM(CASE WHEN UPPER(TRIM(campo)) = 'FDM' THEN TIME_TO_SEC(TIMEDIFF(hora_salida, hora_inicio)) + IF(hora_salida < hora_inicio, 86400, 0) ELSE 0 END) / 3600 as horas_fdm")
            ->groupByRaw('MONTH(fecha)')
            ->get()->keyBy('mes');

        $meses = [];
        $galonesAcum = 0.0;
        $horasAcum = 0.0;
        for ($m = 1; $m <= 12; $m++) {
            $s = $salidas->get($m);
            $h = $horas->get($m);
            $galones = round((float) ($s->galones ?? 0), 3);
            $horasMes = round((float) ($h->horas ?? 0), 2);
            $galonesAcum += $galones;
            $horasAcum += $horasMes;

            $meses[] = [
                'mes' => $m,
                'salidas' => (int) ($s->salidas ?? 0),
                'galones' => $galones,
                'costo' => round((float) ($s->costo ?? 0), 2),
                'sin_distribuir' => (int) ($s->sin_distribuir ?? 0),
                'galones_sin_distribuir' => round((float) ($s->galones_sin_distribuir ?? 0), 3),
                'registros' => (int) ($h->registros ?? 0),
                'horas' => $horasMes,
                'horas_fdm' => round((float) ($h->horas_fdm ?? 0), 2),
                'rendimiento' => $horasMes > 0 ? round($galones / $horasMes, 3) : null,
                'rendimiento_acumulado' => $horasAcum > 0 ? round($galonesAcum / $horasAcum, 3) : null,
                'alerta' => null,
            ];
        }

        $totalGalones = round($galonesAcum, 3);
        $totalHoras = round($horasAcum, 2);
        $promedio = $totalHoras > 0 ? round($totalGalones / $totalHoras, 3) : null;
        $estimado = $maquinaria->consumo_modo === 'hora' ? $maquinaria->consumo_estimado : null;

        foreach ($meses as &$fila) {
            $fila['alerta'] = $this->alertaMes($fila, $promedio, (bool) $maquinaria->usa_distribucion);
        }
        unset($fila);

        return [
            'maquinaria' => [
                'id' => $maquinaria->id,
                'nombre' => $maquinaria->nombre,
                'unidad' => $maquinaria->unidad_combustible,
                'combustible' => $maquinaria->combustible?->nombre_comercial,
                'usa_distribucion' => (bool) $maquinaria->usa_distribucion,
                'consumo_texto' => $maquinaria->consumo_texto,
            ],
            'anio' => $anio,
            'meses' => $meses,
            'totales' => [
                'salidas' => array_sum(array_column($meses, 'salidas')),
                'galones' => $totalGalones,
                'costo' => round(array_sum(array_column($meses, 'costo')), 2),
                'sin_distribuir' => array_sum(array_column($meses, 'sin_distribuir')),
                'galones_sin_distribuir' => round(array_sum(array_column($meses, 'galones_sin_distribuir')), 3),
                'registros' => array_sum(array_column($meses, 'registros')),
                'horas' => $totalHoras,
                'horas_fdm' => round(array_sum(array_column($meses, 'horas_fdm')), 2),
            ],
            'promedio' => $promedio,
            'estimado' => $estimado,
            'alertas' => $this->alertasAnuales($maquinaria, $totalGalones, $totalHoras, $promedio, $estimado, $meses),
        ];
    }

    private function alertaMes(array $fila, ?float $promedio, bool $usaDistribucion): ?string
    {
        if ($fila['galones'] > 0 && $fila['horas'] <= 0) {
            return $usaDistribucion ? 'Recibió combustible pero no tiene horas distribuidas.' : null;
        }
        if ($fila['galones'] <= 0 && $fila['horas'] > 0) {
            return 'Tiene horas pero no recibió combustible (usó lo que sobró del mes anterior).';
        }
        if ($promedio && $fila['rendimiento'] !== null) {
            $desvio = ($fila['rendimiento'] - $promedio) / $promedio;
            if (abs($desvio) > self::TOLERANCIA_MES) {
                return sprintf('Rendimiento %s%% %s el promedio del año.', number_format(abs($desvio) * 100, 0),
                    $desvio > 0 ? 'sobre' : 'bajo');
            }
        }
        return null;
    }

    /** @return array<int, array{variante: string, texto: string}> */
    private function alertasAnuales(Maquinaria $maquinaria, float $galones, float $horas, ?float $promedio, ?float $estimado, array $meses): array
    {
        $alertas = [];
        $unidad = $maquinaria->unidad_combustible;

        if (!$maquinaria->usa_distribucion) {
            $alertas[] = ['variante' => 'info', 'texto' => 'Esta máquina no distribuye su trabajo por campo (su combustible va a FDM): no hay horas para comparar.'];
            return $alertas;
        }
        if ($galones <= 0 && $horas <= 0) {
            return $alertas;
        }
        if ($horas <= 0) {
            $alertas[] = ['variante' => 'warning', 'texto' => "Recibió {$this->num($galones)} {$unidad} en el año, pero no tiene horas distribuidas."];
            return $alertas;
        }

        if ($estimado) {
            $desvio = ($promedio - $estimado) / $estimado;
            $pct = number_format(abs($desvio) * 100, 0);
            if ($desvio > self::TOLERANCIA_ESTIMADO) {
                $alertas[] = ['variante' => 'danger', 'texto' => "Consume {$pct}% más de lo estimado ({$this->num($promedio)} vs {$this->num($estimado)} {$unidad}/h): "
                    . 'revisa posibles pérdidas de combustible, horas sin registrar o desgaste/falla del motor.'];
            } elseif ($desvio < -self::TOLERANCIA_ESTIMADO) {
                $alertas[] = ['variante' => 'warning', 'texto' => "Consume {$pct}% menos de lo estimado ({$this->num($promedio)} vs {$this->num($estimado)} {$unidad}/h): "
                    . 'puede haber horas de más en la distribución o el estimado de la ficha es alto.'];
            } else {
                $alertas[] = ['variante' => 'success', 'texto' => "El consumo real ({$this->num($promedio)} {$unidad}/h) está dentro del ±"
                    . (self::TOLERANCIA_ESTIMADO * 100) . "% del estimado ({$this->num($estimado)} {$unidad}/h)."];
            }
        } else {
            $alertas[] = ['variante' => 'info', 'texto' => "La ficha no tiene consumo estimado por hora: el promedio del año ({$this->num($promedio)} {$unidad}/h) sirve de referencia."];
        }

        $sinDistribuir = array_sum(array_column($meses, 'sin_distribuir'));
        if ($sinDistribuir > 0) {
            $alertas[] = ['variante' => 'warning', 'texto' => "{$sinDistribuir} salida(s) de combustible aún sin distribuir "
                . '(' . $this->num(array_sum(array_column($meses, 'galones_sin_distribuir'))) . " {$unidad}): sus galones ya cuentan pero sus horas no, así que el promedio sale más alto hasta que se distribuyan."];
        }

        return $alertas;
    }

    private function num(float $valor): string
    {
        return rtrim(rtrim(number_format($valor, 3, '.', ','), '0'), '.');
    }
}
