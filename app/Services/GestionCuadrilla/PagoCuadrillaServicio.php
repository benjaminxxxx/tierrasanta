<?php
namespace App\Services\GestionCuadrilla;

use App\Models\CuadRegistroDiario;
use App\Models\CuadTramoLaboral;
use App\Models\CuadTramoLaboralCuadrillero;
use App\Models\CuadTramoLaboralGrupo;
use Carbon\CarbonPeriod;

class PagoCuadrillaServicio
{
    public static function calcularPagoJornal(
        string $tipoPago,
        string $tipoPeriodo,
        string $fechaInicio,
        string $fechaFin
    ): array {
        $esExtras = ($tipoPago === 'PAGO_CUADRILLA_EXTRAS');
        $acumulaCostos = ($tipoPago === 'PAGO_CUADRILLA');
        $modalidadPago = strtolower($tipoPeriodo);
        $registros = CuadRegistroDiario::query()
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->whereNull('desglose_detalle_id')
            ->whereHas('tramoLaboral', fn($q) => $q->where('acumula_costos', $acumulaCostos))
            // Solo filtra la modalidad de pago del grupo cuando acumula costos ($acumulaCostos === true)
            ->when($acumulaCostos, function ($q) use ($modalidadPago) {
                $q->whereHas('grupo', fn($g) => $g->where('modalidad_pago', $modalidadPago));
            })
            ->with([
                'cuadrillero',
                'grupo',
                'tramoLaboral',
                'actividadesBonos' => function ($q) {
                    $q->where('se_paga_con_jornal', true)
                        ->whereNull('desglose_detalle_id');
                },
            ])
            ->get();
        //dd($registros,$fechaInicio,$fechaFin,$modalidadPago,$acumulaCostos);
        $fechasRango = collect(CarbonPeriod::create($fechaInicio, $fechaFin))
            ->map(fn($f) => $f->format('Y-m-d'))
            ->all();

        // --- Precarga: orden de cada (tramo, grupo) y cuadrilleros ---
        $tramoIds = $registros->pluck('tramo_laboral_id')->unique()->filter();

        $tramoGrupos = CuadTramoLaboralGrupo::whereIn('cuad_tramo_laboral_id', $tramoIds)
            ->get()
            ->keyBy(fn($tg) => $tg->cuad_tramo_laboral_id . '-' . $tg->codigo_grupo);

        $ordenesCuadrillero = CuadTramoLaboralCuadrillero::whereIn('cuad_tramo_laboral_grupo_id', $tramoGrupos->pluck('id'))
            ->get()
            ->keyBy(fn($c) => $c->cuad_tramo_laboral_grupo_id . '-' . $c->cuadrillero_id);

        $cuadrillerosMatriz = [];
        $gruposAcumulados = [];
        $granTotal = 0;
        $registrosDiariosIds = [];
        $actividadesBonoIds = [];

        foreach ($registros as $reg) {
            $cuadrilleroId = $reg->cuadrillero_id;
            $fechaKey = $reg->fecha->format('Y-m-d');
            $codigoGrupo = $reg->codigo_grupo;

            $tramoGrupo = $tramoGrupos->get($reg->tramo_laboral_id . '-' . $codigoGrupo);
            $tramoGrupoId = $tramoGrupo->id ?? null;
            $ordenGrupo = $tramoGrupo->orden ?? 999;
            $ordenCuadrillero = $ordenesCuadrillero->get($tramoGrupoId . '-' . $cuadrilleroId)->orden ?? 999;

            $filaKey = $cuadrilleroId . '-' . ($tramoGrupoId ?? 'sin_tramo');

            if (!isset($cuadrillerosMatriz[$filaKey])) {
                $cuadrillerosMatriz[$filaKey] = [
                    'id' => $cuadrilleroId,
                    'nombre' => $reg->cuadrillero->nombres ?? 'N/A',
                    'codigo' => $reg->cuadrillero->codigo ?? '',
                    'codigo_grupo' => $codigoGrupo,
                    'color_grupo' => $reg->grupo->color ?? '#9CA3AF',
                    'orden_grupo' => $ordenGrupo,
                    'orden_cuadrillero' => $ordenCuadrillero,
                    'dias' => [],
                    'total_cuadrillero' => 0,
                ];
            }

            $montoBonosJornal = 0;
            $bonosDetalle = [];
            foreach ($reg->actividadesBonos as $bonoAct) {
                $montoBonosJornal += (float) $bonoAct->total_bono;
                $bonosDetalle[] = [
                    'actividad_bono_id' => $bonoAct->id,
                    'monto' => (float) $bonoAct->total_bono,
                    'se_paga_con_jornal' => $bonoAct->se_paga_con_jornal,
                ];
                $actividadesBonoIds[] = $bonoAct->id;
            }

            $costoJornalDia = (float) $reg->costo_dia;
            $subtotalDia = $costoJornalDia + $montoBonosJornal;

            $cuadrillerosMatriz[$filaKey]['dias'][$fechaKey] = [
                'registro_diario_id' => $reg->id,
                'costo_dia' => $costoJornalDia,
                'bono_con_jornal' => $montoBonosJornal,
                'subtotal' => $subtotalDia,
                'actividades_bono' => $bonosDetalle,
            ];
            $cuadrillerosMatriz[$filaKey]['total_cuadrillero'] += $subtotalDia;

            // ACUMULACIÓN POR GRUPO PARA EXPORTAR A ALPINE.JS
            if (!isset($gruposAcumulados[$codigoGrupo])) {
                $gruposAcumulados[$codigoGrupo] = [
                    'codigo_grupo' => $codigoGrupo,
                    'nombre_grupo' => $reg->grupo->nombre ?? $codigoGrupo,
                    'color_grupo' => $reg->grupo->color ?? '#9CA3AF',
                    'monto_total' => 0,
                    'registro_diario_ids' => [],
                    'actividad_bono_ids' => [],
                ];
            }

            $gruposAcumulados[$codigoGrupo]['monto_total'] += $subtotalDia;
            $gruposAcumulados[$codigoGrupo]['registro_diario_ids'][] = $reg->id;
            foreach ($bonosDetalle as $b) {
                $gruposAcumulados[$codigoGrupo]['actividad_bono_ids'][] = $b['actividad_bono_id'];
            }

            $granTotal += $subtotalDia;
            $registrosDiariosIds[] = $reg->id;
        }

        // Orden de la matriz
        $filasOrdenadas = collect($cuadrillerosMatriz)
            ->sortBy([
                ['orden_grupo', 'asc'],
                ['orden_cuadrillero', 'asc'],
            ])
            ->values();

        // Procesar marcas de fin de grupo y acumulados por grupo
        $totalesGrupoPorFecha = [];
        $totalGeneralGrupo = 0;
        $filasProcesadas = [];

        foreach ($filasOrdenadas as $index => $item) {
            $grupoActual = $item['codigo_grupo'];
            $siguienteGrupo = $filasOrdenadas->get($index + 1)['codigo_grupo'] ?? null;

            foreach ($fechasRango as $fecha) {
                $subtotalDia = $item['dias'][$fecha]['subtotal'] ?? 0;
                $totalesGrupoPorFecha[$fecha] = ($totalesGrupoPorFecha[$fecha] ?? 0) + $subtotalDia;
            }
            $totalGeneralGrupo += $item['total_cuadrillero'];

            $esUltimo = ($siguienteGrupo !== $grupoActual);
            $item['es_ultimo_del_grupo'] = $esUltimo;

            if ($esUltimo) {
                $item['totales_grupo'] = $totalesGrupoPorFecha;
                $item['total_general_grupo'] = round($totalGeneralGrupo, 2);

                $totalesGrupoPorFecha = [];
                $totalGeneralGrupo = 0;
            }

            $filasProcesadas[] = $item;
        }

        // Limpiar duplicados de IDs en cada grupo acumulado
        $gruposDisponibles = collect($gruposAcumulados)->map(function ($grp) {
            $grp['monto_total'] = round($grp['monto_total'], 2);
            $grp['registro_diario_ids'] = array_values(array_unique($grp['registro_diario_ids']));
            $grp['actividad_bono_ids'] = array_values(array_unique($grp['actividad_bono_ids']));
            return $grp;
        })->values()->all();

        return [
            'fechas' => $fechasRango,
            'cuadrilleros' => $filasProcesadas,
            'grupos_disponibles' => $gruposDisponibles, // Se consumirá en Alpine.js
            'gran_total' => round($granTotal, 2),
            'total_registros_diarios_ids' => array_unique($registrosDiariosIds),
            'total_actividades_bono_ids' => array_unique($actividadesBonoIds),
        ];
    }
}