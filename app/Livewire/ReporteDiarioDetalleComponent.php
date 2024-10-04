<?php

namespace App\Livewire;

use App\Models\Campo;
use App\Models\Empleado;
use App\Models\ReporteDiario;
use App\Models\ReporteDiarioCampos;
use App\Models\ReporteDiarioCuadrilla;
use App\Models\ReporteDiarioCuadrillaDetalle;
use App\Models\TipoAsistencia;
use App\Models\ReporteDiarioDetalle;
use Carbon\Carbon;
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
    public $tipoAsistenciasEntidad;
    protected $listeners = ["importarPlanilla", "GuardarInformacion"];
    public function mount()
    {
        $this->campos = Campo::orderBy('nombre')->get(['nombre'])->pluck('nombre')->toArray();
        $this->tipoAsistenciasEntidad = TipoAsistencia::get(['codigo','horas_jornal'])->pluck('horas_jornal','codigo')->toArray();
       
        $this->tipoAsistencias = TipoAsistencia::all()->pluck('codigo')->toArray();
       
        $this->ImportarEmpleados();
        $this->ObtenerTareas();
    }
    public function GuardarInformacion($datos, $totales)
    {
        if (!$this->fecha) {
            return;
        }

        if (!is_array($datos)) {
            return;
        }
        if (!is_array($totales)) {
            return;
        }

        $fecha = $this->fecha;

        // Iniciar transacción para asegurar integridad de datos
        \DB::beginTransaction();

        try {

            $tablaTotales = ReporteDiarioCampos::whereDate('fecha', $fecha)->first();
            if ($tablaTotales) {
                $tablaTotales->update([
                    'total_planillas_asistidos' => $totales['asistido'],
                    'total_faltas' => $totales['faltas'],
                    'total_vacaciones' => $totales['vacaciones'],
                    'total_licencia_maternidad' => $totales['licenciaMaternidad'],
                    'total_licencia_sin_goce' => $totales['licenciaSinGoce'],
                    'total_licencia_con_goce' => $totales['licenciaConGoce'],
                    'total_descanso_medico' => $totales['descansoMedico'],
                    'total_atencion_medica' => $totales['atencionMedica'],
                    'total_cuadrillas' => $totales['cuadrillas'],
                    'total_planilla' => $totales['totalPlanilla']
                ]);
            }

            ReporteDiarioCuadrilla::whereDate('fecha', $fecha)->delete();
            // Iterar sobre cada fila de datos
            foreach ($datos as $fila) {

                $documento = $fila[0];
                $nombresEmpleado = trim(preg_replace('/[\x00-\x1F\x7F\xA0]/u', ' ', $fila[1]));

                $asistencia = $fila[2];

                if (!$documento) {
                    if (mb_strtolower($nombresEmpleado) == 'cuadrilla') {

                        $numeroCuadrilleros = $fila[3];

                        if ($numeroCuadrilleros) {
                            $reporteDiarioCuadrilla = ReporteDiarioCuadrilla::create([
                                'numero_cuadrilleros' => $numeroCuadrilleros,
                                'total_horas' => '00:00:00',
                                'fecha' => $this->fecha
                            ]);

                            $totalHoras = new \DateTime('00:00:00');

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

                                    // Calcular la diferencia entre hora de entrada y salida
                                    $interval = $horaInicioDT->diff($horaFinDT);

                                    // Acumular el tiempo trabajado al total
                                    $totalHoras->add($interval);

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
                            $totalHorasFormateadas = $totalHoras->format('H:i');

                            // Actualizar el total de horas en el reporte de la cuadrilla
                            $reporteDiarioCuadrilla->update([
                                'total_horas' => $totalHorasFormateadas
                            ]);
                        }


                    }
                } else {
                    // Insertar o actualizar el reporte diario
                    $reporteDiario = ReporteDiario::updateOrCreate(
                        ['documento' => $documento, 'fecha' => $this->fecha], // Suponiendo que el documento está en la primera columna
                        [
                            'empleado_nombre' => $nombresEmpleado,
                            'fecha' => $this->fecha,
                            'total_horas' => '00:00:00', // Puedes ajustar esto según sea necesario
                            'tipo_trabajador' => 'planilla',
                            'asistencia' => $asistencia // Asistencia
                        ]
                    );


                    // Eliminar los detalles existentes asociados al reporte
                    ReporteDiarioDetalle::where('reporte_diario_id', $reporteDiario->id)->delete();
                    $totalHoras = new \DateTime('00:00:00');

                    // Procesar los detalles en grupos de 4 columnas
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

                            // Calcular la diferencia entre hora de entrada y salida
                            $interval = $horaInicioDT->diff($horaFinDT);

                            // Acumular el tiempo trabajado al total
                            $totalHoras->add($interval);

                            ReporteDiarioDetalle::create([
                                'reporte_diario_id' => $reporteDiario->id,
                                'campo' => $campo,
                                'labor' => $labor,
                                'hora_inicio' => $horaEntrada,
                                'hora_salida' => $horaSalida
                            ]);
                        }
                    }

                    $indiceTotal = count($fila)-1;
                    // Formatear el total de horas acumuladas
                    $totalHorasFormateadas = isset($fila[$indiceTotal])?$fila[$indiceTotal]:'00:00';

                    // Formatear el total de horas acumuladas
                    //$totalHorasFormateadas = $totalHoras->format('H:i');

                    // Actualizar el total de horas en el reporte de la cuadrilla
                    $reporteDiario->update([
                        'total_horas' => $totalHorasFormateadas
                    ]);
                }

            }

            // Confirmar la transacción
            \DB::commit();

            $this->ImportarEmpleados();
            $this->dispatch("setEmpleados", $this->empleados);
            $this->alert("success", 'Información guardada correctamente.');


        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            \DB::rollBack();

            $this->alert("error", $e->getMessage());
        }
    }
    public function importarPlanilla()
    {
        $empleados = Empleado::where('status', 'activo')->get();

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
                    'orden' => $empleado->orden
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

        $this->empleados = ReporteDiario::with('detalles')
            ->whereDate('fecha', $this->fecha)
            ->orderBy('orden')
            ->get()
            ->map(function ($empleado, $indice) {
                $empleadoData = [
                    'documento' => $empleado->documento,
                    'empleado_nombre' => $empleado->empleado_nombre,
                    'asistencia' => $empleado->asistencia ?? '',
                    'total_horas'=>$empleado->total_horas ? Carbon::parse($empleado->total_horas)->format("H:i"):''
                ];
        
                // Si tiene detalles, agregarlos como campos adicionales dinámicos
                $detalles = $empleado->detalles;
        
                if ($detalles->count() > 0) {
                    foreach ($detalles as $i => $detalle) {
                        // Crear claves dinámicas basadas en el índice $i
                        $empleadoData['campo_' . ($i + 1)] = $detalle->campo ?? '';
                        $empleadoData['labor_' . ($i + 1)] = $detalle->labor ?? '';
                        $empleadoData['entrada_' . ($i + 1)] = $detalle->hora_inicio ? Carbon::parse($detalle->hora_inicio)->format("H:i"):'';
                        $empleadoData['salida_' . ($i + 1)] = $detalle->hora_salida ? Carbon::parse($detalle->hora_salida)->format("H:i"):'';
                    }
                }

                return $empleadoData;

            })->toArray();

    }
    public function addGroupBtn()
    {
        try {
            $this->tareas++;
            $reporteDiarioCampos = ReporteDiarioCampos::firstOrNew(['fecha' => $this->fecha]);
            $reporteDiarioCampos->campos = $this->tareas;
            $reporteDiarioCampos->save();

            $this->dispatch("setColumnas", $this->tareas);

            $this->alert('success', 'Campos actualizados correctamente');
        } catch (\Exception $e) {
            $this->alert('error', 'Error al actualizar los campos');
        }
    }
    public function removeGroupBtn()
    {
        try {
            if ($this->tareas == 1) {
                return;
            }

            $this->tareas--;
            $reporteDiarioCampos = ReporteDiarioCampos::firstOrNew(['fecha' => $this->fecha]);
            $reporteDiarioCampos->campos = $this->tareas;
            $reporteDiarioCampos->save();
            $this->dispatch("setColumnas", $this->tareas);

            $this->alert('success', 'Campos actualizados correctamente');
        } catch (\Exception $e) {
            $this->alert('error', 'Error al actualizar los campos');
        }
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
                'campos' => 1
            ]);
        }
        $this->tareas = $reporte ? $reporte->campos : 1;
    }
    public function render()
    {
        return view('livewire.reporte-diario-detalle-component');
    }
}
