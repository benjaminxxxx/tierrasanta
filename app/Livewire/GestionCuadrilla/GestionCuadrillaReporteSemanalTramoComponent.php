<?php

namespace App\Livewire\GestionCuadrilla;

use App\Models\CuadRegistroDiario;
use App\Services\Cuadrilla\CuadrilleroServicio;
use App\Services\Cuadrilla\TramoLaboralServicio;
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
    protected $listeners = [
        'cuadrillerosAgregadosEnTramo' => 'obtenerReporteTramo',
        'costosSemanalesModificados' => 'obtenerReporteTramo'
    ];
    public function mount($tramoId)
    {
        $this->tramoLaboral = app(TramoLaboralServicio::class)->encontrarTramoPorId($tramoId);
        $this->totalDias = DateHelper::calcularTotalDias($this->tramoLaboral->fecha_inicio, $this->tramoLaboral->fecha_fin);
        $this->obtenerReporteTramo(false);
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
        $this->handsontableData = [];

        $fechaInicio = Carbon::parse($this->tramoLaboral->fecha_inicio)->startOfDay();
        $fechaFin = Carbon::parse($this->tramoLaboral->fecha_fin)->endOfDay();

        // ✅ Traer todos los registros diarios de este tramo (incluyendo grupo)
        $registros = CuadRegistroDiario::whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->get()
            ->groupBy(function ($item) {
                return $item->cuadrillero_id . '|' . $item->codigo_grupo;
            });

        // Rango de días
        $dias = collect();
        for ($d = $fechaInicio->copy(); $d->lte($fechaFin); $d->addDay()) {
            $dias->push($d->copy());
        }

        $gruposEnTramos = $this->tramoLaboral
            ->gruposEnTramos()
            ->with(['grupo', 'cuadrilleros', 'cuadrilleros.cuadrillero'])
            ->orderBy('orden')
            ->get();

        foreach ($gruposEnTramos as $grupoEnTramos) {
            $grupo = $grupoEnTramos->grupo;
            $cuadrilleros = $grupoEnTramos->cuadrilleros()
                ->with(['cuadrillero'])
                ->orderBy('orden')
                ->get();

            // Header de grupo
            $this->handsontableData[] = [
                'header' => true,
                'nombres' => $grupo->nombre,
                'color' => $grupo->color,
            ];

            foreach ($cuadrilleros as $cuadrillero) {
                $fila = [
                    'cuadrillero_id' => $cuadrillero->cuadrillero_id,
                    'codigo_grupo' => $grupo->codigo,
                    'header' => false,
                    'nombres' => $cuadrillero->cuadrillero->nombres,
                    'color' => $grupo->color,
                ];

                $clave = $cuadrillero->cuadrillero_id . '|' . $grupo->codigo;

                $registrosDelCuadrillero = $registros[$clave] ?? collect();

                $totalCosto = 0;
                $totalBono = 0;

                foreach ($dias as $index => $dia) {
                    $fechaStr = $dia->toDateString();

                    $registro = $registrosDelCuadrillero->first(function ($item) use ($fechaStr) {
                        return $item->fecha instanceof \Carbon\Carbon
                            ? $item->fecha->toDateString() === $fechaStr
                            : (string) $item->fecha === $fechaStr;
                    });
                    $horas = ($registro && $registro->total_horas > 0) ? $registro->total_horas : '-';
                    $jornal = ($registro && $registro->costo_dia > 0) ? $registro->costo_dia : '-';
                    $bono = ($registro && $registro->total_bono > 0) ? $registro->total_bono : '-';

                    $fila["dia_" . ($index + 1)] = $horas;
                    $fila["jornal_" . ($index + 1)] = $jornal;
                    $fila["bono_" . ($index + 1)] = $bono;

                    $totalCosto += ($jornal !== '-' ? (float) $jornal : 0);
                    $totalBono += ($bono !== '-' ? (float) $bono : 0);
                }

                $fila['total_costo'] = $totalCosto + $totalBono;
                $fila['total_bono'] = $totalBono;

                $this->handsontableData[] = $fila;
            }
        }

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
            $this->guardarReporteSemanal($fechaInicio, $fechaFin, $datos);
            CuadrilleroServicio::calcularCostosCuadrilla($fechaInicio, $fechaFin);
            $this->obtenerReporteTramo();
            $this->alert('success', 'Información actualizada');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public static function guardarReporteSemanal($inicio, $fin, $rows)
    {
        DB::beginTransaction();
        try {
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
                    ->whereBetween('fecha', [$inicioDate, $finDate])
                    ->pluck('cuadrillero_id')
                    ->unique();

                $cuadrillerosAEliminar = $cuadrilleroIdsActuales->diff($cuadrilleroIdsNuevos);
                if ($cuadrillerosAEliminar->isNotEmpty()) {
                    CuadRegistroDiario::where('codigo_grupo', $codigoGrupo)
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
                        ];

                        if (is_null($total_horas) || $total_horas <= 0) {
                            // ❌ No debe existir registro cuando no hay horas
                            CuadRegistroDiario::where($where)->delete();
                            continue;
                        }

                        // ✅ Upsert cuando hay horas > 0
                        CuadRegistroDiario::updateOrCreate(
                            $where,
                            [
                                'asistencia' => true,
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


    public function render()
    {
        return view('livewire.gestion-cuadrilla.gestion-cuadrilla-reporte-semanal-tramo-component');
    }
}
