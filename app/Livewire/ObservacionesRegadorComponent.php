<?php

namespace App\Livewire;

use App\Models\Observacion;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ObservacionesRegadorComponent extends Component
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
    protected $listeners = ['asignarObservacionesHoras'];
    public function render()
    {
        if ($this->regador && $this->fecha) {
            $this->observaciones = Observacion::where('documento', $this->regador)->whereDate('fecha', $this->fecha)->get();
        }

        return view('livewire.observaciones-regador');
    }
    public function asignarObservacionesHoras($data)
    {
        $this->fecha = $data['fecha'];
        $this->regador = $data['regador'];
        $this->regadorNombre = $data['regadorNombre'];
        $this->tipoPersonal = $data['tipoPersonal'];
        $this->isFormOpen = true;
    }
    public function store()
    {
        $this->validate([
            'horas' => ['required', 'date_format:H:i'], // Valida que esté en formato 00:00 (hora:minuto)
            'observacion' => ['required'], // Campo requerido
        ], [
            'horas.required' => 'El campo horas es obligatorio.',
            'horas.date_format' => 'El formato de horas debe ser HH:MM (24 horas).',
            'observacion.required' => 'El campo observación es obligatorio.',
        ]);
    
        // Continuar con el proceso de guardado si la validación pasa
        $tipoPersonal = $this->tipoPersonal == 'cuadrilleros' ? 'cuadrilla' : 'planilla';
    
    
        try {
            // Intentar registrar la observación en la base de datos
            Observacion::create([
                'detalle_observacion' => $this->observacion,
                'horas' => $this->horas,
                'fecha' => $this->fecha,
                'documento' => $this->regador,
                'tipo_empleado' => $tipoPersonal,
            ]);
    
            // Mostrar un mensaje de éxito si la operación es exitosa
            $this->alert('success', 'Observaciones Registradas con Exito.');
            $this->horas = null;
            $this->observacion = null;
            $this->dispatch('Desconsolidar',$this->fecha);
    
        } catch (\Illuminate\Database\QueryException $e) {
            // Capturar la excepción de la base de datos y mostrar un mensaje de error
            $this->alert('error', 'Error al registrar las observaciones: ' . $e->getMessage());
        } catch (\Exception $e) {
            // Capturar cualquier otra excepción y mostrar un mensaje de error genérico
            $this->alert('error', 'Ha ocurrido un error inesperado: ' . $e->getMessage());
        }
    }
    public function eliminarObservacion($id)
    {
        $this->dispatch('Desconsolidar',$this->fecha);
        Observacion::find($id)->delete();
    }
    public function closeForm()
    {
        $this->isFormOpen = false;
        $this->dispatch('RefrescarMapa');
    }
}
