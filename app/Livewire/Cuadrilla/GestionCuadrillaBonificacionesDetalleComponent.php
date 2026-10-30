<?php

namespace App\Livewire\Cuadrilla;
use App\Models\Actividad;
use App\Models\CuadRegistroDiario;
use App\Models\PlanRegistroDiario;
use App\Services\Cuadrilla\GuardarBonificacionProceso;
use App\Support\DateHelper;
use Exception;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class GestionCuadrillaBonificacionesDetalleComponent extends Component
{
    use LivewireAlert, \App\Traits\HandlesAlerts;
    public $actividad;
    public $metodos = [];
    public $estandarProduccion = 0;
    public $unidades = 'kg.';
    public $recojos = 1;
    public $tableDataBonificados = [];
    /** @var array<string, array<int, float>> cantidades con bono por trabajador ("c:ID" / "p:ID") y recojo */
    private array $cantidades = [];
    public function mount($actividadSeleccionada)
    {
        $this->actividad = Actividad::find($actividadSeleccionada);


        if ($this->actividad) {
            $this->recojos = $this->actividad->recojos;
            $this->metodos = $this->actividad->metodos()->with(['tramos'])->get()->toArray();
            // La unidad es la de la labor (catálogo de unidades de producción), no se escribe aquí
            $this->unidades = \App\Services\Campo\Produccion\CampoProduccionReglas::unidadDeLabor($this->actividad->codigo_labor, $this->actividad->fecha) ?? '';
        }

        //los trabajadores deben estar despues de los recojos para obtener la informacion completa de recojos
        $this->obtenerTrabajadores();

    }
    public function obtenerTrabajadores()
    {
        try {
            $campoNombre = $this->actividad->campo;
            $codigoLabor = $this->actividad->codigo_labor;
            $fecha = $this->actividad->fecha;

            // Carga de registros usando tus métodos de consulta
            $registros = $this->obtenerCuadrillasPorFechaYLabor($campoNombre, $codigoLabor, $fecha, $this->actividad->id);
            $registrosPlanilla = $this->obtenerPlanillasPorFechaYLabor($campoNombre, $codigoLabor, $fecha, $this->actividad->id);

            $dataHandsontable = [];
            // Cantidades con bono por trabajador (registro de producción de campo)
            $this->cantidades = \App\Services\Campo\Produccion\CampoProduccionConsulta::conBonoPorTrabajador($this->actividad->id);

            // 1. Procesar Cuadrillas
            foreach ($registros as $registro) {
                $dataHandsontable[] = $this->mapearFilaTrabajador(
                    $registro,
                    'CUADRILLA',
                    optional($registro->cuadrillero)->nombres ?? '-',
                    $registro->detalleHoras,
                    $campoNombre,
                    $codigoLabor
                );
            }

            // 2. Procesar Planillas
            foreach ($registrosPlanilla as $registroPlanilla) {
                $dataHandsontable[] = $this->mapearFilaTrabajador(
                    $registroPlanilla,
                    'PLANILLA',
                    $registroPlanilla->detalleMensual->nombres ?? '-',
                    $registroPlanilla->detalles,
                    $campoNombre,
                    $codigoLabor
                );
            }

            // 3. Acumulación para Totales
            $countCuadrilla = count($registros);
            $countPlanilla = count($registrosPlanilla);

            $totalesCuadrilla = $this->inicializarAcumuladoresTotales();
            $totalesPlanilla = $this->inicializarAcumuladoresTotales();

            foreach ($dataHandsontable as $row) {
                if ($row['tipo'] === 'CUADRILLA') {
                    $refTotales = &$totalesCuadrilla;
                } else {
                    $refTotales = &$totalesPlanilla;
                }
                
                $refTotales['total_bono'] += (float) $row['total_bono'];
                $refTotales['total_horas'] += (float) $row['total_horas'];

                for ($i = 1; $i <= $this->recojos; $i++) {
                    $refTotales['produccion_' . $i] += (float) ($row['produccion_' . $i] ?? 0);
                }

                // Romper la referencia al finalizar la iteración para evitar efectos secundarios
                unset($refTotales);
            }

            // 4. Construcción de Filas de Totales
            $rowTotalCuadrilla = $this->crearFilaTotal(
                "{$countCuadrilla} cuadrilleros",
                $campoNombre,
                $codigoLabor,
                $totalesCuadrilla
            );

            $rowTotalPlanilla = $this->crearFilaTotal(
                "{$countPlanilla} planilleros",
                $campoNombre,
                $codigoLabor,
                $totalesPlanilla
            );

            // Gran Total (Suma de ambos)
            $totalesGranTotal = [
                'total_bono' => $totalesCuadrilla['total_bono'] + $totalesPlanilla['total_bono'],
                'total_horas' => $totalesCuadrilla['total_horas'] + $totalesPlanilla['total_horas'],
            ];
            for ($i = 1; $i <= $this->recojos; $i++) {
                $totalesGranTotal['produccion_' . $i] = $totalesCuadrilla['produccion_' . $i] + $totalesPlanilla['produccion_' . $i];
            }

            // Filas SIN BONO (producción sin trabajador o de personas sin bono): entran al total de producción
            $filasSinBono = $this->filasSinBono($campoNombre, $codigoLabor);
            foreach ($filasSinBono as $fila) {
                for ($i = 1; $i <= $this->recojos; $i++) {
                    $totalesGranTotal['produccion_' . $i] += (float) ($fila['produccion_' . $i] ?: 0);
                }
            }
            $haySinBono = collect($filasSinBono)->contains(fn($f) => collect(range(1, $this->recojos))->contains(fn($i) => (float) ($f['produccion_' . $i] ?: 0) != 0));

            $rowGranTotal = $this->crearFilaTotal(
                ($countCuadrilla + $countPlanilla) . ' trabajadores' . ($haySinBono ? ' + sin bono' : '') . ' (TOTAL)',
                $campoNombre,
                $codigoLabor,
                $totalesGranTotal
            );

            // Anexar filas de totales a la tabla final
            array_push($dataHandsontable, ...$filasSinBono);
            $dataHandsontable[] = $rowTotalCuadrilla;
            $dataHandsontable[] = $rowTotalPlanilla;
            $dataHandsontable[] = $rowGranTotal;

            $this->tableDataBonificados = $dataHandsontable;

        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    /**
     * Método auxiliar para transformar cada registro individual
     */
    private function mapearFilaTrabajador($registro, string $tipo, string $nombreTrabajador, $detallesHoras, string $campoNombre, string $codigoLabor): array
    {
        $totalBonoCalculado = $registro->actividadesBonos->sum('total_bono');
        $metodo_bonificacion = optional(optional($registro->actividadesBonos->first())->metodo)->titulo;

        $row = [
            'registro_diario_id' => $registro->id,
            'tipo' => $tipo,
            'metodo_bonificacion' => $metodo_bonificacion,
            'cuadrillero_id' => $registro->cuadrillero_id ?? null,
            'nombre_trabajador' => $nombreTrabajador,
            'campo' => $campoNombre,
            'labor' => $codigoLabor,
            'total_bono' => $totalBonoCalculado,
            'bono_manual' => (bool) optional($registro->actividadesBonos->first())->bono_manual,
            // Jornal del día para el destajo que respeta el jornal. Solo cuadrilla: planilla tiene sueldo fijo y su bono es aparte
            'jornal_dia' => $tipo === 'CUADRILLA' ? (float) ($registro->costo_personalizado_dia ?: $registro->jornal_aplicado) : null,
        ];

        // Cantidades por recojo del trabajador (registro de producción de campo)
        $clave = $tipo === 'CUADRILLA'
            ? \App\Services\Campo\Produccion\CampoProduccionConsulta::clave($registro->cuadrillero_id, null)
            : \App\Services\Campo\Produccion\CampoProduccionConsulta::clave(null, $registro->detalleMensual?->plan_empleado_id);
        $producciones = $this->cantidades[$clave] ?? [];

        for ($i = 1; $i <= $this->recojos; $i++) {
            $row['produccion_' . $i] = $producciones[$i] ?? '';
        }

        // Mapeo de tramos de horas usando Carbon
        $horariosConcatenados = [];
        foreach ($detallesHoras as $detalle) {
            $inicio = \Carbon\Carbon::parse($detalle->hora_inicio)->format('H:i');
            $fin = \Carbon\Carbon::parse($detalle->hora_fin)->format('H:i');
            $horariosConcatenados[] = "$inicio-$fin";
        }

        $row['horarios'] = implode(',', $horariosConcatenados);

        // Uso directo de tus métodos estáticos auxiliares
        $row['rango_total_horas'] = DateHelper::calcularDuracionPorTramo($row['horarios']);
        $row['total_horas'] = DateHelper::calcularTotalHorasFloat($row['rango_total_horas']);

        return $row;
    }

    /** Filas en blanco SIN BONO que siempre se ofrecen al final de la tabla (Flavia: "3 o 4 líneas en blanco"). */
    private const FILAS_SIN_BONO = 4;

    /**
     * Filas SIN BONO de la tabla: las ya guardadas (producción de campo sin bono) y filas en blanco hasta tener al menos
     * FILAS_SIN_BONO (mínimo 2 en blanco). Sin trabajador, la columna Trabajador es la observación (editable).
     */
    private function filasSinBono(string $campoNombre, string $codigoLabor): array
    {
        $filas = [];
        foreach (\App\Services\Campo\Produccion\CampoProduccionConsulta::sinBono($this->actividad->id) as $f) {
            $clave = $f['tipo'] ? ($f['tipo'] === 'cuadrilla' ? 'c:' : 'p:') . $f['trabajador_id'] : null;
            $filas[] = $this->filaSinBono($campoNombre, $codigoLabor, $clave,
                $clave ? "{$f['nombre']} (sin bono)" : ($f['observacion'] ?: 'Sin bono'), $f['observacion'], $f['recojos']);
        }
        $blancas = max(2, self::FILAS_SIN_BONO - count($filas));
        for ($i = 0; $i < $blancas; $i++) {
            $filas[] = $this->filaSinBono($campoNombre, $codigoLabor, null, 'Sin bono', null, []);
        }
        return $filas;
    }

    private function filaSinBono(string $campoNombre, string $codigoLabor, ?string $clave, string $texto, ?string $observacion, array $recojos): array
    {
        $fila = [
            'registro_diario_id' => null, 'tipo' => 'SIN BONO', 'metodo_bonificacion' => '', 'cuadrillero_id' => null,
            'nombre_trabajador' => $texto, 'campo' => $campoNombre, 'labor' => $codigoLabor, 'total_bono' => '', 'bono_manual' => false,
            'horarios' => '', 'rango_total_horas' => '', 'total_horas' => '', 'estandar_dia' => '',
            'sb_trabajador' => $clave, 'sb_observacion' => $observacion,
        ];
        for ($i = 1; $i <= max($this->recojos, ...array_keys($recojos ?: [1 => 0])); $i++) {
            $fila['produccion_' . $i] = $recojos[$i] ?? '';
        }
        return $fila;
    }

    /**
     * Filas SIN BONO de la tabla → filas de CampoProduccionProceso::guardarSinBono. Sin trabajador, el texto de la
     * columna Trabajador es la observación.
     */
    private function sinBonoDesdeTabla(array $datos, int $recojos): array
    {
        $filas = [];
        foreach ($datos as $fila) {
            if (($fila['tipo'] ?? null) !== 'SIN BONO') {
                continue;
            }
            $porRecojo = [];
            for ($i = 1; $i <= $recojos; $i++) {
                $porRecojo[$i] = $fila['produccion_' . $i] ?? null;
            }
            [$tipo, $id] = str_contains((string) ($fila['sb_trabajador'] ?? ''), ':') ? explode(':', $fila['sb_trabajador']) : [null, null];
            $texto = trim((string) ($fila['nombre_trabajador'] ?? ''));
            $filas[] = [
                'tipo' => ['c' => 'cuadrilla', 'p' => 'planilla'][$tipo] ?? null, 'trabajador_id' => $id, 'recojos' => $porRecojo,
                'observacion' => $tipo ? ($fila['sb_observacion'] ?? null) : ($texto !== '' ? $texto : 'Sin bono'),
            ];
        }
        return $filas;
    }

    /**
     * Inicializa la estructura para acumular valores numéricos
     */
    private function inicializarAcumuladoresTotales(): array
    {
        $totales = [
            'total_bono' => 0,
            'total_horas' => 0,
        ];

        for ($i = 1; $i <= $this->recojos; $i++) {
            $totales['produccion_' . $i] = 0;
        }

        return $totales;
    }

    /**
     * Construye el array con el formato adecuado para una fila de TOTAL
     */
    private function crearFilaTotal(string $etiquetaTrabajador, string $campoNombre, string $codigoLabor, array $totales): array
    {
        // Convertir horas decimales acumuladas (ej: 12.5) a formato HH:MM (ej: "12:30")
        $horasEnteras = floor($totales['total_horas']);
        $minutos = round(($totales['total_horas'] - $horasEnteras) * 60);
        $rangoFormateado = sprintf('%02d:%02d', $horasEnteras, $minutos);

        $rowTotal = [
            'registro_diario_id' => null,
            'tipo' => 'TOTAL',
            'metodo_bonificacion' => '',
            'nombre_trabajador' => $etiquetaTrabajador,
            'campo' => '',
            'labor' => '',
            'total_bono' => round($totales['total_bono'], 2),
            'bono_manual' => false,
            'horarios' => '',
            'rango_total_horas' => $rangoFormateado,
            'total_horas' => round($totales['total_horas'], 2),
        ];

        for ($i = 1; $i <= $this->recojos; $i++) {
            $rowTotal['produccion_' . $i] = round($totales['produccion_' . $i], 2);
        }

        return $rowTotal;
    }
    public function obtenerCuadrillasPorFechaYLabor($campoNombre, $codigoLabor, $fecha, $actividadId)
    {

        $filtroDetalleHoras = function ($query) use ($campoNombre, $codigoLabor) {
            $query->where('campo_nombre', $campoNombre)
                ->where('codigo_labor', $codigoLabor);
        };

        $filtroActividadBono = function ($query) use ($actividadId) {
            $query->where('actividad_id', $actividadId);
        };

        return CuadRegistroDiario::with([
            'cuadrillero:id,nombres',
            'actividadesBonos' => function ($query) use ($filtroActividadBono) {
                $filtroActividadBono($query);
            },
            'detalleHoras' => function ($query) use ($filtroDetalleHoras) {
                $filtroDetalleHoras($query);
                $query->orderBy('hora_inicio');
            }
        ])
            ->where('fecha', $fecha)
            ->whereHas('detalleHoras', $filtroDetalleHoras)
            ->get();
    }


    public function obtenerPlanillasPorFechaYLabor($campoNombre, $codigoLabor, $fecha, $actividadId)
    {
        $filtroDetalles = function ($query) use ($campoNombre, $codigoLabor) {
            $query->where('campo_nombre', $campoNombre)
                ->where('codigo_labor', $codigoLabor);
        };

        $filtroActividadBono = function ($query) use ($actividadId) {
            $query->where('actividad_id', $actividadId);
        };

        return PlanRegistroDiario::with([
            'detalleMensual',
            'actividadesBonos' => function ($query) use ($filtroActividadBono) {
                $filtroActividadBono($query);
            },
            'detalles' => function ($query) use ($filtroDetalles) {
                $filtroDetalles($query);
                $query->orderBy('hora_inicio');
            }
        ])
            ->where('fecha', $fecha)
            ->whereHas('detalles', $filtroDetalles)
            ->get();
    }

    public function guardarBonificaciones($datos)
    {
        try {

            if (!$this->actividad) {
                throw new Exception("La actividad ha caducado");
            }

            // Filas SIN BONO: se guardan junto con los bonos (todo o nada)
            $sinBono = $this->sinBonoDesdeTabla($datos, (int) $this->recojos);
            $produccion = app(\App\Services\Campo\Produccion\CampoProduccionProceso::class);
            $guardadosSinBono = \Illuminate\Support\Facades\DB::transaction(function () use ($datos, $sinBono, $produccion) {
                GuardarBonificacionProceso::ejecutar(
                    $this->actividad,
                    $this->metodos,
                    $this->unidades,
                    $this->recojos,
                    $datos
                );
                return $produccion->guardarSinBono($this->actividad, $sinBono);
            });

            $this->alert('success', 'Datos guardados correctamente.' . ($guardadosSinBono ? " Producción sin bono: {$guardadosSinBono} registro(s)." : ''));
            $this->dispatch('produccionActualizada');
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }
    public function render()
    {
        // Destajo antiguo ya guardado (descuenta horas del jornal): se avisa para corregirlo a mano
        $destajoAntiguo = $this->actividad ? \App\Models\CuadActividadBono::where('actividad_id', $this->actividad->id)
            ->whereHas('metodo', fn($q) => $q->whereNull('estandar')->where('respeta_jornal', false))->count() : 0;
        return view('livewire.cuadrilla.gestion-cuadrilla-bonificaciones-detalle-component', ['destajoAntiguo' => $destajoAntiguo]);
    }
}