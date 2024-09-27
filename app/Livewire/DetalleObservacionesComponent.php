<?php

namespace App\Livewire;

use App\Models\HorasAcumuladas;
use App\Models\Observacion;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class DetalleObservacionesComponent extends Component
{
    use LivewireAlert;
    public $observaciones;
    public $isFormOpen;
    public $regadorNombre;
    public $tipoPersonal;
    public $horas;
    public $observacion;
    public $regador;
    public $fecha;
    public $horasAcumuladas;
    public $totalHorasAcumuladas;
    public $activarCopiarExcel;
    public $observacionesArray = [];
    public $originalObservacionesArray = [];
    public $cambiosRealizados = false;
    protected $listeners = ['RefrescarMapa' => '$refresh', 'desconsolidacion' => '$refresh'];
    public function mount()
    {
        if ($this->regador && $this->fecha) {
            $observaciones = Observacion::where('documento', $this->regador)
                ->whereDate('fecha', $this->fecha)
                ->get(['detalle_observacion', 'hora_inicio', 'hora_fin', 'horas']);

            // Convertir a array y formatear las horas
            $this->observacionesArray = $observaciones->map(function ($observacion) {
                return [
                    'detalle_observacion' => $observacion->detalle_observacion,
                    'hora_inicio' => !is_null($observacion->hora_inicio) ? substr($observacion->hora_inicio, 0, 5) : '00:00', // Recortar segundos de hora_inicio
                    'hora_fin' => !is_null($observacion->hora_fin) ? substr($observacion->hora_fin, 0, 5) : '00:00',       // Recortar segundos de hora_fin
                    'horas' => !is_null($observacion->horas) ? substr($observacion->horas, 0, 5) : '00:00',
                ];
            })->toArray();

            $this->originalObservacionesArray = $this->observacionesArray;
        }
    }

    public function render()
    {
        if ($this->regador && $this->fecha) {
            //$this->observaciones = Observacion::where('documento', $this->regador)->whereDate('fecha', $this->fecha)->get();

            $this->horasAcumuladas = $this->obtenerHorasAcumuladas($this->regador);
            $this->totalHorasAcumuladas = $this->obtenerTotalHorasAcumuladas($this->regador);
            $this->compararOriginal();

        }

        return view('livewire.detalle-observaciones-component');
    }
    public function compararOriginal()
    {
        $cambiosRealizados = $this->originalObservacionesArray !== $this->observacionesArray;
        $this->cambiosRealizados = $cambiosRealizados;
    }
    public function asignarObservacionesHoras($data)
    {
        $this->fecha = $data['fecha'];
        $this->regador = $data['regador'];
        $this->regadorNombre = $data['regadorNombre'];
        $this->tipoPersonal = $data['tipoPersonal'];
        $this->isFormOpen = true;
    }
    private function obtenerHorasAcumuladas($documento)
    {
        return HorasAcumuladas::where('documento', $documento)
            ->where(function ($query) {
                $query->whereNull('fecha_uso')
                    ->orWhereDate('fecha_uso', $this->fecha);
            })->get();
    }
    private function obtenerTotalHorasAcumuladas($documento)
    {
        $horasAcumuladas = HorasAcumuladas::where('documento', $documento)
            ->where('fecha_uso', $this->fecha)->get();
        if ($horasAcumuladas->count() > 0) {
            $totalMinutos = $horasAcumuladas->sum('minutos_acomulados');

            $horas = floor($totalMinutos / 60);
            $minutosRestantes = $totalMinutos % 60;
            return sprintf('%02d:%02d', $horas, $minutosRestantes);
        }
        return '00:00';
    }
    public function usarEstafecha($id)
    {
        $horaAcumulada = HorasAcumuladas::find($id);
        if ($horaAcumulada) {
            $horaAcumulada->fecha_uso = $this->fecha;
            $horaAcumulada->save();
            $this->dispatch('Desconsolidar', $this->fecha);
        }
    }
    public function noUsarEstafecha($id)
    {
        $horaAcumulada = HorasAcumuladas::find($id);
        if ($horaAcumulada) {
            $horaAcumulada->fecha_uso = null;
            $horaAcumulada->save();
            $this->dispatch('Desconsolidar', $this->fecha);
        }
    }

    public function store()
    {
        // Validar el formato de hora_inicio y hora_fin
        foreach ($this->observacionesArray as $observacion) {
            $horaInicio = \Carbon\Carbon::createFromFormat('H:i', $observacion['hora_inicio']);
            $horaFin = \Carbon\Carbon::createFromFormat('H:i', $observacion['hora_fin']);

            if ($horaInicio >= $horaFin) {
                $this->alert('error', 'Hora de inicio debe ser anterior a la hora de fin.');
                return;
            }

            // Calcular la diferencia en minutos y convertir a horas
            $diferenciaEnMinutos = $horaInicio->diffInMinutes($horaFin);
            $diferenciaEnHoras = sprintf('%02d:%02d', intdiv($diferenciaEnMinutos, 60), $diferenciaEnMinutos % 60);

            if ($diferenciaEnHoras !== $observacion['horas']) {
                $this->alert('error', 'Hubo un error en uno de los cálculos: ' . $diferenciaEnHoras . ' es diferente de: ' . $observacion['horas']);
                return;
            }
        }

        // Continuar con el proceso de guardado si la validación pasa
        $tipoPersonal = $this->tipoPersonal == 'cuadrilleros' ? 'cuadrilla' : 'planilla';


        try {

            // Eliminar las observaciones existentes para la fecha y documento especificados
            Observacion::whereDate('fecha', $this->fecha)->where('documento', $this->regador)->delete();

            // Insertar todas las observaciones en la base de datos
            foreach ($this->observacionesArray as $observacion) {
                Observacion::create([
                    'detalle_observacion' => $observacion['detalle_observacion'],
                    'hora_inicio' => $observacion['hora_inicio'],
                    'hora_fin' => $observacion['hora_fin'],
                    'horas' => $observacion['horas'],
                    'fecha' => $this->fecha,
                    'documento' => $this->regador,
                    'tipo_empleado' => $tipoPersonal,
                ]);
            }

            // Mostrar un mensaje de éxito si la operación es exitosa
            $this->alert('success', 'Observaciones Registradas con Exito.');
            $this->horas = null;
            $this->observacion = null;
            $this->originalObservacionesArray = $this->observacionesArray;
            $this->dispatch('Desconsolidar', $this->fecha);

        } catch (\Illuminate\Database\QueryException $e) {
            // Capturar la excepción de la base de datos y mostrar un mensaje de error
            $this->alert('error', 'Error al registrar las observaciones: ' . $e->getMessage());
        } catch (\Exception $e) {
            // Capturar cualquier otra excepción y mostrar un mensaje de error genérico
            $this->alert('error', 'Ha ocurrido un error inesperado: ' . $e->getMessage());
        }
    }
    public function agregarObservacion()
    {
        $this->observacionesArray[] = [
            'detalle_observacion' => '',
            'hora_inicio' => '',
            'hora_fin' => '',
            'horas' => ''
        ];
    }
    public function cancelarCambios()
    {
        $this->observacionesArray = $this->originalObservacionesArray;
        $this->cambiosRealizados = false;
    }
    public function eliminarObservacion($indice)
    {
        unset($this->observacionesArray[$indice]);
    }
}
