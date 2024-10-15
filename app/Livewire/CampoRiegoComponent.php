<?php

namespace App\Livewire;

use App\Models\Campo;
use App\Models\ConsolidadoRiego;
use App\Models\Cuadrillero;
use App\Models\Empleado;
use App\Models\ReporteDiarioRiego;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CampoRiegoComponent extends Component
{
    use LivewireAlert;
    public $campos;
    public $regadores;
    public $fecha;
    public $camposSeleccionados = [];
    public $regadorSeleccionado;
    public $estaConsolidado;
    protected $listeners = ['RefrescarMapa','desconsolidacion'=>'$refresh'];
    public function mount()
    {
        $this->fecha = Carbon::now()->format('Y-m-d');
        if($this->fecha){
            $this->regadores = ReporteDiarioRiego::whereDate('fecha',$this->fecha)->get()->pluck('regador','documento')->toArray();
        }
    }
    public function render()
    {
        $this->campos = Campo::all();
        return view('livewire.campo-riego-component');
    }

    public function verRiego()
    {
        if (!$this->regadorSeleccionado || !$this->fecha) {
            return;
        }
        $empleados = $this->regadores->pluck('nombre_completo', 'documento');

        $data = [
            'fecha' => $this->fecha,
            'regador' => $this->regadorSeleccionado,
            'regadorNombre' => $empleados[$this->regadorSeleccionado],
            'campos' => $this->camposSeleccionados,
            'tipoPersonal' => $this->tipoPersonal
        ];

        // Despachar el evento con los datos obtenidos
        $this->dispatch('asignarCargarRegadorHoras', $data);
    }
    public function verObservaciones()
    {
        if (!$this->regadorSeleccionado || !$this->fecha) {
            return;
        }
        $empleados = $this->regadores->pluck('nombre_completo', 'documento');

        $data = [
            'fecha' => $this->fecha,
            'regador' => $this->regadorSeleccionado,
            'regadorNombre' => $empleados[$this->regadorSeleccionado],
            'tipoPersonal' => $this->tipoPersonal
        ];

        // Despachar el evento con los datos obtenidos
        $this->dispatch('asignarObservacionesHoras', $data);
    }
}
