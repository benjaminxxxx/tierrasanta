<?php

namespace App\Livewire;

use App\Models\Kardex;
use Livewire\Component;

class KardexIndiceComponent extends Component
{
    public $kardexId;
    public $kardex;
    public $productosKardex = [];
    public function mount($kardexId){
        $this->kardexId = $kardexId;
        $this->kardex = Kardex::find($this->kardexId);
        if($this->kardex){
            $this->productosKardex = $this->kardex->productos()
            ->get()
            ->map(function($productoKardex){
                return [
                    'codigo_existencia'=>$productoKardex->codigo_existencia,
                    'nombre_comercial'=>$productoKardex->producto->nombre_comercial,
                    'tabla_6'=>$productoKardex->producto->tabla6_detalle ,
                ];
            });
        }
    }
    public function render()
    {
        return view('livewire.kardex-indice-component');
    }
}
