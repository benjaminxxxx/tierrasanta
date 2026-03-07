<?php

namespace App\Livewire\GestionPlanilla;

use App\Models\Configuracion;
use App\Models\ConfiguracionHistorial;
use App\Services\Configuracion\ConfiguracionHistorialProceso;
use App\Services\Configuracion\ConfiguracionHistorialServicio;
use Dotenv\Exception\ValidationException;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class ParametrosPlanillaComponent extends Component
{
    use LivewireAlert;
    public $parametros = [];
    public $configuraciones = [];
    public function mount()
    {
        
        $this->refrescarTablaParametros(false);
        $this->configuraciones = Configuracion::get()->pluck('codigo')->toArray();
    }
    public function refrescarTablaParametros($dispatch=true){
        $this->parametros = ConfiguracionHistorialServicio::leer();
        if($dispatch){
            $this->dispatch('refrescarTablaParametros',parametros:$this->parametros);
        }
    }
    public function guardarParametros($data)
    {
        try {

            ConfiguracionHistorialProceso::ejecutar($data);
            $this->refrescarTablaParametros();
            $this->alert('success', 'Parámetros de planilla sincronizados correctamente.');
        } catch (ValidationException $th) {
            $this->alert('error', $th->getMessage());
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function render()
    {
        return view('livewire.gestion-planilla.parametros-planilla-component');
    }
}
