<?php

// app/Services/Cuadrilla/GastoAdicionalServicio.php
namespace App\Services\Cuadrilla;

use App\Models\CuaGrupo;
use App\Models\CuadTramoLaboral;
use App\Models\GastoAdicionalPorGrupoCuadrilla;
use App\Support\FormatoHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

class GastoAdicionalServicio
{
    /**
     * Devuelve los gastos de un tramo listos para mostrar en la tabla,
     * con flag de fuera de rango y permisos por usuario.
     */
    public function listarPorTramo(CuadTramoLaboral $tramo): Collection
    {
        $usuario = Auth::user();

        return GastoAdicionalPorGrupoCuadrilla::where('cuad_tramo_laboral_id', $tramo->id)
            ->with(['grupo', 'creadoPor'])
            ->orderBy('codigo_grupo')
            ->orderBy('fecha_gasto')
            ->get()
            ->map(function (GastoAdicionalPorGrupoCuadrilla $gasto) use ($tramo, $usuario) {
                $inicio = Carbon::parse($tramo->fecha_inicio)->startOfDay();
                $fin    = Carbon::parse($tramo->fecha_fin)->endOfDay();
                $fecha  = Carbon::parse($gasto->fecha_gasto);

                return [
                    'id'                => $gasto->id,
                    'grupo'             => $gasto->grupo?->nombre ?? $gasto->codigo_grupo,
                    'codigo_grupo'      => $gasto->codigo_grupo,
                    'descripcion'       => $gasto->descripcion,
                    'fecha'             => $fecha->toDateString(),
                    'fecha_formateada'  => $fecha->format('d/m/Y'),
                    'monto'             => (float) $gasto->monto,
                    'estado'            => $gasto->estado,
                    'fuera_de_rango'    => !$fecha->between($inicio, $fin),
                    'puede_editar'      => $usuario ? $gasto->estaEditablePor($usuario) : false,
                    'puede_eliminar'    => $usuario ? $gasto->estaEliminablePor($usuario) : false,
                    'creado_por_nombre' => $gasto->creadoPor?->name ?? 'Sistema',
                ];
            });
    }

    /**
     * Crea un único gasto nuevo validando rango de fechas y normalizando descripción.
     */
    public function crear(CuadTramoLaboral $tramo, array $datos): GastoAdicionalPorGrupoCuadrilla
    {
        $grupo = CuaGrupo::where('nombre', $datos['grupo'])->firstOrFail();

        $fecha = FormatoHelper::parseFecha($datos['fecha']);
        $this->validarFechaEnRango($fecha, $tramo);

        return GastoAdicionalPorGrupoCuadrilla::create([
            'monto'                 => $datos['monto'],
            'descripcion'           => mb_strtoupper(trim($datos['descripcion'])),  // normalizar
            'anio_contable'         => Carbon::parse($fecha)->year,
            'mes_contable'          => Carbon::parse($fecha)->month,
            'fecha_gasto'           => $fecha,
            'cuad_tramo_laboral_id' => $tramo->id,
            'codigo_grupo'          => $grupo->codigo,
            'estado'                => 'pendiente',
            'creado_por'            => Auth::id(),
        ]);
    }

    /**
     * Edita un gasto existente (solo si es editable por el usuario actual).
     */
    public function editar(GastoAdicionalPorGrupoCuadrilla $gasto, CuadTramoLaboral $tramo, array $datos): void
    {
        $usuario = Auth::user();

        if (!$gasto->estaEditablePor($usuario)) {
            throw new \RuntimeException('No tiene permisos para editar este gasto o ya fue aprobado.');
        }

        $grupo = CuaGrupo::where('nombre', $datos['grupo'])->firstOrFail();
        $fecha = FormatoHelper::parseFecha($datos['fecha']);
        $this->validarFechaEnRango($fecha, $tramo);

        $gasto->update([
            'monto'        => $datos['monto'],
            'descripcion'  => strtoupper(trim($datos['descripcion'])),
            'anio_contable'=> Carbon::parse($fecha)->year,
            'mes_contable' => Carbon::parse($fecha)->month,
            'fecha_gasto'  => $fecha,
            'codigo_grupo' => $grupo->codigo,
        ]);
    }

    /**
     * Elimina un gasto (solo si es eliminable por el usuario actual).
     */
    public function eliminar(GastoAdicionalPorGrupoCuadrilla $gasto): void
    {
        $usuario = Auth::user();

        if (!$gasto->estaEliminablePor($usuario)) {
            throw new \RuntimeException('No se puede eliminar: el gasto ya fue aprobado.');
        }

        $gasto->delete();
    }

    /**
     * Aprueba todos los gastos pendientes de un tramo (acción de supervisor).
     */
    public function aprobarTodos(CuadTramoLaboral $tramo): int
    {
        $aprobados = GastoAdicionalPorGrupoCuadrilla::where('cuad_tramo_laboral_id', $tramo->id)
            ->whereIn('estado', ['pendiente', 'en_correccion'])
            ->update([
                'estado'      => 'aprobado',
                'aprobado_por'=> Auth::id(),
                'aprobado_en' => now(),
            ]);

        return $aprobados;
    }

    /**
     * Habilita un gasto aprobado para corrección (acción de supervisor).
     */
    public function habilitarParaCorreccion(GastoAdicionalPorGrupoCuadrilla $gasto): void
    {
        if ($gasto->estado !== 'aprobado') {
            throw new \RuntimeException('Solo se pueden habilitar para corrección los gastos aprobados.');
        }

        $gasto->update([
            'estado'        => 'en_correccion',
            'habilitado_por'=> Auth::id(),
            'habilitado_en' => now(),
        ]);
    }

    // ── Privados ──────────────────────────────────────────────────

    private function validarFechaEnRango(string $fecha, CuadTramoLaboral $tramo): void
    {
        $f      = Carbon::parse($fecha);
        $inicio = Carbon::parse($tramo->fecha_inicio)->startOfDay();
        $fin    = Carbon::parse($tramo->fecha_fin)->endOfDay();

        if (!$f->between($inicio, $fin)) {
            throw new \InvalidArgumentException(
                "La fecha {$f->format('d/m/Y')} está fuera del rango del tramo " .
                "({$inicio->format('d/m/Y')} – {$fin->format('d/m/Y')})."
            );
        }
    }
}