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
    protected $listeners = ['RefrescarMapa'=>'$refresh','desconsolidacion'=>'$refresh'];
    public function render()
    {
        if ($this->regador && $this->fecha) {
            $this->observaciones = Observacion::where('documento', $this->regador)->whereDate('fecha', $this->fecha)->get();
            $this->horasAcumuladas = $this->obtenerHorasAcumuladas($this->regador);
            $this->totalHorasAcumuladas = $this->obtenerTotalHorasAcumuladas($this->regador);
        }

        return view('livewire.detalle-observaciones-component');
    }
    public function asignarObservacionesHoras($data)
    {
        $this->fecha = $data['fecha'];
        $this->regador = $data['regador'];
        $this->regadorNombre = $data['regadorNombre'];
        $this->tipoPersonal = $data['tipoPersonal'];
        $this->isFormOpen = true;
    }
    private function obtenerHorasAcumuladas($documento){
        return HorasAcumuladas::where('documento', $documento)
        ->where(function ($query) {
            $query->whereNull('fecha_uso')
                  ->orWhereDate('fecha_uso', $this->fecha);
        })->get();
    }
    private function obtenerTotalHorasAcumuladas($documento){
        $horasAcumuladas = HorasAcumuladas::where('documento', $documento)
        ->where('fecha_uso', $this->fecha)->get();
        if($horasAcumuladas->count()>0){
            $totalMinutos = $horasAcumuladas->sum('minutos_acomulados');

            $horas = floor($totalMinutos/ 60);
            $minutosRestantes = $totalMinutos % 60;
            return sprintf('%02d:%02d', $horas, $minutosRestantes);
        }
        return '00:00';
    }
    public function usarEstafecha($id){
        $horaAcumulada = HorasAcumuladas::find($id);
        if($horaAcumulada){
            $horaAcumulada->fecha_uso = $this->fecha;
            $horaAcumulada->save();
            $this->dispatch('Desconsolidar',$this->fecha);
        }
    }
    public function noUsarEstafecha($id){
        $horaAcumulada = HorasAcumuladas::find($id);
        if($horaAcumulada){
            $horaAcumulada->fecha_uso = null;
            $horaAcumulada->save();
            $this->dispatch('Desconsolidar',$this->fecha);
        }
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
}
