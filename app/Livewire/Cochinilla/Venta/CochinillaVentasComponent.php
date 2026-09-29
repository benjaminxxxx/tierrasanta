<?php

namespace App\Livewire\Cochinilla\Venta;

use Livewire\Attributes\Title;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Session;

#[Title('Ventas')]
class CochinillaVentasComponent extends Component
{
    public function render()
    {
        return view('livewire.cochinilla.venta.cochinilla-ventas-component');
    }
}
