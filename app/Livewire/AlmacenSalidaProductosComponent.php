<?php

namespace App\Livewire;

use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class AlmacenSalidaProductosComponent extends Component
{
    use LivewireAlert;
    public $anio;
    public $mes;
    public $destino;
    public function mount($mes = null, $anio = null,$destino = 'productos')
    {
        $this->mes = $mes ? $mes : Carbon::now()->format('m');
        $this->anio = $anio ? $anio : Carbon::now()->format('Y');
        $this->destino = $destino;
    }
    
    public function mesAnterior()
    {
        $fecha = Carbon::createFromDate($this->anio, $this->mes, 1)->subMonth();
        $this->mes = $fecha->format('m');
        $this->anio = $fecha->format('Y');
    }

    public function mesSiguiente()
    {
        $fecha = Carbon::createFromDate($this->anio, $this->mes, 1)->addMonth();
        $this->mes = $fecha->format('m');
        $this->anio = $fecha->format('Y');
    }
   
    public function render()
    {        
        return view('livewire.almacen-salida-productos-component');
    }
}
