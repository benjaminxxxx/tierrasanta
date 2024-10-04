<?php

namespace App\Livewire;
use App\Models\Empleado;
use App\Models\PlanillaAsistencia;
use App\Models\PlanillaAsistenciaDetalle;
use App\Models\ReporteDiario;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class PlanillaAsistenciaDetalleComponent extends Component
{
    use LivewireAlert;
    public $anio;
    public $mes;
    public $dias;
    public $empleados;
    protected $listeners = ["storeTableData"];
    public function mount($anio, $mes)
    {
        // Definir el año y mes iniciales
        $this->anio = $anio;
        $this->mes = $mes;

        // Generar el array de días del mes
        $this->dias = $this->obtenerDiasDelMes($anio, $mes);

        $this->obtenerEmpleados();

    }
    public function obtenerEmpleados(){

        $anio = $this->anio;
        $mes = $this->mes;

        if(!$anio || !$mes){
            return;
        }

        $ultimoDiaMes = Carbon::createFromDate($anio, $mes, 1)->endOfMonth()->day;

        $this->empleados = PlanillaAsistencia::where('mes', $mes)
            ->where('anio', $anio)
            ->with('detalles') // Traer los detalles de asistencia relacionados
            ->orderBy('grupo')
            ->get()
            ->map(function ($empleado, $indice) use ($ultimoDiaMes, $mes, $anio) {
                // Mapea los detalles de asistencia del empleado por fecha
                $diasAsistencia = [];

                // Inicializa el array de dias (dia_1, dia_2, ...) con null por defecto
                for ($dia = 1; $dia <= $ultimoDiaMes; $dia++) {
                    $diasAsistencia["dia_$dia"] = null; // Valor por defecto
                }

                // Recorre los detalles de asistencia y llena los valores en los días correspondientes
                foreach ($empleado->detalles as $detalle) {
                    $fecha = Carbon::parse($detalle->fecha);
                    // Solo tomamos en cuenta los detalles que coincidan con el mes y año seleccionado
                    if ($fecha->month == $mes && $fecha->year == $anio) {
                        $diaKey = "dia_{$fecha->day}"; // Formato 'dia_1', 'dia_2', etc.
                        $diasAsistencia[$diaKey] = $detalle->horas_jornal; // O el campo que necesites
                    }
                }

                // Retorna los datos del empleado más los días mapeados
                return array_merge([
                    'orden' => $indice + 1,
                    'grupo' => $empleado->grupo,
                    'documento' => $empleado->documento,
                    'nombres' => $empleado->nombres,
                    'total_horas' => $empleado->total_horas,
                ], $diasAsistencia);
            })
            ->toArray();
    }
    public function obtenerDiasDelMes($anio, $mes)
    {
        // Obtener el número total de días del mes
        $totalDias = Carbon::createFromDate($anio, $mes, 1)->daysInMonth;

        // Crear un array desde el día 1 hasta el día final
        return range(1, $totalDias);

    }
    /*
    public function cargarInformacion()
    {
        $mes = $this->mes;
        $anio = $this->anio;
        $inicio = microtime(true);

        \DB::beginTransaction(); // Iniciar transacción
        try {
            // Buscar todos los reportes diarios del mes y año especificado
            $reportesDiarios = ReporteDiario::whereMonth('fecha', $mes)
                ->whereYear('fecha', $anio)
                ->orderBy('orden')
                ->get();

            //                dd($reportesDiarios->toArray());

            // Iterar sobre cada reporte diario
            foreach ($reportesDiarios as $reporte) {
                // Crear o actualizar PlanillaAsistencia
                $planillaAsistencia = PlanillaAsistencia::updateOrCreate(
                    [
                        'documento' => $reporte->documento,
                        'mes' => $mes,
                        'anio' => $anio,
                    ],
                    [
                        'grupo' => $reporte->tipo_trabajador,
                        'nombres' => $reporte->empleado_nombre,
                        'total_horas' => 0, // Inicialmente 0 (luego se sumará en decimal)
                    ]
                );

                //dd($planillaAsistencia);

                // Eliminar los detalles existentes asociados a esta PlanillaAsistencia
                PlanillaAsistenciaDetalle::where('planilla_asistencia_id', $planillaAsistencia->id)->delete();

                // Variable para acumular horas en formato decimal
                $totalHorasDecimal = 0;

                // Obtener el número de días del mes actual
                $diasEnMes = Carbon::create($anio, $mes)->daysInMonth;

                // Calcular y registrar detalles para cada día del mes
                for ($dia = 1; $dia <= $diasEnMes; $dia++) {
                    // Crear una fecha en base al mes y año
                    $fechaDia = Carbon::create($anio, $mes, $dia);

                    // Verificar si existe un reporte para ese día
                    $detalle = ReporteDiario::where('documento', $reporte->documento)
                        ->whereDate('fecha', $fechaDia)
                        ->first();

                    if ($detalle) {
                        // Registrar en PlanillaAsistenciaDetalle
                        PlanillaAsistenciaDetalle::create([
                            'planilla_asistencia_id' => $planillaAsistencia->id,
                            'fecha' => $detalle->fecha,
                            'tipo_asistencia' => $detalle->asistencia, // Asistencia o ausencia
                            'horas_jornal' => $this->convertirHorasADecimal($detalle->total_horas),
                        ]);

                        // Convertir el total_horas a decimal y sumarlo al total del mes
                        $totalHorasDecimal += $this->convertirHorasADecimal($detalle->total_horas);
                    }
                }

                // Actualizar el total de horas en formato decimal en PlanillaAsistencia
                $planillaAsistencia->total_horas = $totalHorasDecimal;
                $planillaAsistencia->save();
            }

            \DB::commit(); // Confirmar la transacción

            $this->obtenerEmpleados();
            $this->dispatch("setEmpleados",$this->empleados);
            $this->alert('success', 'Información cargada correctamente.');

        } catch (\Exception $e) {
            \DB::rollBack(); // Revertir la transacción en caso de error

            // Mostrar mensaje de error usando livewire-alert
            $this->alert('error', 'Error al cargar la información: ' . $e->getMessage());
        }

        $fin = microtime(true);

        // Calcular el tiempo total de ejecución en segundos
        $tiempoEjecucion = $fin - $inicio;

        // Mostrar el tiempo total de ejecución en la consola o como log
        \Log::info('Tiempo de ejecución: ' . $tiempoEjecucion . ' segundos');
    }*/
    public function cargarInformacion()
    {
        $mes = $this->mes;
        $anio = $this->anio;
        $inicio = microtime(true);
    
        \DB::beginTransaction(); // Iniciar transacción
        try {
            // Buscar todos los reportes diarios del mes y año especificado de una vez
            $reportesDiarios = ReporteDiario::whereMonth('fecha', $mes)
                ->whereYear('fecha', $anio)
                ->orderBy('orden')
                ->get();
    
            // Agrupar por documento
            $reportesAgrupados = $reportesDiarios->groupBy('documento');
    
            // Preparar arrays para inserts masivos
            $asistencias = [];
            $detalles = [];
            $planillaIds = [];
    
            // Obtener los documentos de los reportes
            $documentos = $reportesAgrupados->keys();
    
            // Eliminar los detalles existentes de todas las planillas que se van a procesar
            $planillaIds = PlanillaAsistencia::whereIn('documento', $documentos)
                ->where('mes', $mes)
                ->where('anio', $anio)
                ->pluck('id');
    
            PlanillaAsistenciaDetalle::whereIn('planilla_asistencia_id', $planillaIds)->delete();
    
            // Iterar sobre cada grupo de reportes (uno por documento)
            foreach ($reportesAgrupados as $documento => $reportes) {
                // Tomar el primer reporte para información de cabecera
                $primerReporte = $reportes->first();
    
                // Crear o actualizar PlanillaAsistencia
                $planillaAsistencia = PlanillaAsistencia::updateOrCreate(
                    [
                        'documento' => $documento,
                        'mes' => $mes,
                        'anio' => $anio,
                    ],
                    [
                        'grupo' => $primerReporte->tipo_trabajador,
                        'nombres' => $primerReporte->empleado_nombre,
                        'total_horas' => 0, // Inicialmente 0 (se sumará luego)
                    ]
                );
    
                // Preparar array para detalles
                $totalHorasDecimal = 0;
                $diasEnMes = Carbon::create($anio, $mes)->daysInMonth;
    
                // Procesar todos los días del mes
                for ($dia = 1; $dia <= $diasEnMes; $dia++) {
                    $fechaDia = Carbon::create($anio, $mes, $dia);
    
                    // Buscar reporte diario para este día
                    $detalle = $reportes->firstWhere('fecha', $fechaDia->toDateString());
    
                    if ($detalle) {
                        // Convertir horas y sumar al total
                        $horasDecimal = $this->convertirHorasADecimal($detalle->total_horas);
                        $totalHorasDecimal += $horasDecimal;
    
                        // Agregar al array de detalles
                        $detalles[] = [
                            'planilla_asistencia_id' => $planillaAsistencia->id,
                            'fecha' => $detalle->fecha,
                            'tipo_asistencia' => $detalle->asistencia, // Asistencia o ausencia
                            'horas_jornal' => $horasDecimal,
                        ];
                    }
                }
    
                // Actualizar el total de horas acumulado
                $planillaAsistencia->total_horas = $totalHorasDecimal;
                $planillaAsistencia->save();
            }
    
            // Insertar todos los detalles de una vez
            if (!empty($detalles)) {
                PlanillaAsistenciaDetalle::insert($detalles);
            }
    
            \DB::commit(); // Confirmar la transacción
    
            $this->obtenerEmpleados();
            $this->dispatch("setEmpleados", $this->empleados);
            $this->alert('success', 'Información cargada correctamente.');
        } catch (\Exception $e) {
            \DB::rollBack(); // Revertir la transacción en caso de error
            $this->alert('error', 'Error al cargar la información: ' . $e->getMessage());
        }
    
        $fin = microtime(true);
    
        // Calcular el tiempo total de ejecución en segundos
        $tiempoEjecucion = $fin - $inicio;
    
        // Mostrar el tiempo total de ejecución en la consola o como log
        \Log::info('Tiempo de ejecución: ' . $tiempoEjecucion . ' segundos');
    }
    /**
     * Función para convertir horas en formato H:i:s a formato decimal
     */
    protected function convertirHorasADecimal($hora)
    {
        list($horas, $minutos, $segundos) = explode(':', $hora);

        // Convertir minutos a fracción de horas
        $minutosDecimal = $minutos / 60;

        // El total en decimal será la suma de las horas + la fracción de los minutos
        return $horas + $minutosDecimal;
    }
    public function storeTableData($tableData)
    {
        $mes = $this->mes;
        $anio = $this->anio;
        // 1. Eliminar registros anteriores del mes y año seleccionado
        PlanillaAsistencia::where('mes', $mes)
            ->where('anio', $anio)
            ->delete();

        // 2. Reinsertar los nuevos valores
        foreach ($tableData as $row) {
            // Se ignoran las filas vacías
            if (empty($row['documento']) || empty($row['nombres'])) {
                continue;
            }

            // Sumar las horas trabajadas (ignora las celdas null)
            $totalHoras = 0;
            for ($dia = 1; $dia <= 31; $dia++) {
                $diaKey = $dia + 3;
                if (isset($row[$diaKey]) && is_numeric($row[$diaKey])) {
                    $totalHoras += $row[$diaKey];
                }
            }

            // Inserta o crea el registro en PlanillaAsistencia
            $planillaAsistencia = PlanillaAsistencia::create([
                'grupo' => $row[0],
                'documento' => $row[1],
                'nombres' => $row[2],
                'total_horas' => $totalHoras,
                'mes' => $mes,
                'anio' => $anio,
            ]);

            // 3. Insertar los detalles de asistencia si no son null
            for ($dia = 1; $dia <= 31; $dia++) {
                $diaKey = $dia + 3;
                if (isset($row[$diaKey]) && is_numeric($row[$diaKey])) {
                    // Calcular la fecha en función del día, mes y año
                    $fecha = Carbon::create($anio, $mes, $dia);

                    PlanillaAsistenciaDetalle::create([
                        'planilla_asistencia_id' => $planillaAsistencia->id,
                        'fecha' => $fecha,
                        'tipo_asistencia' => $this->obtenerTipoAsistencia($row, $diaKey), // Se obtiene de otra fuente
                        'horas_jornal' => $row[$diaKey],
                    ]);
                }
            }
        }

    }
    public function render()
    {
        return view('livewire.planilla-asistencia-detalle-component');
    }
}
