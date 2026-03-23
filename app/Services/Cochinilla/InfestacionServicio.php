<?php

namespace App\Services\Cochinilla;

use App\Models\Campo;
use App\Models\CochinillaInfestacion;
use App\Models\CochinillaIngreso;
use App\Services\AuditoriaServicio;
use DB;
use Illuminate\Support\Carbon;

class InfestacionServicio
{
    /**
     * Sincroniza campo_campania_id para infestaciones del mes/año indicado
     * que aún no tienen campaña asignada (campo_campania_id IS NULL).
     *
     * Se usa para corregir registros existentes que no pasaron por el trigger
     * porque sus datos no cambiaron durante el guardado masivo.
     */
    public static function sincronizarCampaniasPorMes(int $mes, int $anio): int
    {
        $inicio = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
        $fin = $inicio->copy()->endOfMonth();

        // Traer solo las huérfanas del período
        $infestaciones = CochinillaInfestacion::whereNull('campo_campania_id')
            ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->get(['id', 'campo_nombre', 'fecha']);

        if ($infestaciones->isEmpty()) {
            return 0;
        }

        // Agrupar por campo para reducir queries: 1 consulta por campo único
        $porCampo = $infestaciones->groupBy('campo_nombre');

        $actualizados = 0;

        DB::transaction(function () use ($porCampo, &$actualizados) {
            foreach ($porCampo as $campoNombre => $registros) {

                // Traer todas las campañas del campo de una sola vez
                $campanias = DB::table('campos_campanias')
                    ->where('campo', $campoNombre)
                    ->orderByDesc('fecha_inicio')
                    ->get(['id', 'fecha_inicio', 'fecha_fin']);

                foreach ($registros as $infestacion) {
                    $fecha = Carbon::parse($infestacion->fecha);

                    // Buscar la campaña cuyo rango cubre la fecha
                    $campania = $campanias->first(function ($c) use ($fecha) {
                        $inicio = Carbon::parse($c->fecha_inicio);
                        $fin = $c->fecha_fin ? Carbon::parse($c->fecha_fin) : null;

                        return $fecha->gte($inicio) && ($fin === null || $fecha->lte($fin));
                    });

                    if ($campania) {
                        CochinillaInfestacion::where('id', $infestacion->id)
                            ->update(['campo_campania_id' => $campania->id]);
                        $actualizados++;
                    }
                }
            }
        });

        return $actualizados;
    }
    public static function guardarInfestacionMasivo(array $filas): array
    {
        $resultados = ['creados' => 0, 'actualizados' => 0, 'eliminados' => 0, 'errores' => []];

        DB::transaction(function () use ($filas, &$resultados) {
            foreach ($filas as $fila) {

                $id = $fila['id'] ?? null;

                $camposRequeridos = [
                    'tipo_infestacion' => 'Tipo de infestación',
                    'fecha' => 'Fecha',
                    'campo_nombre' => 'Campo',
                    //'area' => 'Área',
                    'kg_madres' => 'KG Madres',
                    'campo_origen_nombre' => 'Origen campo',
                    'metodo' => 'Método',
                    'capacidad_envase' => 'Capacidad envase',
                ];

                $todosNulos = collect($camposRequeridos)
                    ->keys()
                    ->every(fn($campo) => is_null($fila[$campo] ?? null) || ($fila[$campo] ?? '') === '')
                    && ($fila['numero_envases'] ?? 0) == 0;

                if ($todosNulos) {
                    // Fila completamente vacía
                    if ($id) {
                        self::eliminarInfestacion($id);
                        $resultados['eliminados']++;
                    }
                    // Sin id y vacía → ignorar
                    continue;
                }

                // Validar campos requeridos
                foreach ($camposRequeridos as $campo => $etiqueta) {
                    if (is_null($fila[$campo] ?? null) || ($fila[$campo] ?? '') === '') {
                        throw new \Exception("El campo \"{$etiqueta}\" es obligatorio." . ($id ? " (registro ID: {$id})" : ''));
                    }
                }

                if (($fila['numero_envases'] ?? 0) <= 0) {
                    throw new \Exception("El campo \"Envases\" debe ser mayor a cero." . ($id ? " (registro ID: {$id})" : ''));
                }

                $areaEntrante = $fila['area'] ?? null;
                $areaFinal = (is_null($areaEntrante) || $areaEntrante === '') ? null : $areaEntrante;

                if (is_null($areaFinal) && !empty($fila['campo_nombre'])) {
                    // Intentar obtener area desde la BD
                    $areaFinal = Campo::where('nombre', $fila['campo_nombre'])->value('area');
                }

                if (is_null($areaFinal)) {
                    throw new \Exception(
                        "No se pudo determinar el área para el campo \"{$fila['campo_nombre']}\"."
                        . ($id ? " (registro ID: {$id})" : '')
                        . " Ingrésala manualmente."
                    );
                }

                $fila['area'] = $areaFinal;


                self::guardarInfestacion($fila, [], $id);
                $id ? $resultados['actualizados']++ : $resultados['creados']++;
            }
        });

        return $resultados;
    }

    public static function eliminarInfestacion(int $id): void
    {
        $infestacion = CochinillaInfestacion::with('ingresos')->findOrFail($id);
        $snapshot = $infestacion->withoutRelations()->toArray();

        // Revertir stock si tiene ingresos asociados
        foreach ($infestacion->ingresos as $ingreso) {
            $ingreso->stock_disponible += $ingreso->pivot->kg_asignados;
            $ingreso->save();
        }

        $infestacion->ingresos()->detach();
        $infestacion->delete();

        AuditoriaServicio::registrar(
            modelo: CochinillaInfestacion::class,
            modeloId: $id,
            accion: 'eliminar',
            antes: $snapshot,
            observacion: 'Registro eliminado en guardado masivo',
        );
    }
    public static function guardarInfestacion(array $datosInfestacion, array $ingresosRelacionados, ?int $infestacionId = null): int
    {
        return DB::transaction(function () use ($datosInfestacion, $ingresosRelacionados, $infestacionId) {

            $usuarioId = auth()->id();

            if ($infestacionId) {
                $infestacion = CochinillaInfestacion::with('ingresos')->findOrFail($infestacionId);
                $antesData = $infestacion->withoutRelations()->toArray();
                $datosInfestacion['editado_por'] = $usuarioId;
                $infestacion->update($datosInfestacion);

                // ✅ Revertir stock de ingresos previamente asignados
                foreach ($infestacion->ingresos as $ingresoAnterior) {
                    $ingresoAnterior->stock_disponible += $ingresoAnterior->pivot->kg_asignados;
                    $ingresoAnterior->save();
                }

                // ✅ Detach después de restaurar stock
                $infestacion->ingresos()->detach();
                
                AuditoriaServicio::registrar(
                    modelo: CochinillaInfestacion::class,
                    modeloId: $infestacion->id,
                    accion: 'editar',
                    antes: $antesData,
                    despues: $infestacion->fresh()->withoutRelations()->toArray(),
                    camposIgnorados: ['creado_por', 'editado_por', 'campo_campania_id', 'updated_at', 'created_at'],
                );
            } else {
                $datosInfestacion['creado_por'] = $usuarioId;
                $infestacion = CochinillaInfestacion::create($datosInfestacion);

                AuditoriaServicio::registrar(
                    modelo: CochinillaInfestacion::class,
                    modeloId: $infestacion->id,
                    accion: 'crear',
                    despues: $infestacion->toArray(),
                );
            }

            // ✅ Vincular ingresos nuevos y descontar stock
            foreach ($ingresosRelacionados as $ingresoId => $kg) {
                if ($kg > 0) {
                    $ingreso = CochinillaIngreso::findOrFail($ingresoId);

                    $stockActual = $ingreso->stock_disponible ?? $ingreso->total_kilos;
                    if ($kg > $stockActual) {
                        throw new \Exception("El ingreso {$ingreso->id} no tiene suficiente stock disponible.");
                    }

                    $ingreso->stock_disponible = $stockActual - $kg;
                    $ingreso->save();

                    $infestacion->ingresos()->attach($ingreso->id, [
                        'kg_asignados' => $kg,
                    ]);
                }
            }

            return $infestacion->id;
        });
    }

    public static function ultimasInfestaciones(array $filtro)
    {
        $query = CochinillaInfestacion::with([
            'campoCampania'
        ]);

        // Filtro por fecha de ingreso con tolerancia
        $fechaReferencia = isset($filtro['fecha']) ? Carbon::parse($filtro['fecha']) : now();
        $toleranciaDias = $filtro['tolerancia'] ?? 7;

        $query->whereDate('fecha', '<=', $fechaReferencia)
            ->whereDate('fecha', '>=', $fechaReferencia->copy()->subDays($toleranciaDias));

        return $query->orderBy('fecha', 'desc');
    }
}
