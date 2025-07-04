<?php

namespace App\Http\Controllers;

use App\Models\Campo;
use App\Models\Empleado;
use App\Models\ReporteDiario;
use App\Models\ReporteDiarioCampos;
use App\Models\ReporteDiarioCuadrilla;
use App\Models\ReporteDiarioCuadrillaDetalle;
use App\Models\ReporteDiarioDetalle;
use Illuminate\Http\Request;

class ReporteDiarioControllerBackup extends Controller
{
    public function index()
    {
        return view('reporte.reporte_diario');
    }
    public function GuardarInformacion(Request $request)
    {
        $fecha = $request->input('fecha');
        $datos = json_decode($request->input('empleados'), true);
        $totales = json_decode($request->input('totales'), true);

        // Validar que se recibieron datos
        if (empty($datos)) {
            return response()->json([
                'success' => false,
                'message' => 'No se recibieron datos para guardar.'
            ]);
        }

        
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
                                'fecha' => $request->input('fecha')
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
                        ['documento' => $documento], // Suponiendo que el documento está en la primera columna
                        [
                            'empleado_nombre' => $nombresEmpleado,
                            'fecha' => $request->input('fecha'),
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

                    // Formatear el total de horas acumuladas
                    $totalHorasFormateadas = $totalHoras->format('H:i');

                    // Actualizar el total de horas en el reporte de la cuadrilla
                    $reporteDiario->update([
                        'total_horas' => $totalHorasFormateadas
                    ]);
                }

            }

            // Confirmar la transacción
            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Información guardada correctamente.'
            ]);

        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            \DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function ImportarEmpleados(Request $request)
    {
        try {
            $fecha = $request->input('fecha');

            // Obtener los empleados activos desde una fuente externa o base de datos
            $empleados = Empleado::where('status', 'activo')->get(); // Ajusta la lógica de obtención de empleados según sea necesario


            foreach ($empleados as $empleado) {
                // Verificar si el empleado ya existe en la tabla 'reporte_diarios' por el documento
                $reporteExistente = ReporteDiario::where('documento', $empleado->documento)->where('fecha', $fecha)->first();

                if ($reporteExistente) {
                    // Si el reporte ya existe, actualizar el nombre
                    $reporteExistente->empleado_nombre = $empleado->NombreCompleto;
                    $reporteExistente->orden = $empleado->orden;
                    $reporteExistente->save(); // Guardar los cambios
                } else {
                    // Si no existe, insertar un nuevo registro con la fecha dada
                    ReporteDiario::create([
                        'documento' => $empleado->documento,
                        'empleado_nombre' => $empleado->NombreCompleto,
                        'fecha' => $fecha,
                        'total_horas' => '00:00:00',
                        'tipo_trabajador' => 'planilla',
                        'asistencia' => $empleado->asistencia ?? 'A',
                        'orden' => $empleado->orden
                    ]);
                }
            }

            // Buscar en ReporteDiario y ReporteDiarioDetalle después de la importación
            $reportesConDetalles = ReporteDiario::with('detalles') // 'detalles' es la relación con ReporteDiarioDetalle
                ->where('fecha', $fecha)
                ->orderBy('orden')
                ->get();

            $cuadrillas = ReporteDiarioCuadrilla::with('detalles')->where('fecha', $fecha)->get();

            // Retornar la respuesta en formato JSON con los reportes actualizados/inserciones y detalles
            return response()->json([
                'success' => true,
                'message' => 'Empleados importados o actualizados correctamente',
                'data' => $reportesConDetalles,
                'cuadrillas' => $cuadrillas
            ]);
        } catch (\Exception $e) {
            // En caso de error, retornar la respuesta JSON con el error
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error durante la importación: ' . $e->getMessage()
            ]);
        }
    }

    public function ActualizarCampos(Request $request)
    {
        try {
            // Validar los datos recibidos
            $validatedData = $request->validate([
                'fecha' => 'required|date',
                'campos' => 'required|integer'
            ]);

            // Buscar si ya existe un registro para la fecha
            $reporteDiarioCampos = ReporteDiarioCampos::firstOrNew(['fecha' => $validatedData['fecha']]);

            // Actualizar la cantidad de campos
            $reporteDiarioCampos->campos = $validatedData['campos'];

            // Guardar el registro
            $reporteDiarioCampos->save();

            return response()->json(['success' => true, 'message' => 'Campos actualizados correctamente']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al actualizar los campos']);
        }
    }
    public function ObtenerCampos(Request $request)
    {
        // Validar que la fecha esté presente
        $request->validate([
            'fecha' => 'required|date',
        ]);

        // Buscar los campos (grupos) correspondientes a la fecha
        $reporte = ReporteDiarioCampos::where('fecha', $request->fecha)->first();
        if(!$reporte){
            ReporteDiarioCampos::Create([
                'fecha'=>$request->fecha,
                'campos'=>1
            ]);
        }
        // Si se encuentra un registro, devolver la cantidad de campos, si no, devolver 1 por defecto
        $campos = $reporte ? $reporte->campos : 1;

        return response()->json([
            'success' => true,
            'campos' => $campos
        ]);
    }
    public function ObtenerCampo()
    {
        $campos = Campo::orderBy('nombre')->get(['nombre'])->pluck('nombre')->toArray();

        return response()->json($campos);
    }
}
