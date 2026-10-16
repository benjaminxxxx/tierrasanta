<?php

namespace App\Services\Cuadrilla;

use App\Models\CuadActividadBono;
use App\Models\CuadCostoDiarioGrupo;
use App\Models\CuadRegistroDiario;
use App\Models\CuadTramoLaboral;
use App\Models\CuadTramoLaboralCuadrillero;
use App\Models\CuadTramoLaboralGrupo;
use App\Models\GastoAdicionalPorGrupoCuadrilla;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * Servicio para gestionar la lógica de negocio de los tramos laborales.
 */
class TramoLaboralServicio
{
    /** Obtiene la lista ordenada por grupos y luego por trabajadores de un tramo laboral dado.
     *
     * @param int $tramoSeleccionadoId El ID del tramo laboral seleccionado.
     * @return \Illuminate\Database\Eloquent\Collection La colección de grupos con sus cuadrilleros.
     * @throws \Exception Si el tramo laboral no se encuentra.
     */
    public static function obtenerListaOficial(int $tramoSeleccionadoId)
    {
        $tramoLaboral = CuadTramoLaboral::find($tramoSeleccionadoId);
        if (!$tramoLaboral) {
            throw new \Exception("Tramo laboral no encontrado.");
        }
        return $tramoLaboral
            ->gruposEnTramos()
            ->with([
                'grupo',
                'cuadrilleros' => fn($query) => $query->orderBy('orden'),
                'cuadrilleros.cuadrillero'
            ])
            ->orderBy('orden')
            ->get();
    }
    public static function tramosEnFecha(string $fecha)
    {
        $fechaCarbon = Carbon::parse($fecha);

        return CuadTramoLaboral::whereDate('fecha_inicio', '<=', $fechaCarbon)
            ->whereDate('fecha_fin', '>=', $fechaCarbon)
            ->orderBy('fecha_inicio', 'asc')
            ->get();
    }
    public function encontrarTramoPorId(int $id): ?CuadTramoLaboral
    {
        return CuadTramoLaboral::find($id);
    }

    public function encontrarTramoPorMesAnioLista($mes, $anio)
    {
        // Definimos el inicio y fin del mes objetivo
        $inicioMes = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
        $finMes = $inicioMes->copy()->endOfMonth();

        return CuadTramoLaboral::where(function ($query) use ($inicioMes, $finMes) {
            /* Lógica de intersección:
               Para que un rango (A) se solape con un rango (B):
               A_inicio <= B_fin  Y  A_fin >= B_inicio
            */
            $query->whereDate('fecha_inicio', '<=', $finMes)
                ->whereDate('fecha_fin', '>=', $inicioMes);
        })
            ->orderBy('fecha_inicio', 'asc')
            ->get(); // Retorna la lista (Collection)
    }
    /**
     * Encuentra el tramo laboral actual basándose en la sesión o la fecha de hoy.
     */
    public function encontrarActual(): ?CuadTramoLaboral
    {
        $hoy = Carbon::today();
        $tramoActual = null;

        // 1. Intentar cargar desde la sesión si existe un ID válido.
        $sessionId = Session::get('tramo_actual_id');
        if ($sessionId) {
            $tramoActual = CuadTramoLaboral::find($sessionId);
        }

        // 2. Si no se encontró por sesión, calcularlo.
        if (!$tramoActual) {
            // Buscar un tramo que contenga la fecha de hoy.
            $tramoActual = CuadTramoLaboral::whereDate('fecha_inicio', '<=', $hoy)
                ->whereDate('fecha_fin', '>=', $hoy)
                ->orderBy('fecha_inicio', 'desc')
                ->first();

            // Si no hay tramo que contenga hoy, buscar el más cercano.
            if (!$tramoActual) {
                $previo = $this->encontrarAnteriorAFecha($hoy);
                $siguiente = $this->encontrarSiguienteAFecha($hoy);
                $tramoActual = $previo ?: $siguiente; // Prioriza el anterior, si no, el siguiente.
            }
        }

        // 3. Actualizar la sesión con el ID encontrado o limpiarla si no hay tramo.
        if ($tramoActual) {
            Session::put('tramo_actual_id', $tramoActual->id);
        } else {
            Session::forget('tramo_actual_id');
        }

        return $tramoActual;
    }

    /**
     * Encuentra el tramo inmediatamente anterior a un tramo dado.
     */
    public function encontrarAnterior(CuadTramoLaboral $tramo): ?CuadTramoLaboral
    {
        return CuadTramoLaboral::whereDate('fecha_inicio', '<', $tramo->fecha_inicio)
            ->orderBy('fecha_inicio', 'desc')
            ->first();
    }


    /**
     * Encuentra el tramo inmediatamente siguiente a un tramo dado.
     */
    public function encontrarSiguiente(CuadTramoLaboral $tramo): ?CuadTramoLaboral
    {
        return CuadTramoLaboral::whereDate('fecha_inicio', '>', $tramo->fecha_inicio)
            ->orderBy('fecha_inicio', 'asc')
            ->first();
    }

    /**
     * Crea un nuevo tramo laboral.
     *
     * @param  array  $datos  Los datos para crear el tramo.
     *
     * @throws \Exception
     */
    public function crear(array $datos): CuadTramoLaboral
    {
        $this->validarFechas($datos['fecha_inicio']);

        return CuadTramoLaboral::create([
            'fecha_inicio' => $datos['fecha_inicio'],
            'fecha_fin' => $datos['fecha_fin'],
            'titulo' => $datos['titulo'],
            'acumula_costos' => $datos['acumula_costos'] ?? false,
        ]);
    }

    /**
     * Actualiza un tramo laboral existente.
     *
     * @param  int  $tramoId  El ID del tramo a actualizar.
     * @param  array  $datos  Los datos para actualizar.
     *
     * @throws \Exception
     */
    public function actualizar(int $tramoId, array $datos): CuadTramoLaboral
    {
        $tramo = CuadTramoLaboral::findOrFail($tramoId);
        $this->validarFechas($datos['fecha_inicio'], $tramoId);

        $tramo->update([
            'fecha_inicio' => $datos['fecha_inicio'],
            'fecha_fin' => $datos['fecha_fin'],
            'titulo' => $datos['titulo'],
            'acumula_costos' => $datos['acumula_costos'] ?? false,
        ]);

        return $tramo;
    }

    /*
     * Borrados seguros. Lo pagado (en un desglose) no se borra: la base ya no borra en cascada los registros diarios
     * ni los gastos de un tramo o de un cuadrillero del tramo (migración 2026_10_10_100000), así que todo borrado
     * pasa por aquí: se comprueba que no haya nada pagado y se borra explícitamente lo que no lo está.
     */

    /** Registros diarios pagados (jornal o bono que se paga aparte) entre los dados. */
    public static function registrosPagados($registros)
    {
        $ids = $registros->pluck('id');
        $bonosPagados = CuadActividadBono::whereIn('registro_diario_id', $ids)
            ->where(fn($q) => $q->where('esta_pagado', true)->orWhereNotNull('desglose_detalle_id'))
            ->pluck('registro_diario_id');
        return $registros->filter(fn($r) => $r->esta_pagado || $r->desglose_detalle_id || $bonosPagados->contains($r->id));
    }

    /**
     * Quita cuadrilleros de un grupo del tramo y borra sus registros de ese tramo (horas, detalle, bonos). No deja
     * quitar a quien ya tiene días o bonos pagados.
     *
     * @param int[] $cuadrilleroIds
     */
    public function quitarCuadrillerosDelGrupo(CuadTramoLaboralGrupo $grupo, array $cuadrilleroIds): int
    {
        if (!$cuadrilleroIds) {
            return 0;
        }
        return DB::transaction(function () use ($grupo, $cuadrilleroIds) {
            $tramoCuadrilleros = CuadTramoLaboralCuadrillero::where('cuad_tramo_laboral_grupo_id', $grupo->id)
                ->whereIn('cuadrillero_id', $cuadrilleroIds)->get();
            $registros = CuadRegistroDiario::whereIn('tramo_cuadrillero_id', $tramoCuadrilleros->pluck('id'))->get();
            $pagados = self::registrosPagados($registros);
            if ($pagados->isNotEmpty()) {
                $nombres = $tramoCuadrilleros->whereIn('id', $pagados->pluck('tramo_cuadrillero_id'))->pluck('nombres')->unique()->implode(', ');
                throw new \Exception("No se puede quitar a {$nombres} del grupo {$grupo->codigo_grupo}: tiene(n) {$pagados->count()} día(s) ya pagado(s) en un desglose. "
                    . 'Si es un error, primero quite el pago en el desglose.');
            }
            // Detalle de horas, bonos y producción se van en cascada con cada registro (no tienen nada pagado)
            CuadRegistroDiario::whereIn('id', $registros->pluck('id'))->delete();
            return CuadTramoLaboralCuadrillero::whereIn('id', $tramoCuadrilleros->pluck('id'))->delete();
        });
    }

    /** Lo que se perdería al eliminar el tramo (para avisar antes). */
    public function resumenParaEliminar(CuadTramoLaboral $tramo): array
    {
        $registros = CuadRegistroDiario::where('tramo_laboral_id', $tramo->id)->get(['id', 'esta_pagado', 'desglose_detalle_id', 'total_horas']);
        $gastos = GastoAdicionalPorGrupoCuadrilla::where('cuad_tramo_laboral_id', $tramo->id)->get(['id', 'esta_pagado', 'desglose_detalle_id']);
        return [
            'registros' => $registros->count(),
            'horas' => (float) $registros->sum('total_horas'),
            'gastos' => $gastos->count(),
            'pagados' => self::registrosPagados($registros)->count() + $gastos->filter(fn($g) => $g->esta_pagado || $g->desglose_detalle_id)->count(),
        ];
    }

    /**
     * Elimina un tramo laboral con sus grupos, cuadrilleros, registros diarios, gastos y costos del rango. No se
     * puede si algo del tramo ya está pagado en un desglose (se perdería lo pagado).
     */
    public function eliminar(CuadTramoLaboral $tramo): void
    {
        $resumen = $this->resumenParaEliminar($tramo);
        if ($resumen['pagados'] > 0) {
            throw new \Exception("No se puede eliminar el tramo: tiene {$resumen['pagados']} registro(s) o gasto(s) ya pagados en un desglose. "
                . 'Eliminarlo borraría lo pagado. Si es un error, primero quite esos pagos del desglose.');
        }

        DB::transaction(function () use ($tramo) {
            $codigosGrupos = $tramo->gruposEnTramos()->pluck('codigo_grupo')->toArray();
            if (!empty($codigosGrupos)) {
                // Costos diarios SOLO dentro del rango del tramo
                CuadCostoDiarioGrupo::whereIn('codigo_grupo', $codigosGrupos)
                    ->whereBetween('fecha', [$tramo->fecha_inicio, $tramo->fecha_fin])
                    ->delete();
            }
            // Lo registrado (sin nada pagado): se borra explícitamente, la base ya no lo hace en cascada
            CuadRegistroDiario::where('tramo_laboral_id', $tramo->id)->delete();
            GastoAdicionalPorGrupoCuadrilla::where('cuad_tramo_laboral_id', $tramo->id)->delete();
            $tramo->gruposEnTramos()->delete();
            $tramo->delete();
            Session::forget('tramo_actual_id');
        });
    }

    /**
     * Valida que no exista otro tramo con la misma fecha de inicio.
     *
     * @throws \Exception
     */
    private function validarFechas(string $fechaInicio, ?int $idIgnorado = null): void
    {
        $query = CuadTramoLaboral::where('fecha_inicio', $fechaInicio);

        if ($idIgnorado) {
            $query->where('id', '!=', $idIgnorado);
        }

        if ($query->exists()) {
            throw new \Exception('Ya existe un tramo con la misma fecha de inicio.');
        }
    }

    /**
     * Encuentra el último tramo cuya fecha de inicio es anterior o igual a la fecha dada.
     */
    private function encontrarAnteriorAFecha(Carbon $fecha): ?CuadTramoLaboral
    {
        return CuadTramoLaboral::whereDate('fecha_inicio', '<=', $fecha)
            ->orderBy('fecha_inicio', 'desc')
            ->first();
    }

    /**
     * Encuentra el primer tramo cuya fecha de inicio es posterior a la fecha dada.
     */
    private function encontrarSiguienteAFecha(Carbon $fecha): ?CuadTramoLaboral
    {
        return CuadTramoLaboral::whereDate('fecha_inicio', '>', $fecha)
            ->orderBy('fecha_inicio', 'asc')
            ->first();
    }
}
