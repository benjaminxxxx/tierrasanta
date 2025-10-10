<?php

namespace App\Livewire;

use App\Models\Campo;
use App\Models\DetalleRiego;
use App\Models\PlanEmpleado;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class AsignarRiegoComponent extends Component
{
    use LivewireAlert;
    public $horasPorRegador = [];
    public $isFormOpen;
    public $regadores;
    public $regador;
    public $fecha;
    public $campos;
    public $activarCopiarExcel;
    public $informacionExcel;
    public $tipoPersonal;
    public $regadorNombre;
    protected $listeners = ['abrirRiego', 'asignarCargarRegadorHoras'];
    public function mount()
    {
        $this->regadores = PlanEmpleado::orderBy('apellido_paterno')->orderBy('apellido_materno')->orderBy('nombres')->get();
    }
    public function render()
    {
        return view('livewire.asignar-riego-component');
    }
    public function store()
    {
        if (!$this->regador) {
            return $this->alert('error', 'Selecciona el regador primero');
        }

        if (!$this->fecha) {
            return $this->alert('error', 'Digite alguna fecha válida');
        }

        // Eliminar registros existentes para el regador y la fecha
        DetalleRiego::where('regador', $this->regador)
            ->where('fecha', $this->fecha)
            ->delete();

        // Si activarCopiarExcel está activado, procesamos los datos de informacionExcel
        $campos = [];
        if ($this->activarCopiarExcel) {
            // Procesar información desde Excel
            if($this->informacionExcel){
                $lineas = explode("\n", trim($this->informacionExcel)); // Separar por líneas
                foreach ($lineas as $linea) {
                    $datos = preg_split('/\s+/', trim($linea)); // Separar por espacios o tabulaciones
                    if (count($datos) === 4) { // Solo procesar líneas con 4 columnas (campo, hora_inicio, hora_fin, total_horas)
                        $campos[] = [
                            'nombre' => $datos[0],
                            'inicio' => $datos[1],
                            'fin' => $datos[2],
                            'total' => $datos[3],
                        ];
                    }
                }
            }
            
        } else {
            // Si no está activado, usamos los campos manuales
            $campos = $this->campos;
        }

        // Procesar cada campo y guardar los datos
        if (is_array($campos) && count($campos) > 0) {
            foreach ($campos as $campo) {
                if ($campo['nombre'] != null && trim($campo['nombre']) != '') {
                    $campoModel = Campo::find(trim($campo['nombre']));

                    if ($campoModel && isset($campo['inicio'], $campo['fin'])) {
                        try {
                            // Convertir las horas de inicio y fin a objetos Carbon
                            $horaInicio = Carbon::createFromFormat('H:i', $campo['inicio']);
                            $horaFin = Carbon::createFromFormat('H:i', $campo['fin']);

                            // Validar que la hora de fin sea mayor que la hora de inicio
                            if ($horaFin <= $horaInicio) {
                                throw new \Exception('La hora de fin debe ser mayor que la hora de inicio.');
                            }

                            // Calcular la diferencia de horas en formato HH:MM (si no está precalculada en total)
                            $totalHoras = isset($campo['total']) && trim($campo['total']) != '' ?
                                $campo['total'] :
                                $horaInicio->diff($horaFin)->format('%H:%I');

                            // Crear el nuevo registro de riego
                            DetalleRiego::create([
                                'campo' => $campoModel->nombre,
                                'regador' => $this->regador,
                                'fecha' => $this->fecha,
                                'hora_inicio' => $horaInicio->format('H:i'),
                                'hora_fin' => $horaFin->format('H:i'),
                                'total_horas' => $totalHoras,
                            ]);
                        } catch (\Exception $e) {
                            return $this->alert('error', $e->getMessage());
                        }
                    }
                }
            }

            $this->activarCopiarExcel = false;
            $this->informacionExcel = null;
            

            $this->alert('success', 'Registro de Horas de Riego Exitoso.');
            $this->closeForm();
        } else {

            $this->activarCopiarExcel = false;
            $this->informacionExcel = null;
            $this->alert('success', 'Se limpiaron todos los registros.');
            $this->closeForm();
        }
    }


    public function eliminarIndice($indice)
    {
        unset($this->campos[$indice]);
    }

    public function abrirRiego($data)
    {

        $this->fecha = $data['fecha'];
        $this->regador = $data['regador'];
        $this->campos = $data['campos'];

        $this->isFormOpen = true;
    }
    public function asignarCargarRegadorHoras($data)
    {
        $this->fecha = $data['fecha'];
        $this->regador = $data['regador'];
        $this->regadorNombre = $data['regadorNombre'];
        $this->campos = $data['campos'];
        $this->tipoPersonal = $data['tipoPersonal'];
        $this->cargarRegadorHoras();
    }
    
    public function cargarRegadorHoras()
    {
        if (!$this->regador || !$this->fecha) {
            return;
        }

        // Obtener los registros de riego según la fecha y el regador
        $detalleRiegos = DetalleRiego::where('regador', $this->regador)
            ->whereDate('fecha', $this->fecha) // Filtrar por la fecha actual
            ->get();

        // Convertir los detalles obtenidos en el formato deseado
        $camposExistentes = $detalleRiegos->map(function ($riego) {
            return [
                'nombre' => $riego->campo,
                'inicio' => Carbon::parse($riego->hora_inicio)->format('H:i'), // Formato de hora
                'fin' => Carbon::parse($riego->hora_fin)->format('H:i'), // Formato de hora
                'total' => Carbon::parse($riego->total_horas)->format('H:i'), // Formato de total horas
            ];
        })->keyBy('nombre')->toArray(); // Usar 'keyBy' para indexar por nombre del campo

        // Merge con los campos seleccionados
        foreach ($this->campos as $campo) {
            if ($campo['nombre'] != null && trim($campo['nombre']) != '') {
                // Si el campo ya existe en los registros existentes, actualizarlo
                $camposExistentes[$campo['nombre']] = [
                    'nombre' => $campo['nombre'],
                    'inicio' => $campo['inicio'],
                    'fin' => $campo['fin'],
                    'total' => $campo['total'],
                ];
            }
        }

        // Convertir el array de campos existentes a una colección para pasar al evento
        $camposSeleccionados = array_values($camposExistentes);

        // Preparar los datos para despachar el evento
        $data = [
            'fecha' => $this->fecha,
            'regador' => $this->regador,
            'campos' => $camposSeleccionados,
        ];

        $this->abrirRiego($data);
    }

    public function closeForm()
    {
        $this->campos = [];
        $this->regador = null;
        $this->isFormOpen = false;
        $this->dispatch('Desconsolidar',$this->fecha);
        $this->dispatch('RefrescarMapa');
    }
    public function clacularTotal($nombreCampo)
    {
        $key = null;
        foreach ($this->campos as $index => $campo) {
            if ($campo['nombre'] === $nombreCampo) {
                $key = $index;
                break;
            }
        }

        if ($key !== null) {
            $inicio = $this->campos[$key]['inicio'] ?? null;
            $fin = $this->campos[$key]['fin'] ?? null;

            if ($inicio && $fin) {
                try {
                    $hora_inicio = new \DateTime($inicio);
                    $hora_fin = new \DateTime($fin);
                    $intervalo = $hora_inicio->diff($hora_fin);
                    $this->campos[$key]['total'] = $intervalo->format('%H:%I');
                } catch (\Exception $e) {
                    // Manejar errores en el formato de tiempo
                    $this->campos[$key]['total'] = '00:00';
                    \Log::error('Error al calcular el total: ' . $e->getMessage());
                }
            } else {
                // Si falta alguno de los tiempos, dejar el total en blanco o en un valor predeterminado
                $this->campos[$key]['total'] = '00:00';
            }
        }


    }
}
