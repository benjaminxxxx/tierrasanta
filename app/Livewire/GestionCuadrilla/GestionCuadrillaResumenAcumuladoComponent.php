<?php

namespace App\Livewire\GestionCuadrilla;

use App\Models\CuadTramoLaboral;
use App\Services\Cuadrilla\ResumenTramoServicio;
use App\Services\Cuadrilla\TramoLaboralServicio;
use App\Traits\HandlesAlerts;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class GestionCuadrillaResumenAcumuladoComponent extends Component
{
    use LivewireAlert;
    use HandlesAlerts;
    public $tramoLaboralId;
    public $resumenes = [];
    public $fechaHastaBono;
    protected $listeners = ["abrirResumenAcumuladoCuadrilla"];
    public bool $mostrarCuadroResumenCuadrilleroSemanal = false;

    public function mount($tramoLaboralId,$fechaHastaBono)
    {
        $this->tramoLaboralId = $tramoLaboralId;
        $this->fechaHastaBono = $fechaHastaBono;
        $this->obtenerResumen();
        //$this->tramoLaboral = app(TramoLaboralServicio::class)->encontrarTramoPorId($tramoId);
    }
    public function obtenerResumen(){
        $this->resumenes = app(ResumenTramoServicio::class)->obtenerResumen($this->tramoLaboralId)->toArray();
    }
    public function abrirResumenAcumuladoCuadrilla(ResumenTramoServicio $servicio)
    {
        $this->mostrarCuadroResumenCuadrilleroSemanal = true;
    }

    public function recalcularResumenTramo(ResumenTramoServicio $servicio)
    {
        try {
            CuadTramoLaboral::where('id',$this->tramoLaboralId)->update(['fecha_hasta_bono'=>$this->fechaHastaBono]);
            $servicio->regenerar($this->tramoLaboralId); // borra lo anterior y reconstruye
            $this->obtenerResumen();
            $this->successAlert('Resumen actualizado correctamente.');
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }
    public function render()
    {
        return view('livewire.gestion-cuadrilla.gestion-cuadrilla-resumen-acumulado-component');
    }
}
