<?php

namespace App\Livewire\GestionCuadrilla;

use App\Models\CuadRegistroDiario;
use App\Models\CuadResumenPorTramo;
use App\Models\CuadTramoLaboralGrupo;
use App\Services\Cuadrilla\CuadrilleroServicio;
use App\Services\Cuadrilla\TramoLaboral\ResumenTramoServicio;
use App\Services\Cuadrilla\TramoLaboralServicio;
use App\Services\Handsontable\HSTCuadrillaReporteSemanalHoras;
use App\Support\DateHelper;
use Carbon\CarbonPeriod;
use DB;
use Exception;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class GestionCuadrillaReporteSemanalTramoComponent extends Component
{
    use LivewireAlert;
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
    public function renovarListaYResumir(){
        
        $this->obtenerReporteTramo();
        $this->procesarCalculoListadoResumen();
    }
    public function abrirPrecioPersonalizado($cuadrilleros)
    {
        try {
            //Buscar al menos un registro con cuadrillero_id siendo null
            $existeRegistroNuevo = collect($cuadrilleros)->some('cuadrillero_id', null);
            if ($existeRegistroNuevo) {
                throw new Exception("Solo seleccione cuadrilleros válidos");
            }

            $inicio = $this->tramoLaboral->fecha_inicio;
            $fin = $this->tramoLaboral->fecha_fin;

            $registros = CuadRegistroDiario::whereBetween('fecha', [$inicio, $fin])
                ->whereNotNull('costo_personalizado_dia')
                ->get(['cuadrillero_id', 'fecha', 'costo_personalizado_dia']);

            $diasSemana = [];

            // Inicializar las fechas de la semana vacías
            $periodo = CarbonPeriod::create($inicio, $fin);
            foreach ($periodo as $date) {
                $fechaStr = $date->toDateString();
                $diasSemana[] = $fechaStr;
            }

            $registroCuadrilla = [];

            foreach ($cuadrilleros as $cuadrilla) {

                $indiceCuadrilla = $cuadrilla['cuadrillero_id'];
                $registroCuadrilla[$indiceCuadrilla] = [
                    'cuadrillero_id' => $cuadrilla['cuadrillero_id'],
                    'cuadrillero_nombres' => $cuadrilla['nombres'],
                ];
                foreach ($periodo as $key => $date) {
                    $fechaStr = $date->toDateString();
                    $costoPersonalizado = $registros->first(function ($registro) use ($indiceCuadrilla, $fechaStr) {
                        return $registro->cuadrillero_id === $indiceCuadrilla && $registro->fecha->toDateString() === $fechaStr;
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
    public function registrarCostoPersonalizado()
    {
        try {
            DB::beginTransaction();

            foreach ($this->cuadrillerosCostosPersonalizados as $cuadrilla) {
                $cuadrilleroId = $cuadrilla['cuadrillero_id'];

                foreach ($cuadrilla['costos'] as $index => $costo) {
                    $fecha = $this->diasSemana[$index];

                    if (!is_null($costo)) {

                        CuadRegistroDiario::updateOrCreate(
                            [
                                'cuadrillero_id' => $cuadrilleroId,
                                'fecha' => $fecha,
                            ],
                            [
                                'costo_personalizado_dia' => $costo,
                            ]
                        );
                    }
                }
            }

            CuadrilleroServicio::calcularCostosCuadrilla($this->tramoLaboral->fecha_inicio, $this->tramoLaboral->fecha_fin);

            DB::commit();
            $this->obtenerReporteTramo();
            $this->alert('success', 'Costos personalizados actualizados correctamente');
            $this->mostrarFormularioCostoHora = false;

        } catch (\Throwable $th) {
            DB::rollBack();
            $this->alert('error', 'Error al guardar: ' . $th->getMessage());
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
            $this->guardarReporteSemanal($fechaInicio, $fechaFin, $datos,$this->resumenes,$tramoLaboralId);
            CuadrilleroServicio::calcularCostosCuadrilla($fechaInicio, $fechaFin);
            $this->obtenerReporteTramo();
            $this->procesarCalculoListadoResumen();
            $this->alert('success', 'Información actualizada');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public static function guardarReporteSemanal($inicio, $fin, $rows,$resumenes,$tramoLaboralId)
    {
        DB::beginTransaction();
        try {

            /*
            */
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
                    ->where('tramo_laboral_id',$tramoLaboralId)
                    ->whereBetween('fecha', [$inicioDate, $finDate])
                    ->pluck('cuadrillero_id')
                    ->unique();

                $cuadrillerosAEliminar = $cuadrilleroIdsActuales->diff($cuadrilleroIdsNuevos);
                if ($cuadrillerosAEliminar->isNotEmpty()) {
                    
                    CuadRegistroDiario::where('codigo_grupo', $codigoGrupo)
                        ->where('tramo_laboral_id',$tramoLaboralId)
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
    }
    #region Resumen por tramo
   
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
    public function procesarCalculoListadoResumen(){
        try {
            app(TramoLaboralServicio::class)->generarResumen($this->tramoLaboral->id,$this->fechaHastaBono);
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
    #endregion
    #region Reordenar Grupos
    public function abrirReordenarGruposForm()
    {
        try {
            $this->mostrarReordenarGrupoForm = true;
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
    #endregion
    public function render()
    {
        return view('livewire.gestion-cuadrilla.gestion-cuadrilla-reporte-semanal-tramo-component');
    }
}
