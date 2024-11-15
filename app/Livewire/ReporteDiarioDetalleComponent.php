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
use App\Models\ReporteDiarioRiego;
use App\Services\CuadrillaServicio;
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
    protected $listeners = ["importarPlanilla", "GuardarInformacion"];
    public function mount()
    {
        $this->reporteDiarioCampos = ReporteDiarioCampos::whereDate('fecha',$this->fecha)->first();
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
    public function GuardarInformacion($datos)
    {
        if (!$this->fecha) {
            return;
        }

        if (!is_array($datos)) {
            return;
        }
        
        /*
        if (!is_array($totales)) {
            return;
        }*/

        $fecha = $this->fecha;

        // Iniciar transacción para asegurar integridad de datos
        DB::beginTransaction();

        try {


            ReporteDiarioCuadrilla::whereDate('fecha', $fecha)->delete();
            $contadorAsistencias = [];
            // Iterar sobre cada fila de datos
            foreach ($datos as $fila) {

                $documento = $fila[0];
                $nombresEmpleado = trim(preg_replace('/[\x00-\x1F\x7F\xA0]/u', ' ', $fila[1]));

                $asistencia = $fila[2];
                $indiceTotal = count($fila) - 2;
                $indiceBono = count($fila) - 1;


                $bonoProductividad = trim(preg_replace('/[\x00-\x1F\x7F\xA0]/u', ' ', $fila[$indiceBono]));

                // Validar que el valor es un número decimal o entero
                if (preg_match('/^[-+]?[0-9]*\.?[0-9]+$/', $bonoProductividad)) {
                    // Convertir a decimal
                    $bonoProductividad = (float)$bonoProductividad; // O puedes usar number_format($bonoProductividad, 2)
                } else {
                    // Manejar el error: el valor no es un número válido
                    $bonoProductividad = null; // O asignar un valor predeterminado
                }

                if (!$documento) {
                    if (mb_strtolower($nombresEmpleado) == 'cuadrilla') {

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
                        }
                    }
                } else {
                    // Insertar o actualizar el reporte diario
                    if(!array_key_exists($asistencia,$contadorAsistencias))
                        $contadorAsistencias[$asistencia] = 0;
                    
                    $contadorAsistencias[$asistencia]++;

                    $reporteDiario = ReporteDiario::updateOrCreate(
                        ['documento' => $documento, 'fecha' => $this->fecha], // Suponiendo que el documento está en la primera columna
                        [
                            'empleado_nombre' => $nombresEmpleado,
                            'fecha' => $this->fecha,
                            'total_horas' => '00:00:00', // Puedes ajustar esto según sea necesario
                            'tipo_trabajador' => 'planilla',
                            'asistencia' => $asistencia,
                            'bono_productividad' => $bonoProductividad
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

                            if (!$horaInicioDT || !$horaFinDT) {
                                continue;
                            }

                            ReporteDiarioDetalle::create([
                                'reporte_diario_id' => $reporteDiario->id,
                                'campo' => $campo,
                                'labor' => $labor,
                                'hora_inicio' => $horaEntrada,
                                'hora_salida' => $horaSalida
                            ]);
                        }
                    }

                    
                    $totalHorasFormateadas = isset($fila[$indiceTotal]) ? trim(preg_replace('/[\x00-\x1F\x7F\xA0]/u', ' ', $fila[$indiceTotal])) : '00:00';
                    
                    $totalHorasFormateadas = str_replace('.', ':', $totalHorasFormateadas);

                    if (preg_match('/^\d+$/', $totalHorasFormateadas)) {
                        $totalHorasFormateadas .= ':00';
                    } elseif (preg_match('/^\d+:\d$/', $totalHorasFormateadas)) {
                        // Si el formato es algo como "3:5", convertirlo en "03:05"
                        list($hours, $minutes) = explode(':', $totalHorasFormateadas);
                        $totalHorasFormateadas = str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutes, 2, '0', STR_PAD_RIGHT);
                    }
                    
                    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $totalHorasFormateadas)) {
                        // Si no es válido, asignar '00:00' por defecto
                        $totalHorasFormateadas = '00:00';
                    } else {
                        // Si es válido, formatea a H:i (asegura que tenga dos dígitos en horas y minutos)
                        $totalHorasFormateadas = date('H:i', strtotime($totalHorasFormateadas));
                    }
                    
                    $reporteDiario->update([
                        'total_horas' => $totalHorasFormateadas
                    ]);
                }
                
            }

            $reporteDiarioCampos = ReporteDiarioCampos::whereDate('fecha',$this->fecha)->first();
            if($reporteDiarioCampos){
                
                $reporteDiarioCampos->totales()->detach();

                foreach ($contadorAsistencias as $codigoAsistencia => $contadorAsistencia) {
                    $tipoAsistencia = TipoAsistencia::where('codigo',$codigoAsistencia)->first();
                    if($tipoAsistencia){
                        $reporteDiarioCampos->totales()->attach($tipoAsistencia->id,['total'=>$contadorAsistencia]);
                        
                    }
                }
                $this->obtenerTotales();
            }
            

            // Confirmar la transacción
            DB::commit();

            $this->ImportarEmpleados();
            $this->hasUnsavedChanges = false;
            $this->dispatch("setEmpleados", $this->empleados);
            $this->alert("success", 'Información guardada correctamente.');
        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            DB::rollBack();
            $this->alert("error", $e->getMessage());
        }
    }
    public function obtenerTotales(){
        if(!$this->reporteDiarioCampos || !$this->tipoAsistencias){
            return;
        }

        $totales = $this->reporteDiarioCampos->totales()->pluck('total','tipo_asistencia_id')->toArray();

        $arrayDatos = [];
        $sumaTotal = 0;

        foreach ($this->tipoAsistencias as $tipoAsistencia) {
            $total = 0;
            if(array_key_exists($tipoAsistencia->id,$totales)){
                $total = $totales[$tipoAsistencia->id];
            }
            $arrayDatos[$tipoAsistencia->codigo] = [
                'descripcion'=>$tipoAsistencia->descripcion,
                'total'=>$total,
            ];
            $sumaTotal+=$total;
        }

        $totalCuadrilleros = ReporteDiarioCuadrilla::whereDate('fecha',$this->reporteDiarioCampos->fecha)->sum('numero_cuadrilleros');
        $sumaTotal+=$totalCuadrilleros;
        
        $this->totalesAsistencias = $arrayDatos;
        $this->totalesAsistenciasCuadrilleros = $totalCuadrilleros;
        $this->reporteDiarioCampos->total_planilla = $sumaTotal;
        $this->reporteDiarioCampos->save();
        $this->totalCuadrilleroSegunHora = CuadrillaServicio::cantidadCuadrilleros($this->reporteDiarioCampos->fecha);
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
            /*
innecesario si abajo se hace una busqueda
            $empleadosPlanilla = ReporteDiario::where('documento',$documento)->where('tipo_trabajador','planilla')->exists();
            if(!$empleadosPlanilla){
                continue;
            }*/

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
                            'tipo_trabajador' => $this->getTipoTrabajador($reporteDiario->documento),
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
    public function getTipoTrabajador($documento)
    {
        return 'planilla';
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
        $fecha = Carbon::parse($this->fecha);
        $descuentoMinutos = $fecha->isSaturday() ? 0 : 60;

        if (!$reporte) {


            $this->minutosDescontados = $descuentoMinutos;
            ReporteDiarioCampos::Create([
                'fecha' => $this->fecha,
                'campos' => 1,
                'descuento_minutos' => $descuentoMinutos
            ]);
        } else {
            if (!$reporte->descuento_minutos) {
                $reporte->descuento_minutos = $descuentoMinutos;
                $reporte->save();
                $this->minutosDescontados = $descuentoMinutos;
            }else{
                $this->minutosDescontados = $reporte->descuento_minutos;
            }
            
        }
        $this->tareas = $reporte ? $reporte->campos : 1;
    }
    public function aplicarDescuento(){
        $reporteDiarioCampos = ReporteDiarioCampos::where(['fecha' => $this->fecha])->first();
        if($this->minutosDescontados){
            $reporteDiarioCampos->descuento_minutos = $this->minutosDescontados;
        }else{
            $reporteDiarioCampos->descuento_minutos = 0;
            $this->minutosDescontados = 0;
        }
        
        $reporteDiarioCampos->save();
        $this->hasUnsavedChanges = true;
     
        $this->dispatch('recalcular',$this->hasUnsavedChanges,$this->minutosDescontados);
        $this->alert('success','Minutos actualizados correctamente');
    }
    public function render()
    {
        return view('livewire.reporte-diario-detalle-component');
    }
}
