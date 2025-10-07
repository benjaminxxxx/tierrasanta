<?php

namespace App\Livewire;

use App\Models\Campo;
use App\Models\ConsolidadoRiego;
use App\Models\Empleado;
use App\Models\ReporteDiario;
use App\Models\ReporteDiarioCampos;
use App\Models\ReporteDiarioCuadrilla;
use App\Models\ReporteDiarioCuadrillaDetalle;
use App\Models\TipoAsistencia;
use App\Models\ReporteDiarioDetalle;
use App\Services\CuadrillaServicio;
use App\Services\RecursosHumanos\Personal\ActividadServicio;
use App\Support\DateHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ReporteDiarioDetalleComponent extends Component
{
    use LivewireAlert;
    public $campos;
    public $tareas;
    public $fecha;
    public $empleados;
    public $tipoAsistencias;
    public $tipoAsistenciasCodigos;
    public $tipoAsistenciasHoras;
    public $minutosDescontados;
    public $hasUnsavedChanges = false;
    public $totalesAsistencias = [];
    public $reporteDiarioCampos;
    public $totalesAsistenciasCuadrilleros = 0;
    public $totalCuadrilleroSegunHora = 0;
    protected $listeners = ["importarPlanillaAgraria", "eliminarPlanilla"];
    public function mount()
    {
        $this->reporteDiarioCampos = ReporteDiarioCampos::whereDate('fecha', $this->fecha)->first();
        $this->campos = [""];
        $camposNuevos = Campo::orderBy('nombre')->get(['nombre'])->pluck('nombre')->toArray();
        $this->campos = array_merge($this->campos, $camposNuevos);
        $this->tipoAsistenciasHoras = TipoAsistencia::get(['codigo', 'horas_jornal'])->pluck('horas_jornal', 'codigo')->toArray();


        $this->tipoAsistencias = TipoAsistencia::all();
        $this->tipoAsistenciasCodigos = $this->tipoAsistencias->pluck('codigo')->toArray();
        $this->tipoAsistenciasCodigos = array_merge([''], $this->tipoAsistenciasCodigos);

        $this->obtenerTotales();
        $this->ImportarEmpleados();
        $this->ObtenerTareas();
    }
    public function eliminarPlanilla($planillas)
    {
        foreach ($planillas as $planilla) {
            ReporteDiario::where('documento', $planilla['documento'])
                ->where('fecha', $this->fecha)
                ->delete();
        }
        $this->ImportarEmpleados();
        $this->dispatch("setEmpleados", $this->empleados);
        $this->alert('success', 'Empleados eliminados correctamente.');
    }
    public function guardarInformacionRegistroPlanilla($datos)
    {
        if (!$this->fecha) {
            return;
        }

        if (!is_array($datos)) {
            return;
        }

        $fecha = $this->fecha;

        // Iniciar transacción para asegurar integridad de datos
        DB::beginTransaction();

        try {
            //guardamos la cantidad de tramos
            $reporteDiarioCampos = ReporteDiarioCampos::firstOrNew(['fecha' => $this->fecha]);
            $reporteDiarioCampos->campos = $this->tareas;
            $reporteDiarioCampos->save();

            ReporteDiarioCuadrilla::whereDate('fecha', $fecha)->delete();
            $contadorAsistencias = [];
            $errores = [];
            // Iterar sobre cada fila de datos
            foreach ($datos as $i => $fila) {

                $documento = $fila[0];
                $nombresEmpleado = trim(preg_replace('/[\x00-\x1F\x7F\xA0]/u', ' ', $fila[1]));

                $asistencia = $fila[2];
                $indiceTotal = count($fila) - 2;
                $indiceBono = count($fila) - 1;
                $tramos = [];


                $bonoProductividad = trim(preg_replace('/[\x00-\x1F\x7F\xA0]/u', ' ', $fila[$indiceBono]));

                // Validar que el valor es un número decimal o entero
                if (preg_match('/^[-+]?[0-9]*\.?[0-9]+$/', $bonoProductividad)) {
                    // Convertir a decimal
                    $bonoProductividad = (float) $bonoProductividad; // O puedes usar number_format($bonoProductividad, 2)
                } else {
                    // Manejar el error: el valor no es un número válido
                    $bonoProductividad = null; // O asignar un valor predeterminado
                }

                if (!$documento) {
                    if (mb_strtolower($nombresEmpleado) == 'cuadrilla') {
/*
                        $numeroCuadrilleros = trim(preg_replace('/[\x00-\x1F\x7F\xA0]/u', ' ', $fila[3]));

                        if ($numeroCuadrilleros) {
                            $reporteDiarioCuadrilla = ReporteDiarioCuadrilla::create([
                                'numero_cuadrilleros' => $numeroCuadrilleros,
                                'total_horas' => '0',
                                'fecha' => $this->fecha
                            ]);

                            //$totalHoras = new \DateTime('00:00:00');

                            for ($i = 4; $i < count($fila); $i += 4) {
                                $campo = $fila[$i] ?? null;
                                $labor = $fila[$i + 1] ?? null;
                                $horaEntrada = $fila[$i + 2] ?? null;
                                $horaSalida = $fila[$i + 3] ?? null;

                                // Reemplazar puntos por dos puntos en la hora, si es necesario
                                if ($horaEntrada) {
                                    $horaEntrada = str_replace('.', ':', $horaEntrada);
                                }
                                if ($horaSalida) {
                                    $horaSalida = str_replace('.', ':', $horaSalida);
                                }

                                // Si se tienen los datos necesarios, crear el detalle
                                if ($campo && $labor && $horaEntrada && $horaSalida) {



                                    $horaInicioDT = \DateTime::createFromFormat('H:i', $horaEntrada);
                                    $horaFinDT = \DateTime::createFromFormat('H:i', $horaSalida);

                                    if (!$horaInicioDT || !$horaFinDT) {
                                        continue;
                                    }


                                    ReporteDiarioCuadrillaDetalle::create([
                                        'reporte_diario_id' => $reporteDiarioCuadrilla->id,
                                        'campo' => $campo,
                                        'labor' => $labor,
                                        'hora_inicio' => $horaEntrada,
                                        'hora_salida' => $horaSalida
                                    ]);
                                }
                            }

                            // Formatear el total de horas acumuladas

                            $totalHorasFormateadas = isset($fila[$indiceTotal]) ? trim(preg_replace('/[\x00-\x1F\x7F\xA0]/u', ' ', $fila[$indiceTotal])) : '0';
                            //$totalHorasFormateadas = str_replace('.', '.', $totalHorasFormateadas);

                            if (!preg_match('/^([0-9]|[1-9][0-9]|[0-1][0-9]|2[0-9])(\.[0-5]?[0-9]?)?$/', $totalHorasFormateadas)) {
                                // Si no es válido, asignar '0' por defecto
                                $totalHorasFormateadas = '0';
                            }
                            // Actualizar el total de horas en el reporte de la cuadrilla
                            $reporteDiarioCuadrilla->update([
                                'total_horas' => $totalHorasFormateadas
                            ]);
                        }*/
                    }
                        
                } else {
                    // Insertar o actualizar el reporte diario
                    if ($asistencia != null) {
                        if (!array_key_exists($asistencia, $contadorAsistencias))
                            $contadorAsistencias[$asistencia] = 0;

                        $contadorAsistencias[$asistencia]++;
                    }



                    // Eliminar los detalles existentes asociados al reporte
                    //ReporteDiarioDetalle::where('reporte_diario_id', $reporteDiario->id)->delete();

                    // Procesar los detalles en grupos de 4 columnas
                    for ($j = 4; $j < count($fila); $j += 4) {
                        $campo = $fila[$j] ?? null;
                        $labor = $fila[$j + 1] ?? null;
                        $inicio = $fila[$j + 2] ?? null;
                        $fin = $fila[$j + 3] ?? null;

                        // Reemplazar puntos por dos puntos en la hora, si es necesario
                        if ($inicio) {
                            $inicio = str_replace('.', ':', $inicio);
                        }
                        if ($fin) {
                            $fin = str_replace('.', ':', $fin);
                        }
                        
                        if ($inicio || $fin || $campo || $labor) {
                            if (!$inicio || !$fin || !$labor) {
                                $errores[] = "Fila " . ($i + 1) . ", tramo $j: falta hora o labor.";
                                continue;
                            }

                            $tramos[] = [
                                'labor' => $labor,
                                'campo' => $campo,
                                'hora_inicio' => $inicio,
                                'hora_salida' => $fin,
                            ];
                        }
                    }

                    $horaFormateada = DateHelper::formatearHorasDesdeTexto($fila[$indiceTotal] ?? null);

                    $registro = ReporteDiario::updateOrCreate(
                        ['documento' => $documento, 'fecha' => $this->fecha], // Suponiendo que el documento está en la primera columna
                        [
                            'empleado_nombre' => $nombresEmpleado,
                            'fecha' => $this->fecha,
                            'total_horas' => $horaFormateada, // Puedes ajustar esto según sea necesario
                            'tipo_trabajador' => 'planilla',
                            'asistencia' => $asistencia ?? '',
                            //'bono_productividad' => $bonoProductividad
                        ]
                    );

                    if (empty($tramos)) {
                        $registro->detalles()->delete();
                        continue;
                    }

                    $existentes = $registro->detalles()->get();

                    // Mapear claves para comparar
                    $clave = fn($tramo) => implode('|', [
                        $tramo['labor'],
                        $tramo['campo'],
                        Carbon::parse($tramo['hora_inicio'])->format('H:i'),
                        Carbon::parse($tramo['hora_salida'])->format('H:i'),
                    ]);

                    $existentesMap = $existentes->keyBy($clave);
                    $nuevosMap = collect($tramos)->keyBy($clave);

                    foreach ($existentes as $existente) {

                        $k = $clave($existente->toArray());
                        if (!$nuevosMap->has($k)) {
                            $existente->delete();
                        }
                    }

                    foreach ($nuevosMap as $k => $nuevo) {
                        $detalle = $existentesMap->get($k);

                        if ($detalle) {
                            // Ya existe, se mantiene. Si necesitas actualizar algún campo adicional, hazlo aquí.
                            continue;
                        }

                        // Crear nuevo
                        $registro->detalles()->create([
                            'campo' => $nuevo['campo'],
                            'labor' => $nuevo['labor'],
                            'hora_inicio' => $nuevo['hora_inicio'],
                            'hora_salida' => $nuevo['hora_salida']
                        ]);
                    }
                }
            }

            $reporteDiarioCampos = ReporteDiarioCampos::whereDate('fecha', $this->fecha)->first();
            if ($reporteDiarioCampos) {

                $reporteDiarioCampos->totales()->detach();

                foreach ($contadorAsistencias as $codigoAsistencia => $contadorAsistencia) {
                    $tipoAsistencia = TipoAsistencia::where('codigo', $codigoAsistencia)->first();
                    if ($tipoAsistencia) {
                        $reporteDiarioCampos->totales()->attach($tipoAsistencia->id, ['total' => $contadorAsistencia]);
                    }
                }
                $this->obtenerTotales();
            }

            DB::commit();

            ActividadServicio::detectarYCrearActividades($this->fecha);

            $this->ImportarEmpleados();
            $this->hasUnsavedChanges = false;
            $this->dispatch("setEmpleados", $this->empleados);
            $this->alert("success", 'Información guardada correctamente.');
        } catch (\Exception $th) {
            // Revertir la transacción en caso de error
            DB::rollBack();
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', $th->getMessage());
        }
    }


    public function obtenerTotales()
    {
        if (!$this->reporteDiarioCampos || !$this->tipoAsistencias) {
            return;
        }

        $totales = $this->reporteDiarioCampos->totales()->pluck('total', 'tipo_asistencia_id')->toArray();

        $arrayDatos = [];
        $sumaTotal = 0;

        foreach ($this->tipoAsistencias as $tipoAsistencia) {
            $total = 0;
            if (array_key_exists($tipoAsistencia->id, $totales)) {
                $total = $totales[$tipoAsistencia->id];
            }
            $arrayDatos[$tipoAsistencia->codigo] = [
                'descripcion' => $tipoAsistencia->descripcion,
                'total' => $total,
            ];
            $sumaTotal += $total;
        }

        $totalCuadrilleros = ReporteDiarioCuadrilla::whereDate('fecha', $this->reporteDiarioCampos->fecha)->sum('numero_cuadrilleros');
        //$sumaTotal+=$totalCuadrilleros; SE SOLICITO NO SUMAR LAS CUADRILLAS

        $this->totalesAsistencias = $arrayDatos;
        $this->totalesAsistenciasCuadrilleros = $totalCuadrilleros;
        $this->reporteDiarioCampos->total_planilla = $sumaTotal;
        $this->reporteDiarioCampos->save();
        $this->totalCuadrilleroSegunHora = CuadrillaServicio::cantidadCuadrilleros($this->reporteDiarioCampos->fecha);
    }

    public function importarPlanillaAgraria()
    {
        $fecha = Carbon::parse($this->fecha);
        $mes = $fecha->format('m');
        $anio = $fecha->format('Y');
        $empleados = Empleado::planillaAgraria($mes,$anio)->get();
        
        if ($empleados->count() == 0) {
            return;
        }

        if (!$this->fecha) {
            return;
        }


        foreach ($empleados as $empleado) {
            // Verificar si el empleado ya existe en la tabla 'reporte_diarios' por el documento
            $reporteExistente = ReporteDiario::where('documento', $empleado->documento)->whereDate('fecha', $this->fecha)->first();

            if ($reporteExistente) {
                // Si el reporte ya existe, actualizar el nombre
                $reporteExistente->empleado_nombre = $empleado->NombreCompleto;
                $reporteExistente->orden = $empleado->orden;
                $reporteExistente->save(); // Guardar los cambios
            } else {
                ReporteDiario::create([
                    'documento' => $empleado->documento,
                    'empleado_nombre' => $empleado->NombreCompleto,
                    'fecha' => $this->fecha,
                    'total_horas' => '00:00:00',
                    'tipo_trabajador' => 'planilla',
                    'asistencia' => $empleado->asistencia ?? '',
                    'orden' => $empleado->orden,
                    'bono_productividad' => 0
                ]);
            }
        }

        $this->ImportarEmpleados();
        $this->dispatch("setEmpleados", $this->empleados);
    }
    public function ImportarEmpleados()
    {
        if (!$this->fecha) {
            return;
        }
        /*Esta funcionalidad es para extraer las horas de los regadores y pasarlo a el reporte diario principal */
        $informacionAdicionalRiego = ConsolidadoRiego::whereDate('fecha', $this->fecha)
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->regador_documento => [
                        'hora_inicio' => $item->hora_inicio,
                        'hora_fin' => $item->hora_fin,
                        'total_horas_jornal' => $item->total_horas_jornal,
                    ],
                ];
            })
            ->toArray();

        foreach ($informacionAdicionalRiego as $documento => $infoRiego) {


            if ($infoRiego['hora_inicio'] && $infoRiego['hora_fin'] && $infoRiego['total_horas_jornal'] !== '00:00:00') {

                $horaInicio = Carbon::parse($infoRiego['hora_inicio'])->format('H:i');
                $horaFin = Carbon::parse($infoRiego['hora_fin'])->format('H:i');

                $reporteDiario = ReporteDiario::where('documento', $documento)
                    ->whereDate('fecha', $this->fecha)
                    ->first();

                if ($reporteDiario) {
                    // Verificar si ya existe un detalle con la misma hora_inicio y hora_fin
                    $existeRegistro = ReporteDiarioDetalle::where('reporte_diario_id', $reporteDiario->id)
                        ->whereTime('hora_inicio', $horaInicio)
                        ->whereTime('hora_salida', $horaFin)
                        ->exists();

                    if (!$existeRegistro) {

                        ReporteDiarioDetalle::where('reporte_diario_id', $reporteDiario->id)->delete();

                        ReporteDiarioDetalle::create([
                            'reporte_diario_id' => $reporteDiario->id,
                            'hora_inicio' => $horaInicio,
                            'hora_salida' => $horaFin,
                            'campo' => 'FDM',
                            'labor' => '81'
                        ]);

                        $reporteDiario->update([
                            'asistencia' => 'A',
                            'tipo_trabajador' => 'planilla',
                            'total_horas' => $infoRiego['total_horas_jornal'],
                            'fecha' => $this->fecha
                        ]);
                    }
                }
            }
        }

        $this->empleados = ReporteDiario::with('detalles')
            ->whereDate('fecha', $this->fecha)
            ->orderBy('orden')
            ->get()
            ->map(function ($empleado, $indice) {
                $empleadoData = [
                    'documento' => $empleado->documento,
                    'empleado_nombre' => $empleado->empleado_nombre,
                    'asistencia' => $empleado->asistencia ?? '',
                    'total_horas' => $empleado->total_horas ? Carbon::parse($empleado->total_horas)->format("G.i") : '',
                    'bono_productividad' => $empleado->bono_productividad
                ];

                // Si tiene detalles, agregarlos como campos adicionales dinámicos
                $detalles = $empleado->detalles;

                if ($detalles->count() > 0) {
                    foreach ($detalles as $i => $detalle) {
                        // Crear claves dinámicas basadas en el índice $i
                        $empleadoData['campo_' . ($i + 1)] = $detalle->campo ?? '';
                        $empleadoData['labor_' . ($i + 1)] = $detalle->labor ?? '';
                        $empleadoData['entrada_' . ($i + 1)] = $detalle->hora_inicio ? Carbon::parse($detalle->hora_inicio)->format("G.i") : '';
                        $empleadoData['salida_' . ($i + 1)] = $detalle->hora_salida ? Carbon::parse($detalle->hora_salida)->format("G.i") : '';
                    }
                }

                return $empleadoData;
            })->toArray();

        $cuadrillas = ReporteDiarioCuadrilla::with('detalles')
            ->where('fecha', $this->fecha)
            ->get()
            ->map(function ($cuadrilla, $indice) {
                $cuadrillaData = [
                    'documento' => '',
                    'empleado_nombre' => 'CUADRILLA',
                    'numero_cuadrilleros' => $cuadrilla->numero_cuadrilleros,
                    'asistencia' => '',
                    'total_horas' => $cuadrilla->total_horas
                ];

                // Si tiene detalles, agregarlos como campos adicionales dinámicos
                $detalles = $cuadrilla->detalles;

                if ($detalles->count() > 0) {
                    foreach ($detalles as $i => $detalle) {
                        // Crear claves dinámicas basadas en el índice $i
                        $cuadrillaData['campo_' . ($i + 1)] = $detalle->campo ?? '';
                        $cuadrillaData['labor_' . ($i + 1)] = $detalle->labor ?? '';
                        $cuadrillaData['entrada_' . ($i + 1)] = $detalle->hora_inicio ? Carbon::parse($detalle->hora_inicio)->format("G.i") : '';
                        $cuadrillaData['salida_' . ($i + 1)] = $detalle->hora_salida ? Carbon::parse($detalle->hora_salida)->format("G.i") : '';
                    }
                }

                return $cuadrillaData;
            })->toArray();
        $this->empleados = array_merge($this->empleados, $cuadrillas);
    }

    public function ObtenerTareas()
    {
        if (!$this->fecha) {
            return;
        }

        $reporte = ReporteDiarioCampos::where('fecha', $this->fecha)->first();

        if (!$reporte) {
            ReporteDiarioCampos::Create([
                'fecha' => $this->fecha,
                'campos' => 1,
                'descuento_minutos' => 0
            ]);
        }
        $this->tareas = $reporte ? $reporte->campos : 1;
    }
   
    public function render()
    {
        return view('livewire.reporte-diario-detalle-component');
    }
}
