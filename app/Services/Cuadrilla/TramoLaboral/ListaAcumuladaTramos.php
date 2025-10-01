<?php

namespace App\Services\Cuadrilla\TramoLaboral;

use App\Models\CuadRegistroDiario;
use App\Models\CuadResumenPorTramo;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;

class ListaAcumuladaTramos
{
    public function obtenerPagoCuadrillerosPorTramo($resumenTramo, $listaPago)
    {
        $tramos = $this->obtenerTramosAcumulados($resumenTramo);
        /*$listaCuadrilleros = $tramos
            ->flatMap(fn($tramo) => $tramo->cuadrilleros())
            ->unique('id')
            ->sortBy('nombres')
            ->values();*/
        $resultado = [];

        foreach ($tramos as $tramo) {

            $inicio = Carbon::parse($tramo->fecha_inicio);
            $fin = Carbon::parse($tramo->fecha_fin);

            $pagosDelTramo = $this->obtenerPagosParaTramo($inicio, $fin, $listaPago);

            $resultado[] = [
                'fecha_inicio' => $inicio->toDateString(),
                'fecha_fin' => $fin->toDateString(),
                'pagos' => $pagosDelTramo,
            ];
        }
        return $resultado;

    }
    private function obtenerPagosParaTramo(Carbon $inicio, Carbon $fin, $listaPago): array
    {
        $pagosFormateados = [];
        foreach ($listaPago as $trabajador) {
            $pagoPersona = ['nombre' => $trabajador['nombres']];

            // Itera día por día dentro del tramo para buscar pagos.
            for ($fecha = $inicio->copy(); $fecha->lte($fin); $fecha->addDay()) {
                $fechaKey = $fecha->toDateString();
                if (isset($trabajador[$fechaKey]) && !is_null($trabajador[$fechaKey]['costo_dia'])) {
                    $pagoPersona[$fechaKey] = (float) $trabajador[$fechaKey]['costo_dia'];
                }
            }

            $pagosFormateados[] = $pagoPersona;
        }

        return $pagosFormateados;
    }
    public function obtenerListaCuadrilleros(CuadResumenPorTramo $resumenTramo): array
    {
        // 1. Obtener todos los tramos acumulados (recursivamente hacia atrás)
        $tramos = $this->obtenerTramosAcumulados($resumenTramo);
        //dd($tramos);
        // 2. Unificar cuadrilleros
        $listaCuadrilleros = $tramos
            ->flatMap(fn($tramo) => $tramo->cuadrilleros())
            ->unique('id')
            ->sortBy('nombres')
            ->keyBy('id');
        //Too few arguments to function Illuminate\Support\Collection::get(), 0 passed in C:\laragon\www\tierrasanta\app\Services\Cuadrilla\TramoLaboral\ListaAcumuladaTramos.php on line 19 and at least 1 expected

        // 3. Construir la lista de pagos (tu lógica de obtenerListaResumen)
        return $this->construirListaPagos($listaCuadrilleros, $resumenTramo);
    }
    private function obtenerTramosAcumulados(CuadResumenPorTramo $tramo)
    {
        $tramos = collect();
        $actual = $tramo;

        while ($actual) {
            $tramos->push($actual);

            if (!$actual->tramo_acumulado_id) {
                break;
            }

            $actual = CuadResumenPorTramo::where('tramo_id', $actual->tramo_acumulado_id)
                ->where('grupo_codigo', $actual->grupo_codigo)->first();
        }

        return $tramos->sortBy('fecha_inicio')->values();
    }
    private function construirListaPagos($listaCuadrilleros, CuadResumenPorTramo $resumenTramo): array
    {
        $fechaFin = $resumenTramo->fecha_fin;
        $codigoGrupo = $resumenTramo->grupo_codigo;
        $fechaInicioPago = $resumenTramo->fecha_acumulada;
        $tramoLaboral = $resumenTramo->tramo;

        $pagosEnTramo = CuadRegistroDiario::whereBetween('fecha', [$fechaInicioPago, $fechaFin])
            ->where('codigo_grupo', $codigoGrupo)
            ->get();

        $bonosPendientes = collect();
        $fechaInicio = null;

        $bonosPendientes = CuadRegistroDiario::where('bono_esta_pagado', false)
            ->where('total_bono', '>', 0)
            ->where('codigo_grupo', $codigoGrupo)
            ->whereDate('fecha', '<', $fechaInicioPago)
            ->get();

        if ($bonosPendientes->isNotEmpty()) {
            $fechaInicio = $bonosPendientes->min('fecha');
        }


        $pagosIndexados = $pagosEnTramo
            ->groupBy('cuadrillero_id')
            ->map(fn($grupo) => $grupo->groupBy(fn($item) => Carbon::parse($item->fecha)->toDateString()));

        $fechaInicioEvaluacion = $fechaInicio ?? $fechaInicioPago;
        $periodo = collect(CarbonPeriod::create($fechaInicioEvaluacion, $fechaFin))
            ->map(fn($date) => $date->toDateString())
            ->all();

        $cuadrilleros = $listaCuadrilleros->map(function ($cuadrillero) use ($periodo, $pagosIndexados, $bonosPendientes, $fechaInicioPago,$tramoLaboral) {
            $info = $cuadrillero;

            $data = [
                'nombres' => $info->nombres,
                'dni' => $info->dni,
                'monto' => 0,
                'bono' => 0,
                'total' => 0,
            ];

            $monto = 0;
            $bono = 0;

            foreach ($periodo as $fecha) {
                $registro = null;
                $fechaObj = Carbon::parse($fecha)->startOfDay();
                $inicioObj = Carbon::parse($fechaInicioPago)->startOfDay();

                if ($fechaObj->gte($inicioObj)) {
                    $registro = $pagosIndexados[$cuadrillero->id][$fecha][0] ?? null;
                }

                if ($fechaObj->lt($inicioObj)) {
                    $registro = $bonosPendientes
                        ->where('cuadrillero_id', $info->id)
                        ->first(fn($item) => Carbon::parse($item->fecha)->isSameDay($fechaObj));
                    
                    $costoDia = $registro->costo_dia ?? null;
                    $totalBono = $registro->total_bono ?? 0;
                    $data[$fecha] = [
                        'costo_dia' => $costoDia,
                        'total_bono' => $totalBono,
                        'esta_pagado' => $registro->esta_pagado ?? false,
                        'bono_esta_pagado' => $registro->bono_esta_pagado ?? false,
                        'tramo_pagado_jornal_id' => $registro->tramo_pagado_jornal_id ?? null,
                        'tramo_pagado_bono_id' => $registro->tramo_pagado_bono_id ?? null,
                        
                        'bloqueado_jornal' => isset($registro->tramo_pagado_jornal_id) && $registro->tramo_pagado_jornal_id != $tramoLaboral->id,
                        'bloqueado_bono' => isset($registro->tramo_pagado_bono_id) && $registro->tramo_pagado_bono_id != $tramoLaboral->id,
                    ];
                    $bono += $totalBono;
                } else {
                    $costoDia = $registro->costo_dia ?? null;
                    $totalBono = $registro->total_bono ?? 0;
                    $data[$fecha] = [
                        'costo_dia' => $costoDia,
                        'total_bono' => $totalBono,
                        'esta_pagado' => $registro->esta_pagado ?? false,
                        'bono_esta_pagado' => $registro->bono_esta_pagado ?? false,
                        'tramo_pagado_jornal_id' => $registro->tramo_pagado_jornal_id ?? null,
                        'tramo_pagado_bono_id' => $registro->tramo_pagado_bono_id ?? null,
                        'bloqueado_jornal' => isset($registro->tramo_pagado_jornal_id) && $registro->tramo_pagado_jornal_id != $tramoLaboral->id,
                        'bloqueado_bono' => isset($registro->tramo_pagado_bono_id) && $registro->tramo_pagado_bono_id != $tramoLaboral->id,
                    ];
                }
            }

            return $data;
        })->toArray();
        
        return [
            'periodo' => $periodo,
            'listaPago' => $cuadrilleros,
        ];
    }
}