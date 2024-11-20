<?php

namespace App\Livewire;

use App\Models\Kardex;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class KardexFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;
    public $tipo_kardex;
    public $fecha_inicial;
    public $fecha_final;
    public $nombre;
    protected $listeners = ['crearKardex'];
    protected $rules = [
        "nombre"=>"required|string",
        "fecha_inicial"=>"required|date",
        "fecha_final"=>"required|date",
        "tipo_kardex"=>"required",
    ];
    protected $messages = [
        "nombre.required"=>"El nombre es requerido",
        "tipo_kardex.required"=>"El tipo de Kardex es requerido",
        "fecha_inicial.required"=>"La fecha inicial es requerido",
        "fecha_inicial.date"=>"La fecha inicial no tiene un formato válido",
        "fecha_final.required"=>"La fecha final es requerido",
        "fecha_final.date"=>"La fecha final no tiene un formato válido",
    ];
    public function mount()
    {
        $this->tipo_kardex = 'blanco';
    }
    public function crearKardex()
    {
        $this->resetForm();
        $this->mostrarFormulario = true;
    }
    public function storeKardexForm(){

        $data = $this->validate();
        
        try {            
            
            Kardex::create($data);
            $this->dispatch("kardexRegistrado");
            $this->resetForm();
            $this->mostrarFormulario = false;
            $this->alert("success","Registro de Kardex exitoso");
        } catch (\Throwable $th) {
            $this->alert("error",$th->getMessage());
        }
    }
    public function resetForm(){
        $this->resetErrorBag();
        $this->reset(['nombre','fecha_inicial']);
    }
    public function render()
    {
        return view('livewire.kardex-form-component');
    }
}
