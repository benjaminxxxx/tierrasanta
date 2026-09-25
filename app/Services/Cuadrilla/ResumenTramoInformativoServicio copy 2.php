<?php

namespace App\Services\Cuadrilla;

use App\Models\CuadActividadBono;
use App\Models\CuadRegistroDiario;
use App\Models\CuadTramoLaboral;
use App\Models\CuaGrupo;
use App\Models\GastoAdicionalPorGrupoCuadrilla;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ResumenTramoInformativoServicioCopy2
{
    protected array $meses = [
        1 => 'enero',
        2 => 'febrero',
        3 => 'marzo',
        4 => 'abril',
        5 => 'mayo',
        6 => 'junio',
        7 => 'julio',
        8 => 'agosto',
        9 => 'septiembre',
        10 => 'octubre',
        11 => 'noviembre',
        12 => 'diciembre',
    ];

    public function generar($tramoLaboralId): Collection
    {
        $tramoLaboral = CuadTramoLaboral::find($tramoLaboralId);
        $fechaCorte = $tramoLaboral->fecha_fin;
        $fechaCorteBono = $tramoLaboral->fecha_hasta_bono ?? $fechaCorte;
        $acumula = (bool) $tramoLaboral->acumula_costos;

        $codigosGrupo = $tramoLaboral->gruposEnTramos()->pluck('codigo_grupo');
        if ($acumula) {
            $codigosGrupo = $codigosGrupo->merge($this->codigosConAlgoAunPendiente($fechaCorte, $fechaCorteBono))->unique();
        }

        return $codigosGrupo->flatMap(function ($codigoGrupo) use ($tramoLaboral, $fechaCorte, $fechaCorteBono, $acumula) {
            $grupo = CuaGrupo::withTrashed()->find($codigoGrupo);
            if (!$grupo) {
                return [];
            }

            return collect([
                ...$this->filasSueldo($grupo, $tramoLaboral, $fechaCorte, $acumula),
                $this->filaBono($grupo, $tramoLaboral, $fechaCorteBono, $acumula),
                ...$this->filasAdicionales($grupo, $tramoLaboral, $fechaCorte, $acumula),
            ])->filter();
        })->values();
    }

    protected function filasSueldo(CuaGrupo $grupo, CuadTramoLaboral $tramoLaboral, string $fechaCorte, bool $acumula): array
    {
        $registrosDeEsteTramo = CuadRegistroDiario::where('codigo_grupo', $grupo->codigo)
            ->where('tramo_laboral_id', $tramoLaboral->id)
            ->with(['desgloseDetalle.desglose', 'actividadesBonos'])
            ->get();

        if ($grupo->modalidad_pago === 'mensual') {
            return $this->filasSueldoMensual($grupo, $tramoLaboral, $fechaCorte, $acumula, $registrosDeEsteTramo);
        }

        $deudaActual = $registrosDeEsteTramo->sum('total_pago_jornal');
        $fuenteRegistros = null;

        if ($acumula) {
            $registrosHistoricos = CuadRegistroDiario::where('codigo_grupo', $grupo->codigo)
                ->where('fecha', '<=', $fechaCorte)
                ->whereHas('tramoLaboral', fn($q) => $q->where('acumula_costos', true))
                ->with(['desgloseDetalle.desglose', 'actividadesBonos'])
                ->get();

            if ($registrosHistoricos->isEmpty() && $deudaActual <= 0) {
                return [];
            }
            $pendiente = $this->sumarPendienteComoDeCorte($registrosHistoricos, $fechaCorte, 'total_pago_jornal');
            $fuenteRegistros = $registrosHistoricos;
        } else {
            if ($registrosDeEsteTramo->isEmpty()) {
                return [];
            }
            $pendiente = $this->sumarPendienteComoDeCorte($registrosDeEsteTramo, $fechaCorte, 'total_pago_jornal');
            $fuenteRegistros = $registrosDeEsteTramo;
        }

        // Se envía $tramoLaboral para evaluar la ventana de fechas del tramo actual
        $ultimoPago = $this->ultimoPagoValidoEnTramo($fuenteRegistros, $tramoLaboral);
        $montoPagado = $this->sumarMontoPagadoEnTramo($fuenteRegistros, $tramoLaboral, 'total_pago_jornal');

        return [$this->fila($grupo, 'sueldo', $grupo->nombre, $deudaActual, $pendiente, $montoPagado, $ultimoPago)];
    }

    protected function filasSueldoMensual(CuaGrupo $grupo, CuadTramoLaboral $tramoLaboral, string $fechaCorte, bool $acumula, Collection $registrosDeEsteTramo): array
    {
        $actualPorMes = $registrosDeEsteTramo->groupBy(fn($r) => $r->fecha->format('Y-m'));

        $mesesConPendienteHistorico = collect();
        if ($acumula) {
            $mesesConPendienteHistorico = CuadRegistroDiario::where('codigo_grupo', $grupo->codigo)
                ->where('fecha', '<=', $fechaCorte)
                ->whereHas('tramoLaboral', fn($q) => $q->where('acumula_costos', true))
                ->with(['desgloseDetalle.desglose', 'actividadesBonos'])
                ->get()
                ->groupBy(fn($r) => $r->fecha->format('Y-m'))
                ->filter(fn($regs) => $this->sumarPendienteComoDeCorte($regs, $fechaCorte, 'total_pago_jornal') > 0)
                ->keys();
        }

        $todosLosMeses = $actualPorMes->keys()->merge($mesesConPendienteHistorico)->unique();

        return $todosLosMeses->map(function ($mesKey) use ($grupo, $tramoLaboral, $fechaCorte, $acumula, $actualPorMes) {
            $nombreMes = $this->meses[(int) Carbon::parse($mesKey . '-01')->format('n')];
            $registrosDeEsteTramoDelMes = $actualPorMes->get($mesKey, collect());

            $deudaActual = $registrosDeEsteTramoDelMes->sum('total_pago_jornal');
            $fuenteRegistros = null;

            if ($acumula) {
                [$anio, $mes] = explode('-', $mesKey);
                $registrosHistoricosDelMes = CuadRegistroDiario::where('codigo_grupo', $grupo->codigo)
                    ->whereYear('fecha', $anio)->whereMonth('fecha', $mes)
                    ->where('fecha', '<=', $fechaCorte)
                    ->whereHas('tramoLaboral', fn($q) => $q->where('acumula_costos', true))
                    ->with(['desgloseDetalle.desglose', 'actividadesBonos'])
                    ->get();
                $pendiente = $this->sumarPendienteComoDeCorte($registrosHistoricosDelMes, $fechaCorte, 'total_pago_jornal');
                $fuenteRegistros = $registrosHistoricosDelMes;
            } else {
                $pendiente = $this->sumarPendienteComoDeCorte($registrosDeEsteTramoDelMes, $fechaCorte, 'total_pago_jornal');
                $fuenteRegistros = $registrosDeEsteTramoDelMes;
            }

            $ultimoPago = $this->ultimoPagoValidoEnTramo($fuenteRegistros, $tramoLaboral);
            $montoPagado = $this->sumarMontoPagadoEnTramo($fuenteRegistros, $tramoLaboral, 'total_pago_jornal');

            return $this->fila($grupo, 'sueldo', "{$grupo->nombre} ({$nombreMes})", $deudaActual, $pendiente, $montoPagado, $ultimoPago);
        })->filter()->values()->all();
    }

    protected function filaBono(CuaGrupo $grupo, CuadTramoLaboral $tramoLaboral, string $fechaCorteBono, bool $acumula): ?array
    {
        $registrosDeEsteTramo = CuadActividadBono::where('se_paga_con_jornal', false)
            ->whereHas('registroDiario', fn($q) => $q->where('codigo_grupo', $grupo->codigo)->where('tramo_laboral_id', $tramoLaboral->id))
            ->with('desgloseDetalle.desglose')
            ->get();

        $deudaActual = $registrosDeEsteTramo->sum('total_bono');
        $fuenteRegistros = null;

        if ($acumula) {
            $registrosHistoricos = CuadActividadBono::where('se_paga_con_jornal', false)
                ->whereHas('registroDiario', fn($q) => $q->where('codigo_grupo', $grupo->codigo)
                    ->where('fecha', '<=', $fechaCorteBono)
                    ->whereHas('tramoLaboral', fn($q2) => $q2->where('acumula_costos', true)))
                ->with('desgloseDetalle.desglose')
                ->get();

            if ($registrosHistoricos->isEmpty() && $deudaActual <= 0) {
                return null;
            }
            $pendiente = $this->sumarPendienteComoDeCorte($registrosHistoricos, $fechaCorteBono, 'total_bono');
            $fuenteRegistros = $registrosHistoricos;
        } else {
            if ($registrosDeEsteTramo->isEmpty()) {
                return null;
            }
            $pendiente = $this->sumarPendienteComoDeCorte($registrosDeEsteTramo, $fechaCorteBono, 'total_bono');
            $fuenteRegistros = $registrosDeEsteTramo;
        }

        $ultimoPago = $this->ultimoPagoValidoEnTramo($fuenteRegistros, $tramoLaboral);
        $montoPagado = $this->sumarMontoPagadoEnTramo($fuenteRegistros, $tramoLaboral, 'total_bono');

        return $this->fila($grupo, 'bono', 'BONO ' . $grupo->nombre, $deudaActual, $pendiente, $montoPagado, $ultimoPago);
    }

    protected function filasAdicionales(CuaGrupo $grupo, CuadTramoLaboral $tramoLaboral, string $fechaCorte, bool $acumula): array
    {
        $gastosDeEsteTramo = GastoAdicionalPorGrupoCuadrilla::where('codigo_grupo', $grupo->codigo)
            ->where('cuad_tramo_laboral_id', $tramoLaboral->id)
            ->with('desgloseDetalle.desglose')
            ->get()
            ->groupBy('descripcion');

        $descripciones = $gastosDeEsteTramo->keys();
        $gastosHistoricos = collect();

        if ($acumula) {
            $gastosHistoricos = GastoAdicionalPorGrupoCuadrilla::where('codigo_grupo', $grupo->codigo)
                ->where('fecha_gasto', '<=', $fechaCorte)
                ->whereHas('tramoLaboral', fn($q) => $q->where('acumula_costos', true))
                ->with('desgloseDetalle.desglose')
                ->get()
                ->groupBy('descripcion');

            $descripciones = $descripciones->merge($gastosHistoricos->keys())->unique();
        }

        return $descripciones->map(function ($descripcion) use ($grupo, $tramoLaboral, $fechaCorte, $acumula, $gastosDeEsteTramo, $gastosHistoricos) {
            $delTramo = $gastosDeEsteTramo->get($descripcion, collect());
            $deudaActual = $delTramo->sum('monto');

            $fuentePendiente = $acumula ? $gastosHistoricos->get($descripcion, collect()) : $delTramo;
            $pendiente = $this->sumarPendienteComoDeCorte($fuentePendiente, $fechaCorte, 'monto');

            if ($deudaActual <= 0 && $pendiente <= 0) {
                return null;
            }

            $ultimoPago = $this->ultimoPagoValidoEnTramo($fuentePendiente, $tramoLaboral);
            $montoPagado = $this->sumarMontoPagadoEnTramo($fuentePendiente, $tramoLaboral, 'monto');

            return [
                'grupo_codigo' => $grupo->codigo,
                'color' => $grupo->color,
                'tipo' => 'adicional',
                'descripcion' => $descripcion,
                'descripcion_alias' => $descripcion . ' ' . $grupo->nombre,
                'condicion' => $pendiente > 0 ? 'Pendiente' : 'Pagado',
                'deuda_actual' => round($deudaActual, 2),
                'deuda_acumulada' => round($pendiente, 2),
                'monto_pagado' => round($montoPagado, 2),
                'fecha' => $ultimoPago?->desgloseDetalle?->desglose?->fecha,
                'recibo' => $ultimoPago?->desgloseDetalle?->nro_documento,
            ];
        })->filter()->values()->all();
    }

    protected function fila(CuaGrupo $grupo, string $tipo, string $descripcion, float $deudaActual, float $pendiente, float $montoPagado, $ultimoPago): ?array
    {
        if ($deudaActual <= 0 && $pendiente <= 0) {
            return null;
        }

        return [
            'grupo_codigo' => $grupo->codigo,
            'color' => $grupo->color,
            'tipo' => $tipo,
            'descripcion' => $descripcion,
            'condicion' => $pendiente > 0 ? 'Pendiente' : 'Pagado',
            'deuda_actual' => round($deudaActual, 2),
            'deuda_acumulada' => round($pendiente, 2),
            'monto_pagado' => round($montoPagado, 2),
            'fecha' => $ultimoPago?->desgloseDetalle?->desglose?->fecha,
            'recibo' => $ultimoPago?->desgloseDetalle?->nro_documento,
        ];
    }

    protected function sumarPendienteComoDeCorte(Collection $registros, string $fechaCorte, string $campoMonto): float
    {
        return $registros
            ->filter(function ($r) use ($fechaCorte) {
                $pagadoDefinitivo = $r->esta_pagado
                    && $r->desgloseDetalle?->desglose
                    && $r->desgloseDetalle->desglose->fecha->lte($fechaCorte);

                return !$pagadoDefinitivo;
            })
            ->sum($campoMonto);
    }

    /**
     * Suma los montos pagados de los registros/gastos cuyo DESGLOSE (momento del pago)
     * se efectuó dentro del rango de fechas del tramo laboral evaluado.
     */
    protected function sumarMontoPagadoEnTramo(?Collection $registros, CuadTramoLaboral $tramoLaboral, string $campoMonto): float
    {
        if (!$registros) {
            return 0.0;
        }

        return $registros
            ->filter(function ($r) use ($tramoLaboral) {
                if (!$r->esta_pagado || !$r->desgloseDetalle?->desglose?->fecha) {
                    return false;
                }

                $fechaDesglose = $r->desgloseDetalle->desglose->fecha;

                // Evalúa la FECHA EN QUE SE PAGÓ (Desglose), permitiendo liquidar 
                // deudas de 20 días atrás si el pago se realizó dentro de este tramo.
                return $fechaDesglose->gte($tramoLaboral->fecha_inicio)
                    && $fechaDesglose->lte($tramoLaboral->fecha_fin);
            })
            ->sum($campoMonto);
    }

    /**
     * Obtiene el comprobante o registro del último pago realizado en la ventana del tramo actual.
     */
    protected function ultimoPagoValidoEnTramo(?Collection $registros, CuadTramoLaboral $tramoLaboral)
    {
        if (!$registros) {
            return null;
        }

        return $registros
            ->filter(function ($r) use ($tramoLaboral) {
                if (!$r->esta_pagado || !$r->desgloseDetalle?->desglose?->fecha) {
                    return false;
                }

                $fechaDesglose = $r->desgloseDetalle->desglose->fecha;

                return $fechaDesglose->gte($tramoLaboral->fecha_inicio)
                    && $fechaDesglose->lte($tramoLaboral->fecha_fin);
            })
            ->sortByDesc(fn($r) => $r->desgloseDetalle->desglose->fecha)
            ->first();
    }
    protected function codigosConAlgoAunPendiente(string $fechaCorte, string $fechaCorteBono): Collection
    {
        $deSueldo = CuadRegistroDiario::where('fecha', '<=', $fechaCorte)
            ->whereHas('tramoLaboral', fn($q) => $q->where('acumula_costos', true))
            ->where(fn($q) => $q->where('esta_pagado', false)->orWhereHas('desgloseDetalle.desglose', fn($q2) => $q2->where('fecha', '>', $fechaCorte)))
            ->pluck('codigo_grupo');

        $deBono = CuadActividadBono::where('se_paga_con_jornal', false)
            ->whereHas('registroDiario', fn($q) => $q->where('fecha', '<=', $fechaCorteBono)->whereHas('tramoLaboral', fn($q2) => $q2->where('acumula_costos', true)))
            ->where(fn($q) => $q->where('esta_pagado', false)->orWhereHas('desgloseDetalle.desglose', fn($q2) => $q2->where('fecha', '>', $fechaCorteBono)))
            ->with('registroDiario')
            ->get()
            ->pluck('registroDiario.codigo_grupo');

        $deAdicionales = GastoAdicionalPorGrupoCuadrilla::where('fecha_gasto', '<=', $fechaCorte)
            ->whereHas('tramoLaboral', fn($q) => $q->where('acumula_costos', true))
            ->where(fn($q) => $q->where('esta_pagado', false)->orWhereHas('desgloseDetalle.desglose', fn($q2) => $q2->where('fecha', '>', $fechaCorte)))
            ->pluck('codigo_grupo');

        return $deSueldo->merge($deBono)->merge($deAdicionales)->unique()->filter();
    }
}