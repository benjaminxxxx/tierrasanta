<?php

namespace App\Livewire;

use App\Models\Cuadrillero;
use App\Models\Empleado;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CuadrillaDesdeEmpleadosComponent extends Component
{
    use LivewireAlert;
    public $empleados;
    public $mostrarFormulario = false;
    public $empleadosSeleccionados = [];
    protected $listeners = ["registrarCuadrilleroDePlanilla"];
    public function registrarCuadrilleroDePlanilla(){
        $this->empleados = Empleado::orderBy('apellido_paterno')
        ->orderBy('apellido_materno')
        ->orderBy('nombres')
        ->get();
        $this->mostrarFormulario = true;
    }
    public function registrarSeleccionados(){
        $dataAgregados = [];
        if(is_array($this->empleadosSeleccionados) && count($this->empleadosSeleccionados)>0){
            foreach ($this->empleadosSeleccionados as $empleadoId) {
                $empleado = Empleado::find($empleadoId);
                if($empleado){
                    
                    $cuadrillero = Cuadrillero::where('dni',$empleado->documento)->first();
                    if(!$cuadrillero){
                        $nuevoCuadrillero = Cuadrillero::Create([
                            'nombres'=>$empleado->nombreCompleto,
                            'dni'=>$empleado->documento,
                        ]);
                        $dataAgregados[] = $nuevoCuadrillero->id;
                    }else{
                        $dataAgregados[] = $cuadrillero->id;
                    }
                }
                
            }
            $this->dispatch('cuadrilleroRegistradoDeEmpleados',$dataAgregados);
            $this->mostrarFormulario = false;
            $this->empleados = null;
            $this->empleadosSeleccionados = [];
            $this->alert('success','Registros agregados correctamente');
        }else{
            $this->alert('error','Debe seleccionar al menos 1 registro');
        }
    }
    public function seleccionarEmpleado($empleadoId)
    {
        if (in_array($empleadoId, $this->empleadosSeleccionados)) {
            // Si ya está seleccionado, lo removemos del array
            $this->empleadosSeleccionados = array_diff($this->empleadosSeleccionados, [$empleadoId]);
        } else {
            // Si no está seleccionado, lo agregamos
            $this->empleadosSeleccionados[] = $empleadoId;
        }
    }
    public function render()
    {
        return view('livewire.cuadrilla-desde-empleados-component');
    }
}
