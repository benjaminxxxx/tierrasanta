<?php

namespace App\Livewire;

use App\Exports\RiegosExport;
use App\Models\Campo;
use App\Models\ConsolidadoRiego;
use App\Models\Cuadrillero;
use App\Models\DetalleRiego;
use App\Models\Empleado;
use App\Models\HorasAcumuladas;
use App\Models\Observacion;
use Carbon\Carbon;
use Exception;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Storage;

class DetalleRiegoComponent extends Component
{
    use LivewireAlert;
    use WithFileUploads;
    public $fecha;
    public $regadores;
    public $tipoPersonal;
    public $riegos = [];
    public $regadorSeleccionado;
    public $estaConsolidado;
    public $consolidados;
    public $search;
    public $archivoBackupHoy;
    protected $listeners = ['RefrescarMapa' => '$refresh', 'desconsolidacion' => '$refresh'];
    public function mount()
    {
        $this->fecha = (new \DateTime('now'))->format('Y-m-d');
        $this->tipoPersonal = 'regadores';

        $this->obtenerRiegos();

        // 6. Depuración para ver el resultado
        //dd($this->riegos);
    }

    private function obtenerRiegos()
    {
        if (!$this->fecha) {
            return;
        }
        //where $this->search when diferente de vacio o null
        $this->consolidados = ConsolidadoRiego::whereDate('fecha', $this->fecha)->get();
        /* $this->consolidados = ConsolidadoRiego::whereDate('fecha', $this->fecha)
         ->where(function ($query) {
             $query->where('regador_nombre', 'like', '%' . $this->search . '%');
         })
         ->get();
 */


        if ($this->consolidados->count() != 0) {
            return;
        }

        // 1. Cargar todos los regadores por defecto
        $regadores = Empleado::where('cargo_id', 'RIE')
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombres')
            ->get()
            ->map(function ($empleado) {
                return [
                    'nombre_completo' => $empleado->apellido_paterno . ' ' . $empleado->apellido_materno . ', ' . $empleado->nombres,
                    'documento' => $empleado->documento,
                ];
            });

        // 2. Consultar en DetalleRiego, Observacion, y HorasAcumuladas para obtener documentos únicos

        // De DetalleRiego
        $detalleRiegoDocumentos = DetalleRiego::whereDate('fecha', $this->fecha)
            ->pluck('regador'); // El campo 'regador' es el documento en DetalleRiego

        // De Observacion
        $observacionDocumentos = Observacion::whereDate('fecha', $this->fecha)
            ->pluck('documento'); // El campo 'documento' es el DNI en Observacion

        // De HorasAcumuladas
        $horasAcumuladasDocumentos = HorasAcumuladas::whereDate('fecha_uso', $this->fecha)
            ->pluck('documento'); // El campo 'documento' es el DNI en HorasAcumuladas

        // 3. Unir todos los documentos en una colección
        $todosDocumentos = $detalleRiegoDocumentos
            ->merge($observacionDocumentos)
            ->merge($horasAcumuladasDocumentos)
            ->unique(); // Evitar duplicados de documentos

        // 4. Procesar todos los documentos recolectados
        $otrosEmpleados = $todosDocumentos->map(function ($documento) {
            return [
                'nombre_completo' => $this->obtenerNombreRegador($documento), // Usar la función para obtener nombre
                'documento' => $documento,
            ];
        });

        // 5. Hacer merge con los regadores, evitando duplicados basados en 'documento'
        $this->riegos = $regadores->merge($otrosEmpleados)
            ->unique('documento') // Evitar duplicados basados en el campo 'documento'
            ->pluck('nombre_completo', 'documento')
            ->toArray();

        foreach ($this->riegos as $documento => $nombreRegador) {
            $consolidado = ConsolidadoRiego::where('regador_documento', $documento)->whereDate('fecha', $this->fecha)->first();

            if (!$consolidado) {
                $this->crearConsolidado($documento);
            }
        }

        $this->consolidados = ConsolidadoRiego::whereDate('fecha', $this->fecha)->get();
    }
    private function crearConsolidado($documento)
    {

        $nombreRegador = $this->obtenerNombreRegador($documento);

        ConsolidadoRiego::create([
            'regador_documento' => $documento,
            'regador_nombre' => $nombreRegador,
            'fecha' => $this->fecha,
            'hora_inicio' => null,
            'hora_fin' => null,
            'total_horas_riego' => 0,
            'total_horas_observaciones' => 0,
            'total_horas_acumuladas' => 0,
            'total_horas_jornal' => 0,
            'estado' => 'noconsolidado',
        ]);
    }
    public function render()
    {
        $consolidadoExiste = ConsolidadoRiego::where('fecha', $this->fecha)->first();
        if ($consolidadoExiste) {
            $this->estaConsolidado = ConsolidadoRiego::where('fecha', $this->fecha)->where('estado', 'noconsolidado')->exists() ? false : true;
        } else {
            $this->estaConsolidado = false;
        }

        $documentosAgregados = array_keys(ConsolidadoRiego::where('fecha', $this->fecha)->pluck('regador_documento', 'regador_documento')->toArray());

        if ($this->tipoPersonal) {

            switch ($this->tipoPersonal) {
                case 'empleados':
                    $this->regadores = Empleado::whereNotIn('documento', $documentosAgregados)
                        ->orderBy('apellido_paterno')
                        ->orderBy('apellido_materno')
                        ->orderBy('nombres')
                        ->get()
                        ->map(function ($empleado) {
                            return [
                                'nombre_completo' => $empleado->apellido_paterno . ' ' . $empleado->apellido_materno . ', ' . $empleado->nombres,
                                'documento' => $empleado->documento,
                            ];
                        });
                    break;

                case 'cuadrilleros':
                    $this->regadores = Cuadrillero::whereNotIn('dni', $documentosAgregados)
                        ->whereNotNull('dni')
                        ->orderBy('nombre_completo')
                        ->get(['dni as documento', 'nombre_completo'])
                        ->map(function ($cuadrillero) {
                            return [
                                'nombre_completo' => $cuadrillero->nombre_completo,
                                'documento' => $cuadrillero->documento,
                            ];
                        });
                    break;

                default:
                    $this->regadores = Empleado::where('cargo_id', 'RIE')
                        ->whereNotIn('documento', $documentosAgregados)
                        ->orderBy('apellido_paterno')
                        ->orderBy('apellido_materno')
                        ->orderBy('nombres')
                        ->get()
                        ->map(function ($empleado) {
                            return [
                                'nombre_completo' => $empleado->apellido_paterno . ' ' . $empleado->apellido_materno . ', ' . $empleado->nombres,
                                'documento' => $empleado->documento,
                            ];
                        });
                    break;
            }

        }
        return view('livewire.detalle-riego-component');
    }
    public function consolidar()
    {
        $this->dispatch('ConsolidarRegadores', $this->fecha);
    }
    public function agregarDetalle()
    {
        /*
                if (!array_key_exists($this->regadorSeleccionado, $this->riegos)) {
                    $this->riegos[$this->regadorSeleccionado] = $this->obtenerNombreRegador($this->regadorSeleccionado);
                    
                }*/
        if (!$this->regadorSeleccionado) {
            return $this->alert('error', 'Debe seleccionar un Regador');
        }
        $this->crearConsolidado($this->regadorSeleccionado);
        $this->regadorSeleccionado = null;
        $this->obtenerRiegos();
    }
    private function obtenerNombreRegador($documento)
    {
        return optional(Empleado::where('documento', $documento)->first())->nombre_completo
            ?? Cuadrillero::where('dni', $documento)->value('nombre_completo')
            ?? 'NN';
    }
    public function updatedFecha()
    {
        $this->obtenerRiegos();
    }
    public function fechaAnterior()
    {
        // Restar un día a la fecha seleccionada
        $this->fecha = \Carbon\Carbon::parse($this->fecha)->subDay()->format('Y-m-d');
        $this->obtenerRiegos();
    }

    public function fechaPosterior()
    {
        // Sumar un día a la fecha seleccionada
        $this->fecha = \Carbon\Carbon::parse($this->fecha)->addDay()->format('Y-m-d');
        $this->obtenerRiegos();
    }
    public function descargarBackup()
    {
        if (!$this->fecha) {
            return;
        }

        return Excel::download(new RiegosExport($this->fecha), $this->fecha . '_DetalleRiego.xlsx');
    }
    public function descargarBackupCompleto()
    {
        return Excel::download(new RiegosExport, 'DetalleRiego.xlsx');
    }
    public function updatedArchivoBackupHoy()
    {


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

        $sheet = $spreadsheet->getSheetByName('DetalleRiego');
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
            $hora_inicio = $row[4] ?? null;
            $hora_fin = $row[5] ?? null;
            $total_horas = $row[6] ?? null;
            
            
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
            } catch (\Exception $e) {
                throw new Exception("El formato de horas en horaInicio es incorrecto en la fila {$index}.");
            }
            try {
                // Intentar crear con el formato H:i
                $horaFin = \Carbon\Carbon::createFromFormat('H:i', $hora_fin_formatted);
            } catch (\Exception $e) {
                throw new Exception("El formato de horas en horaFin es incorrecto en la fila {$index}.");
            }
            try {
                // Intentar crear con el formato H:i
                $totalHoras = \Carbon\Carbon::createFromFormat('H:i', $total_horas_formatted);
            } catch (\Exception $e) {
                throw new Exception("El formato de horas en horasTotal es incorrecto en la fila {$index}.");
            }


            // Validar que el DNI existe en Empleado o Cuadrilla
            $empleado = Empleado::where('documento', $documento)->first();
            $cuadrilla = Cuadrillero::where('dni', $documento)->first();

            if ($fecha != $fechasolicitada) {
                throw new Exception("La fecha {$fecha} no coincide con la fecha a Restaurar en la fila {$index}.");
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
                $dataBackup = DetalleRiego::whereDate('fecha', $fechasolicitada)->get();
            
                // Convertir los datos a un array asociativo
                $dataArray = $dataBackup->toArray();
            
                // Generar un nombre de archivo con la fecha actual y un identificador único
                $timestamp = now()->format('Ymd-His'); // Formato de fecha y hora
                $filename = "{$timestamp}-detalleriego.json"; // Nombre del archivo
            
                // Guardar los datos en formato JSON en el disco 'public'
                Storage::disk('public')->put("data/backup/{$filename}", json_encode($dataArray, JSON_PRETTY_PRINT));
            
            
            } catch (\Throwable $th) {
                // Lanzar el error capturado
                throw $th;
            }

            // Eliminar los registros existentes para la fecha especificada
            DetalleRiego::whereDate('fecha', $fecha)->delete();
        }

        // Procesar los datos a partir de la segunda fila (índice 1)
        foreach ($rows as $index => $row) {
            if ($index === 0) {
                // Omitir la primera fila (encabezados)
                continue;
            }
            

            $fecha = $row[0] ?? null;
            $documento = $row[1] ?? null;
            $campo = $row[3] ?? null;
            $hora_inicio = $row[4] ?? null;
            $hora_fin = $row[5] ?? null;
            $total_horas = $row[6] ?? null;
            $sh = isset($row[7])?mb_strtoupper($row[7])=='SI'?1:0:0;
            
            // Procesar e insertar los datos
            DetalleRiego::create([
                'fecha' => $fecha,
                'regador' => $documento,
                'campo' => $campo,
                'hora_inicio' => $hora_inicio,
                'hora_fin' => $hora_fin,
                'total_horas' => $total_horas,
                'sh'=>$sh
            ]);


        }
    
    }


}
