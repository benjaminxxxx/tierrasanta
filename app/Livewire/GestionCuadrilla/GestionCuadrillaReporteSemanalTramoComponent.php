<?php

namespace App\Livewire\GestionCuadrilla;

use App\Constants\Permisos;
use App\Models\CuadRegistroDiario;
use App\Models\CuadResumenPorTramo;
use App\Models\Cuadrillero;
use App\Models\CuadTramoLaboralCuadrillero;
use App\Models\CuadTramoLaboralGrupo;
use App\Procesos\Cuadrillas\ReemplazarCuadrillero;
use App\Services\Cuadrilla\CuadrilleroServicio;
use App\Services\Cuadrilla\RegistroDiarioServicio;
use App\Services\Cuadrilla\TramoLaboral\ResumenTramoServicio;
use App\Services\Cuadrilla\TramoLaboralServicio;
use App\Services\Handsontable\HSTCuadrillaReporteSemanalHoras;
use App\Support\DateHelper;
use App\Traits\HandlesAlerts;
use Carbon\CarbonPeriod;
use DB;
use Exception;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class GestionCuadrillaReporteSemanalTramoComponent extends Component
{
    use LivewireAlert;
    use HandlesAlerts;
    public $tramoLaboral;
    public $totalDias = 0;
    public $handsontableData = [];
    //percios personalizados por cuadrillero
    public $diasSemana = [];
    public $mostrarFormularioCostoHora = false;
    public $cuadrillerosCostosPersonalizados = [];
    #region ordenar grupos

    public $mostrarReordenarGrupoForm = false;
    public $listaGrupos = [];
    #endregion
    #region resumenes
    public $resumenes = [];
    #endregion
    public $fechaHastaBono;
    public $mostrarReemplazarCuadrilleroForm = false;
    public array $cuadrilleros = [];
    public $cuadrilleroARemplazarSeleccionado;
    public $cuadrilleroPorReemplazar;
    protected $listeners = [
        'cuadrillerosAgregadosEnTramo' => 'renovarListaYResumir',
        'costosSemanalesModificados' => 'renovarListaYResumir'
    ];
    public function mount($tramoId)
    {


        $this->tramoLaboral = app(TramoLaboralServicio::class)->encontrarTramoPorId($tramoId);
        if ($this->tramoLaboral) {
            $this->fechaHastaBono = $this->tramoLaboral->fecha_hasta_bono;
        }
        $this->totalDias = DateHelper::calcularTotalDias($this->tramoLaboral->fecha_inicio, $this->tramoLaboral->fecha_fin);
        $this->obtenerReporteTramo(false);
        $this->listarResumenes();

    }
    public function getCuadrillero($search)
    {
        $query = Cuadrillero::orderBy('nombres');

        // Si hay búsqueda, filtrar
        if ($search) {
            $query->where('nombres', 'like', "%{$search}%");
        }

        return $query
            ->limit(10)
            ->get(['id', 'nombres'])
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->nombres
            ])
            ->toArray();
    }
    public function reemplazarCuadrillero($cuadrilleroId)
    {
        $this->cuadrilleroARemplazarSeleccionado = null;
        $this->cuadrilleroPorReemplazar = Cuadrillero::find($cuadrilleroId);
        $this->mostrarReemplazarCuadrilleroForm = true;
    }
    public function confirmarReemplazo()
    {
        try {
            app(ReemplazarCuadrillero::class)->ejecutar(
                tramoLaboralId: $this->tramoLaboral->id,
                anteriorId: $this->cuadrilleroPorReemplazar->id,
                nuevoId: (int) $this->cuadrilleroARemplazarSeleccionado,
            );
            $this->obtenerReporteTramo();
            $this->mostrarReemplazarCuadrilleroForm = false;
            $this->alert('success', 'Reemplazo de cuadrillero exitoso');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function renovarListaYResumir()
    {

        $this->obtenerReporteTramo();
        $this->procesarCalculoListadoResumen();
    }
    public function abrirPrecioPersonalizado($cuadrilleros)
    {
        try {
            // Buscar al menos un registro con cuadrillero_id siendo null
            $existeRegistroNuevo = collect($cuadrilleros)->some('cuadrillero_id', null);
            if ($existeRegistroNuevo) {
                throw new Exception("Solo seleccione cuadrilleros válidos");
            }

            $inicio = $this->tramoLaboral->fecha_inicio;
            $fin = $this->tramoLaboral->fecha_fin;
            $tramoLaboralId = $this->tramoLaboral->id;

            // 1. Incluir 'codigo_grupo' en la consulta SQL y acotar por 'tramo_laboral_id'
            $registros = CuadRegistroDiario::where('tramo_laboral_id', $tramoLaboralId)
                ->whereBetween('fecha', [$inicio, $fin])
                ->whereNotNull('costo_personalizado_dia')
                ->get(['cuadrillero_id', 'codigo_grupo', 'fecha', 'costo_personalizado_dia']);

            $diasSemana = [];

            // Inicializar las fechas de la semana vacías
            $periodo = CarbonPeriod::create($inicio, $fin);
            foreach ($periodo as $date) {
                $diasSemana[] = $date->toDateString();
            }

            $registroCuadrilla = [];

            foreach ($cuadrilleros as $cuadrilla) {

                $cuadrilleroId = $cuadrilla['cuadrillero_id'];
                $codigoGrupo = trim($cuadrilla['codigo_grupo'] ?? '');

                // 2. Usar una clave compuesta (grupo + cuadrillero) para evitar sobreescribir el arreglo
                $indiceCuadrilla = $codigoGrupo . '_' . $cuadrilleroId;

                $registroCuadrilla[$indiceCuadrilla] = [
                    'cuadrillero_id' => $cuadrilleroId,
                    'cuadrillero_nombres' => $cuadrilla['nombres'],
                    'grupo_codigo' => $codigoGrupo,
                    'costos' => []
                ];

                foreach ($periodo as $key => $date) {
                    $fechaStr = $date->toDateString();

                    // 3. Evaluar coincidencia exacta por cuadrillero_id, codigo_grupo y fecha
                    $costoPersonalizado = $registros->first(function ($registro) use ($cuadrilleroId, $codigoGrupo, $fechaStr) {
                        return $registro->cuadrillero_id === $cuadrilleroId
                            && $registro->codigo_grupo === $codigoGrupo
                            && $registro->fecha->toDateString() === $fechaStr;
                    });

                    $registroCuadrilla[$indiceCuadrilla]['costos'][$key] = $costoPersonalizado?->costo_personalizado_dia;
                }
            }
            $this->diasSemana = $diasSemana;
            $this->cuadrillerosCostosPersonalizados = $registroCuadrilla;
            $this->mostrarFormularioCostoHora = true;

        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    /*
    public function registrarCostoPersonalizado(RegistroDiarioServicio $servicio)
    {
        try {
            $tramoLaboralId = $this->tramoLaboral->id;
            DB::transaction(function () use ($servicio, $tramoLaboralId) {
                foreach ($this->cuadrillerosCostosPersonalizados as $cuadrilla) {

                    foreach ($cuadrilla['costos'] as $index => $costo) {
                        $servicio->asignarCostoPersonalizado(
                            $cuadrilla['cuadrillero_id'],
                            $this->diasSemana[$index],
                            $costo,
                            $tramoLaboralId
                        );
                    }
                }
            });

            $this->obtenerReporteTramo();
            $this->mostrarFormularioCostoHora = false;
            $this->alert('success', 'Costos actualizados correctamente');

        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage(), [

                    'position' => 'center',
                    'toast' => false,
                    'timer' => null,
                ]);
        }
    }
        */
    public function registrarCostoPersonalizado(RegistroDiarioServicio $servicio)
    {
        try {
            $tramoLaboralId = $this->tramoLaboral->id;

            DB::transaction(function () use ($servicio, $tramoLaboralId) {
                foreach ($this->cuadrillerosCostosPersonalizados as $cuadrilla) {

                    $cuadrilleroId = $cuadrilla['cuadrillero_id'];
                    $codigoGrupo = $cuadrilla['grupo_codigo']; // 👈 Extraer el grupo específico

                    foreach ($cuadrilla['costos'] as $index => $costo) {
                        $fecha = $this->diasSemana[$index] ?? null;

                        if (!$fecha) {
                            continue;
                        }

                        $servicio->asignarCostoPersonalizado(
                            $cuadrilleroId,
                            $codigoGrupo, // 👈 Se envía el grupo correcto
                            $fecha,
                            $costo,
                            $tramoLaboralId
                        );
                    }
                }
            });

            $this->obtenerReporteTramo();
            $this->mostrarFormularioCostoHora = false;
            $this->alert('success', 'Costos actualizados correctamente');

        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }
    public function obtenerReporteTramo($dispatched = true)
    {
        if (!$this->tramoLaboral)
            return;

        $generator = new HSTCuadrillaReporteSemanalHoras($this->tramoLaboral);

        $this->handsontableData = $generator->generate();
        $this->listaGrupos = $generator->getGroupList();

        if ($dispatched) {
            $this->dispatch('recargarTablaTramos', $this->handsontableData);
        }
    }

    public function storeTableDataGuardarHoras($datos)
    {
        try {
            if (!$this->tramoLaboral) {
                throw new Exception("Recargar la página");
            }
            $fechaInicio = $this->tramoLaboral->fecha_inicio;
            $fechaFin = $this->tramoLaboral->fecha_fin;
            $tramoLaboralId = $this->tramoLaboral->id;

            $this->guardarReporteSemanal($fechaInicio, $fechaFin, $datos, $this->resumenes, $tramoLaboralId);
            CuadrilleroServicio::registrarTotalesEnResumenDiarioPlanilla($fechaInicio, $fechaFin);

            $this->obtenerReporteTramo();
            $this->procesarCalculoListadoResumen();
            $this->alert('success', 'Información actualizada');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage(), [
                'position' => 'center',
                'toast' => false,
                'timer' => null,
            ]);
        }
    }
    /*
    public static function guardarReporteSemanal($inicio, $fin, $rows, $resumenes, $tramoLaboralId)
    {
        DB::beginTransaction();
        try {

            if (!auth()->user()->can(Permisos::CUADRILLA_SEMANAL_GESTIONAR_HORAS)) {
                throw new Exception("No tiene permisos para editar el reporte semanal");
            }
            foreach ($resumenes as $id => $resumenData) {
                // Solo enviar fecha y recibo
                $payload = [
                    'fecha' => $resumenData['fecha'] ?? null,
                    'recibo' => $resumenData['recibo'] ?? null,
                ];

                ResumenTramoServicio::actualizar($id, $payload);
            }

            $inicioDate = Carbon::parse($inicio)->startOfDay();
            $finDate = Carbon::parse($fin)->endOfDay();

            // Rango de fechas día por día
            $dias = collect();
            for ($d = $inicioDate->copy(); $d->lte($finDate); $d->addDay()) {
                $dias->push($d->copy());
            }

            // 1) Agrupar filas por grupo
            $grupos = collect($rows)->groupBy(function ($fila) {
                return trim($fila['codigo_grupo'] ?? '');
            })->filter(function ($_, $codigoGrupo) {
                return $codigoGrupo !== '';
            });

            foreach ($grupos as $codigoGrupo => $filasGrupo) {
                // 2) Diferencial de cuadrilleros por grupo (altas/bajas)
                $cuadrilleroIdsNuevos = $filasGrupo->pluck('cuadrillero_id')->filter()->unique();

                $cuadrilleroIdsActuales = CuadRegistroDiario::where('codigo_grupo', $codigoGrupo)
                    ->where('tramo_laboral_id', $tramoLaboralId)
                    ->whereBetween('fecha', [$inicioDate, $finDate])
                    ->pluck('cuadrillero_id')
                    ->unique();

                $cuadrillerosAEliminar = $cuadrilleroIdsActuales->diff($cuadrilleroIdsNuevos);
                if ($cuadrillerosAEliminar->isNotEmpty()) {

                    CuadRegistroDiario::where('codigo_grupo', $codigoGrupo)
                        ->where('tramo_laboral_id', $tramoLaboralId)
                        ->whereBetween('fecha', [$inicioDate, $finDate])
                        ->whereIn('cuadrillero_id', $cuadrillerosAEliminar)
                        ->delete();
                }

                // 3) Procesar filas del grupo (insert/update/delete por día)
                foreach ($filasGrupo as $fila) {
                    $cuadrilleroId = $fila['cuadrillero_id'] ?? null;
                    if (!$cuadrilleroId) {
                        continue;
                    }
                    if ($fila['nombres'] == 'JUAN ARMAS') {
                        //dd($fila);
                    }
                    foreach ($dias as $index => $d) {
                        $fechaStr = $d->toDateString();
                        $keyDia = 'dia_' . ($index + 1);
                        $valorBruto = $fila[$keyDia] ?? null;

                        // Normalizar horas: null/'' => null, numérico => float
                        $total_horas = (is_null($valorBruto) || $valorBruto === '')
                            ? null
                            : floatval($valorBruto);

                        $where = [
                            'cuadrillero_id' => $cuadrilleroId,
                            'fecha' => $fechaStr,
                            'codigo_grupo' => $codigoGrupo,
                            'tramo_laboral_id' => $tramoLaboralId
                        ];

                        if (is_null($total_horas) || $total_horas <= 0) {
                            // No debe existir registro cuando no hay horas

                            CuadRegistroDiario::where($where)->delete();
                            continue;
                        }
                        // Upsert cuando hay horas > 0
                        CuadRegistroDiario::updateOrCreate(
                            $where,
                            [
                                'tramo_laboral_id' => $tramoLaboralId,
                                'total_horas' => $total_horas,
                                'costo_dia' => 0,
                            ]
                        );
                    }
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }*/
    public static function guardarReporteSemanal($inicio, $fin, $rows, $resumenes, $tramoLaboralId)
    {
        DB::beginTransaction();
        try {

            if (!auth()->user()->can(Permisos::CUADRILLA_SEMANAL_GESTIONAR_HORAS)) {
                throw new Exception("No tiene permisos para editar el reporte semanal");
            }

            foreach ($resumenes as $id => $resumenData) {
                // Solo enviar fecha y recibo
                $payload = [
                    'fecha' => $resumenData['fecha'] ?? null,
                    'recibo' => $resumenData['recibo'] ?? null,
                ];

                ResumenTramoServicio::actualizar($id, $payload);
            }

            $inicioDate = Carbon::parse($inicio)->startOfDay();
            $finDate = Carbon::parse($fin)->endOfDay();

            // Rango de fechas día por día
            $dias = collect();
            for ($d = $inicioDate->copy(); $d->lte($finDate); $d->addDay()) {
                $dias->push($d->copy());
            }

            // 🚀 PRECARGAR MAPPING: Obtener los tramo_cuadrillero_id mapeados por (codigo_grupo + '|' + cuadrillero_id)
            $relacionesCuadrilleros = DB::table('cuad_tramo_cuadrilleros as tc')
                ->join('cuad_tramo_grupos as tg', 'tc.cuad_tramo_laboral_grupo_id', '=', 'tg.id')
                ->where('tg.cuad_tramo_laboral_id', $tramoLaboralId)
                ->select('tc.id as tramo_cuadrillero_id', 'tc.cuadrillero_id', 'tg.codigo_grupo')
                ->get()
                ->keyBy(fn($item) => $item->codigo_grupo . '|' . $item->cuadrillero_id);

            // Mapa auxiliar cuadrillero_id -> nombres, para mensajes de error legibles
            // en el diferencial de bajas (ahí no tenemos la fila completa, solo el id)
            $nombresPorCuadrilleroId = collect($rows)
                ->filter(fn($fila) => !empty($fila['cuadrillero_id']))
                ->keyBy('cuadrillero_id')
                ->map(fn($fila) => $fila['nombres'] ?? "ID {$fila['cuadrillero_id']}");

            // 1) Agrupar filas por grupo
            $grupos = collect($rows)->groupBy(function ($fila) {
                return trim($fila['codigo_grupo'] ?? '');
            })->filter(function ($_, $codigoGrupo) {
                return $codigoGrupo !== '';
            });

            foreach ($grupos as $codigoGrupo => $filasGrupo) {
                // 2) Diferencial de cuadrilleros por grupo (altas/bajas)
                $cuadrilleroIdsNuevos = $filasGrupo->pluck('cuadrillero_id')->filter()->unique();

                $cuadrilleroIdsActuales = CuadRegistroDiario::where('codigo_grupo', $codigoGrupo)
                    ->where('tramo_laboral_id', $tramoLaboralId)
                    ->whereBetween('fecha', [$inicioDate, $finDate])
                    ->pluck('cuadrillero_id')
                    ->unique();

                $cuadrillerosAEliminar = $cuadrilleroIdsActuales->diff($cuadrilleroIdsNuevos);
                if ($cuadrillerosAEliminar->isNotEmpty()) {

                    // 🚨 Verificar que ninguno de los registros a borrar en bloque tenga bono calculado
                    $registroConBono = CuadRegistroDiario::where('codigo_grupo', $codigoGrupo)
                        ->where('tramo_laboral_id', $tramoLaboralId)
                        ->whereBetween('fecha', [$inicioDate, $finDate])
                        ->whereIn('cuadrillero_id', $cuadrillerosAEliminar)
                        ->where('total_bono', '>', 0)
                        ->first();

                    if ($registroConBono) {
                        $nombre = $nombresPorCuadrilleroId->get($registroConBono->cuadrillero_id)
                            ?? "Cuadrillero ID {$registroConBono->cuadrillero_id}";

                        throw new Exception(
                            "No se puede quitar a {$nombre} del grupo {$codigoGrupo}: " .
                            "tiene un bono de {$registroConBono->total_bono} calculado el " .
                            "{$registroConBono->fecha->toDateString()}. " .
                            "Retire o ajuste el bono desde el módulo de bonificaciones antes de eliminarlo del grupo."
                        );
                    }

                    CuadRegistroDiario::where('codigo_grupo', $codigoGrupo)
                        ->where('tramo_laboral_id', $tramoLaboralId)
                        ->whereBetween('fecha', [$inicioDate, $finDate])
                        ->whereIn('cuadrillero_id', $cuadrillerosAEliminar)
                        ->delete();
                }

                // 3) Procesar filas del grupo (insert/update/delete por día)
                foreach ($filasGrupo as $fila) {
                    $cuadrilleroId = $fila['cuadrillero_id'] ?? null;
                    if (!$cuadrilleroId) {
                        continue;
                    }

                    // 🚀 BUSCAR EL ID OBLIGATORIO DE LA RELACIÓN
                    $keyRelacion = $codigoGrupo . '|' . $cuadrilleroId;
                    $tramoCuadrillero = $relacionesCuadrilleros->get($keyRelacion);

                    if (!$tramoCuadrillero) {
                        throw new Exception("El cuadrillero ID {$cuadrilleroId} no pertenece al grupo {$codigoGrupo} en este tramo.");
                    }

                    $tramoCuadrilleroId = $tramoCuadrillero->tramo_cuadrillero_id;

                    foreach ($dias as $index => $d) {
                        $fechaStr = $d->toDateString();
                        $keyDia = 'dia_' . ($index + 1);
                        $valorBruto = $fila[$keyDia] ?? null;

                        // Normalizar horas: null/'' => null, numérico => float
                        $total_horas = (is_null($valorBruto) || $valorBruto === '')
                            ? null
                            : floatval($valorBruto);

                        $where = [
                            'cuadrillero_id' => $cuadrilleroId,
                            'fecha' => $fechaStr,
                            'codigo_grupo' => $codigoGrupo,
                            'tramo_laboral_id' => $tramoLaboralId
                        ];

                        if (is_null($total_horas) || $total_horas <= 0) {
                            // No debe existir registro cuando no hay horas...
                            // salvo que ya tenga un bono calculado: en ese caso, no se borra a ciegas
                            $registroExistente = CuadRegistroDiario::where($where)->first();

                            if ($registroExistente && $registroExistente->total_bono > 0) {
                                throw new Exception(
                                    "No se puede quitar la hora de {$fila['nombres']} el {$fechaStr}: " .
                                    "tiene un bono de {$registroExistente->total_bono} ya calculado en ese día. " .
                                    "Retire o ajuste el bono desde el módulo de bonificaciones antes de eliminar el registro."
                                );
                            }

                            CuadRegistroDiario::where($where)->delete();
                            continue;
                        }

                        // Upsert cuando hay horas > 0 (Incluye el tramo_cuadrillero_id obligatorio)
                        CuadRegistroDiario::updateOrCreate(
                            $where,
                            [
                                'tramo_cuadrillero_id' => $tramoCuadrilleroId, // 👈 Se agrega la FK NOT NULL
                                'tramo_laboral_id' => $tramoLaboralId,
                                'total_horas' => $total_horas,
                                'costo_dia' => 0,
                            ]
                        );
                    }
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function cambiarEstadoResumen($resumenId)
    {
        try {

            app(ResumenTramoServicio::class)->cambiarCondicion($resumenId);
            $this->listarResumenes();
            $this->alert('success', 'Estado actualizado correctamente.');

        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function listarResumenes()
    {
        try {
            if (!$this->tramoLaboral) {
                return;
            }
            $this->resumenes = CuadResumenPorTramo::where('tramo_id', $this->tramoLaboral->id)
                ->orderBy('orden')
                ->get()
                ->keyBy('id')
                ->toArray();

        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function procesarCalculoListadoResumen()
    {
        try {
            app(TramoLaboralServicio::class)->generarResumen($this->tramoLaboral->id, $this->fechaHastaBono);
            $this->listarResumenes();
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
    }
    public function recalcularResumenTramo()
    {
        try {
            $this->procesarCalculoListadoResumen();
            $this->alert('success', 'Resumen actualizado correctamente.');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function abrirReordenarGruposForm()
    {
        try {
            $this->mostrarReordenarGrupoForm = true;
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function eliminarGrupo($codigo_grupo)
    {
        $grupo = CuadTramoLaboralGrupo::where('cuad_tramo_laboral_id', $this->tramoLaboral->id)
            ->where('codigo_grupo', $codigo_grupo)
            ->first();

        if (!$grupo) {
            $this->alert('error', 'El grupo ya no existe o fue eliminado previamente.');
            $this->obtenerReporteTramo(); // por si la tabla quedó desincronizada
            return;
        }

        $tieneCuadrilleros = CuadTramoLaboralCuadrillero::where('cuad_tramo_laboral_grupo_id', $grupo->id)
            ->exists();

        if ($tieneCuadrilleros) {
            $this->alert('error', 'No se puede eliminar el grupo porque tiene cuadrilleros asignados.');
            return;
        }

        $grupo->delete();

        $this->obtenerReporteTramo();
        $this->alert('success', 'Grupo eliminado correctamente');
    }
    public function eliminarCuadrillero($cuadrillero_id, $codigo_grupo)
    {
        try {
            $grupo = CuadTramoLaboralGrupo::where('cuad_tramo_laboral_id', $this->tramoLaboral->id)
                ->where('codigo_grupo', $codigo_grupo)
                ->first();

            if (!$grupo) {
                $this->alert('error', 'El grupo no existe o fue eliminado.');
                $this->obtenerReporteTramo();
                return;
            }

            $eliminado = CuadTramoLaboralCuadrillero::where('cuad_tramo_laboral_grupo_id', $grupo->id)
                ->where('cuadrillero_id', $cuadrillero_id)
                ->delete();

            if (!$eliminado) {
                $this->alert('error', 'El cuadrillero ya no estaba en este grupo.');
            } else {
                $this->alert('success', 'Cuadrillero eliminado del grupo correctamente');
            }

            $this->obtenerReporteTramo();
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function registrarOrdenGrupal()
    {
        foreach ($this->listaGrupos as $index => $grupo) {
            CuadTramoLaboralGrupo::updateOrInsert(
                ['codigo_grupo' => $grupo['codigo'], 'cuad_tramo_laboral_id' => $this->tramoLaboral->id],
                ['orden' => $index + 1]
            );
        }

        $this->mostrarReordenarGrupoForm = false;
        $this->obtenerReporteTramo();
        $this->alert('success', 'Orden actualizado correctamente');
    }
    public function render()
    {
        return view('livewire.gestion-cuadrilla.gestion-cuadrilla-reporte-semanal-tramo-component');
    }
}
