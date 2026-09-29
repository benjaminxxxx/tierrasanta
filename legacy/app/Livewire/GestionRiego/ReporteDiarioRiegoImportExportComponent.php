<?php

namespace App\Livewire\GestionRiego;
use App\Exports\RiegosExport;
use App\Models\Campo;
use App\Models\Cuadrillero;
use App\Models\PlanEmpleado;
use App\Models\ReporteDiarioRiego;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Storage as FacadesStorage;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Maatwebsite\Excel\Facades\Excel;
use Storage;

class ReporteDiarioRiegoImportExportComponent extends Component
{
    use WithFileUploads;
    use LivewireAlert;
    public $archivoBackupHoy;
    public $fecha;
    protected $listeners = ["RDRIE_descargarPorFecha"=> "descargarPorFecha","RDRIE_descargarBackupCompleto"=> "descargarBackupCompleto"];
   
    public function updatedArchivoBackupHoy()
    {
        //codigo verificado, este es el oficial
        if ($this->archivoBackupHoy) {
            
            try {
                // Validar el archivo
                $this->validate([
                    'archivoBackupHoy' => 'required|file|mimes:xlsx,xls,csv|max:2048',
                ]);

                // Importar el archivo
                // Leer el archivo usando PHPSpreadsheet
                $spreadsheet = IOFactory::load($this->archivoBackupHoy->getRealPath());
                
                $fecha = $this->fecha;
                $this->processDetalleRiegoSheet($spreadsheet, $fecha);
                //$this->processAsignacionFamiliarSheet($spreadsheet);

                // Mostrar una alerta de éxito
                $this->alert('success', 'Los datos se Restauraron correctamente.');
                
                $this->dispatch('generalActualizado');
            } catch (Exception $e) {
                // Manejar excepciones
                $this->alert('error', 'Hubo un error al importar los datos: ' . $e->getMessage());
            }
        }
    }
    protected function processDetalleRiegoSheet($spreadsheet, $fechasolicitada = null)
    {
        $sePuedeProcesar = false;

        $sheet = $spreadsheet->getSheetByName('ReporteDiarioRiego');
        if (!$sheet) {
            throw new Exception("La Hoja DetalleRiego dentro del archivo No existe, usar la plantilla correcta");
        }
        
        $rows = $sheet->toArray();

        // Validar los datos antes de hacer el backup y la eliminación
        foreach ($rows as $index => $row) {
            
            if ($index === 0) {
                // Omitir la primera fila (encabezados)
                continue;
            }
            
            $fecha = $row[0] ?? null;
            $documento = $row[1] ?? null;
            $campo = $row[3] ?? null;
            $hora_inicio = $row[4] ?? '00:00';
            $hora_fin = $row[5] ?? '00:00';
            $total_horas = $row[6] ?? '00:00';
            $tipo_labor = $row[7] ?? '';
            $descripcion = $row[8] ?? '';
            $sh = $row[9] ? (mb_strtoupper($row[9])=='SI'?1:0):0;
            
            
            if(!$fecha){
                throw new Exception("La fila {$index} no contiene una fecha válida.");
            }

            $hora_inicio = trim($hora_inicio);
            $hora_fin = trim($hora_fin);
            $total_horas = trim($total_horas);
            $total_horas_formatted = substr($total_horas, 0, 5);
            $hora_inicio_formatted = substr($hora_inicio, 0, 5);
            $hora_fin_formatted = substr($hora_fin, 0, 5);
            
            $horaInicio = null;
            $horaFin = null;
            $totalHoras = null;
            try {
                // Intentar crear con el formato H:i
                $horaInicio = \Carbon\Carbon::createFromFormat('H:i', $hora_inicio_formatted);
            } catch (Exception $e) {
                throw new Exception("El formato de horas en horaInicio es incorrecto en la fila {$index}.");
            }
            try {
                // Intentar crear con el formato H:i
                $horaFin = \Carbon\Carbon::createFromFormat('H:i', $hora_fin_formatted);
            } catch (Exception $e) {
                throw new Exception("El formato de horas en horaFin es incorrecto en la fila {$index}.");
            }
            try {
                // Intentar crear con el formato H:i
                $totalHoras = \Carbon\Carbon::createFromFormat('H:i', $total_horas_formatted);
            } catch (Exception $e) {
                throw new Exception("El formato de horas en horasTotal es incorrecto en la fila {$index}.");
            }


            // Validar que el DNI existe en Empleado o Cuadrilla
            $empleado = PlanEmpleado::where('documento', $documento)->first();
            $cuadrilla = Cuadrillero::where('dni', $documento)->first();

            $fechaExcel = Carbon::parse($fecha);
            $fechasolicitada = Carbon::parse($fechasolicitada);
            $fecha = $fechaExcel->format('Y-m-d');
            
            
            // Comparar ambas fechas formateadas a "Y-m-d"
            if (!$fechaExcel->equalTo($fechasolicitada)) {
                throw new Exception("La fecha {$fechaExcel->format('Y-m-d')} no coincide con la fecha a Restaurar en la fila {$index}.");
            }

            if (!$empleado && !$cuadrilla) {
                throw new Exception("DNI {$documento} no encontrado en la fila {$index}.");
            }

            // Validar que el campo existe
            $campoExistente = Campo::where('nombre', $campo)->first();
            if (!$campoExistente) {
                throw new Exception("Campo '{$campo}' no encontrado en la fila {$index}.");
            }

            // Validar que hora_fin sea mayor que hora_inicio
            if ($hora_inicio && $hora_fin) {

                if ($horaInicio >= $horaFin) {
                    throw new Exception("La hora de inicio debe ser anterior a la hora de fin en la fila {$index}.");
                }

                $diferenciaMinutos = $horaInicio->diffInMinutes($horaFin);
                $totalHorasCalculadas = sprintf('%02d:%02d', intdiv($diferenciaMinutos, 60), $diferenciaMinutos % 60);

                if ($totalHoras->format('H:i') !== $totalHorasCalculadas) {
                    throw new Exception("El cálculo de horas es incorrecto en la fila {$index}. Se esperaba {$totalHorasCalculadas} y se Obtuvo {$totalHoras}.");
                }
            }

            $sePuedeProcesar = true;
        }

        if(!$sePuedeProcesar){
            throw new Exception("No hay información Válida para Procesar.");
        }
        // Si todas las validaciones pasan, hacer el backup
        if ($fechasolicitada) {
            try {
                // Obtener los datos de detalle_riego para la fecha especificada
                $dataBackup = ReporteDiarioRiego::whereDate('fecha', $fechasolicitada)->get();
            
                // Convertir los datos a un array asociativo
                $dataArray = $dataBackup->toArray();
            
                // Generar un nombre de archivo con la fecha actual y un identificador único
                $timestamp = now()->format('Ymd-His'); // Formato de fecha y hora
                $filename = "{$timestamp}-detalleriego.json"; // Nombre del archivo
            
                // Guardar los datos en formato JSON en el disco 'public'
                FacadesStorage::disk('public')->put("data/backup/{$filename}", json_encode($dataArray, JSON_PRETTY_PRINT));
            
            
            } catch (\Throwable $th) {
                // Lanzar el error capturado
                throw $th;
            }

            // Eliminar los registros existentes para la fecha especificada
            ReporteDiarioRiego::whereDate('fecha', $fecha)->delete();
        }

        $fechas = [];

        foreach ($rows as $index => $row) {
            if ($index === 0) {
                // Omitir la primera fila (encabezados)
                continue;
            }
            

            $fecha = $row[0] ?? null;
            $documento = $row[1] ?? null;
            $regador = $row[2] ?? null;
            $campo = $row[3] ?? null;
            $hora_inicio = $row[4] ?? null;
            $hora_fin = $row[5] ?? null;
            $total_horas = $row[6] ?? null;

            $tipo_labor = $row[7] ?? '';
            $descripcion = $row[8] ?? '';
            $sh = $row[9] ? (mb_strtoupper($row[9])=='SI'?1:0):0;
            
            $fechaExcel = Carbon::parse($fecha);
            $fecha = $fechaExcel->format('Y-m-d');

            if(!isset($fechas[$fecha])){
                $fechas[$fecha] = [];
            }

            $fechas[$fecha][$documento] = true;

            ReporteDiarioRiego::create([
                'fecha' => $fecha,
                'documento' => $documento,
                'regador' => $regador,
                'campo' => $campo,
                'hora_inicio' => $hora_inicio,
                'hora_fin' => $hora_fin,
                'total_horas' => $total_horas,
                'tipo_labor' => $tipo_labor,
                'descripcion' => $descripcion,
                'sh'=>$sh
            ]);
        }
       
        $this->dispatch('consolidarRegadorMasivo',$fechas);
    }
    public function descargarPorFecha($fecha)
    {
        if (!$fecha) {
            return;
        }

        return Excel::download(new RiegosExport($fecha), $fecha . '_DetalleRiego.xlsx');
    }
    public function descargarBackupCompleto()
    {
        return Excel::download(new RiegosExport, 'DetalleRiego.xlsx');
    }
    public function render()
    {
        return view('livewire.reporte-diario-riego-import-export-component');
    }
}
