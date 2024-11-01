<?php

namespace App\Livewire;

use App\Models\Cuadrillero;
use App\Models\GruposCuadrilla;
use Illuminate\Database\QueryException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CuadrillaFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;

    public $nombres;
    public $dni;
    public $cuadrilleroId = null;

    protected $listeners = ["registrarCuadrillero",'editarCuadrillero'];
   
    public function registrar(){
        $this->validate([
            'nombres' => 'required|string|max:255',
            'dni' => 'required|string|max:20|unique:cuadrilleros,dni,' . $this->cuadrilleroId, // Valida DNI único si es un nuevo registro
        ],[
            'nombres.required' => 'El nombre es obligatorio',
            'dni.required' => 'El dni es obligatorio',
            'dni.unique' => 'El dni ya esta siendo utilizado',
        ]);

        $data = [
            'nombres' => mb_strtoupper($this->nombres),
            'dni' => $this->dni,
        ];

        $cuadrillero = null;

        try {
            if ($this->cuadrilleroId) {
                // Actualizar registro existente
                $cuadrillero = Cuadrillero::where('id', $this->cuadrilleroId)->update($data);
                $this->alert('success','Cuadrillero actualizado exitosamente.');
            } else {
                // Crear nuevo registro
                $cuadrillero = Cuadrillero::create($data);
                $this->alert('success','Cuadrillero registrado exitosamente.');
            }

            // Cerrar el formulario y resetear campos
            
            $this->mostrarFormulario = false;
            $this->resetForm();
            $this->dispatch('cuadrilleroRegistrado',$cuadrillero);
        } catch (QueryException $e) {
            $this->alert('error','Hubo un error al guardar el cuadrillero: ' . $e->getMessage());
        }
    }
    public function registrarCuadrillero(){
        $this->resetForm();
        $this->mostrarFormulario = true;
    }
    public function editarCuadrillero($cuadrilleroId){
        $this->resetForm();
        $cuadrillero = Cuadrillero::find($cuadrilleroId);
        if($cuadrillero){
            $this->cuadrilleroId = $cuadrilleroId;
            $this->nombres = $cuadrillero->nombres;
            $this->dni = $cuadrillero->dni;
            $this->mostrarFormulario = true;
        }
        
    }
    
    private function resetForm()
    {
        
        $this->resetErrorBag();
        $this->cuadrilleroId = null;
        $this->nombres = '';
        $this->dni = '';
    }
    public function render()
    {
        return view('livewire.cuadrilla-form-component');
    }
}
