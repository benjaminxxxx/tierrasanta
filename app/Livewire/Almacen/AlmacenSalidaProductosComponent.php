<?php

namespace App\Livewire\Almacen;

use App\Traits\Selectores\ConSelectorMes;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class AlmacenSalidaProductosComponent extends Component
{
    use LivewireAlert, ConSelectorMes;
    public $destino;
    public function mount($mes = null, $anio = null, $destino = 'productos')
    {
        $this->destino = $destino;
        $this->inicializarMesAnio();
    }
    protected function despuesMesAnioModificado(string $mes, string $anio)
    {
    }
    public function render()
    {
        return view('livewire.almacen.almacen-salida-productos-component');
    }
}
