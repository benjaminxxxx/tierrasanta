<?php

namespace App\Services\Cuadrilla;

use App\Models\CuadCostoDiarioGrupo;
use App\Models\CuadTramoLaboral;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * Servicio para gestionar la lógica de negocio de los tramos laborales.
 */
class TramoLaboralServicio
{
    public function encontrarTramoPorId(int $id): ?CuadTramoLaboral
    {
        return CuadTramoLaboral::find($id);
    }
    /**
     * Encuentra el tramo laboral actual basándose en la sesión o la fecha de hoy.
     *
     * @return CuadTramoLaboral|null
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
     *
     * @param CuadTramoLaboral $tramo
     * @return CuadTramoLaboral|null
     */
    public function encontrarAnterior(CuadTramoLaboral $tramo): ?CuadTramoLaboral
    {
        return CuadTramoLaboral::whereDate('fecha_inicio', '<', $tramo->fecha_inicio)
            ->orderBy('fecha_inicio', 'desc')
            ->first();
    }

    /**
     * Encuentra el tramo inmediatamente siguiente a un tramo dado.
     *
     * @param CuadTramoLaboral $tramo
     * @return CuadTramoLaboral|null
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
     * @param array $datos Los datos para crear el tramo.
     * @return CuadTramoLaboral
     * @throws \Exception
     */
    public function crear(array $datos): CuadTramoLaboral
    {
        $this->validarFechas($datos['fecha_inicio']);

        return CuadTramoLaboral::create([
            'fecha_inicio' => $datos['fecha_inicio'],
            'fecha_fin' => $datos['fecha_fin'],
            'titulo' => $datos['titulo'],
            'acumula_costos' => $datos['acumula_costos'] ?? false
        ]);
    }

    /**
     * Actualiza un tramo laboral existente.
     *
     * @param int $tramoId El ID del tramo a actualizar.
     * @param array $datos Los datos para actualizar.
     * @return CuadTramoLaboral
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
            'acumula_costos' => $datos['acumula_costos'] ?? false
        ]);

        return $tramo;
    }

    /**
     * Elimina un tramo laboral.
     *
     * @param CuadTramoLaboral $tramo
     * @return void
     */
    public function eliminar(CuadTramoLaboral $tramo): void
    {
        DB::transaction(function () use ($tramo) {
            // 🔎 Obtener códigos de grupos del tramo
            $codigosGrupos = $tramo->gruposEnTramos()->pluck('codigo_grupo')->toArray();

            if (!empty($codigosGrupos)) {
                // 🗑️ Borrar costos diarios SOLO dentro del rango del tramo
                CuadCostoDiarioGrupo::whereIn('codigo_grupo', $codigosGrupos)
                    ->whereBetween('fecha', [$tramo->fecha_inicio, $tramo->fecha_fin])
                    ->delete();
            }

            // 🗑️ Borrar relaciones de grupos del tramo
            $tramo->gruposEnTramos()->delete();

            // 🗑️ Borrar el tramo en sí
            $tramo->delete();

            // 🔄 Limpiar sesión
            Session::forget('tramo_actual_id');
        });
    }

    /**
     * Genera un título descriptivo para un rango de fechas.
     *
     * @param Carbon $inicio
     * @param Carbon $fin
     * @return string
     */
    public function generarTitulo(Carbon $inicio, Carbon $fin): string
    {
        Carbon::setLocale('es');
        $mesInicio = mb_strtoupper($inicio->translatedFormat('F'), 'UTF-8');

        if ($inicio->equalTo($fin)) {
            return sprintf(
                "CUADRILLA MENSUAL DEL %s %s DE %s",
                mb_strtoupper($inicio->translatedFormat('l'), 'UTF-8'),
                $inicio->day,
                $mesInicio
            );
        }

        $mesFin = mb_strtoupper($fin->translatedFormat('F'), 'UTF-8');
        return sprintf(
            "CUADRILLA MENSUAL DEL %s DE %s AL %s DE %s",
            $inicio->day,
            $mesInicio,
            $fin->day,
            $mesFin
        );
    }

    /**
     * Valida que no exista otro tramo con la misma fecha de inicio.
     *
     * @param string $fechaInicio
     * @param int|null $idIgnorado
     * @return void
     * @throws \Exception
     */
    private function validarFechas(string $fechaInicio, int $idIgnorado = null): void
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
     *
     * @param Carbon $fecha
     * @return CuadTramoLaboral|null
     */
    private function encontrarAnteriorAFecha(Carbon $fecha): ?CuadTramoLaboral
    {
        return CuadTramoLaboral::whereDate('fecha_inicio', '<=', $fecha)
            ->orderBy('fecha_inicio', 'desc')
            ->first();
    }

    /**
     * Encuentra el primer tramo cuya fecha de inicio es posterior a la fecha dada.
     *
     * @param Carbon $fecha
     * @return CuadTramoLaboral|null
     */
    private function encontrarSiguienteAFecha(Carbon $fecha): ?CuadTramoLaboral
    {
        return CuadTramoLaboral::whereDate('fecha_inicio', '>', $fecha)
            ->orderBy('fecha_inicio', 'asc')
            ->first();
    }
}
