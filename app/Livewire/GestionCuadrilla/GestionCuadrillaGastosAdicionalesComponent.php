<?php

namespace App\Livewire\GestionCuadrilla;
use App\Models\CuaGrupo;
use App\Models\Grupo;
use App\Services\Cuadrilla\CuadrilleroServicio;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class GestionCuadrillaGastosAdicionalesComponent extends Component
{
    use LivewireAlert;

    public $mostrarFormularioGastosAdicionales = false;
    public $inicio;
    public $rangoDias = 7;
    public $grupos = [];
    protected $listeners = ['abrirGastosAdicionales'];
    public function mount()
    {
        $this->grupos = CuaGrupo::where('estado',true)->get()->pluck('nombre')->toArray();
    }
    public function abrirGastosAdicionales($inicio)
    {
        $this->inicio = $inicio;
        $this->fin = Carbon::parse($inicio)->addDays($this->rangoDias);

        $this->mostrarFormularioGastosAdicionales = true;
        $listaGastosAdicionales = CuadrilleroServicio::listarHandsontableGastosAdicionales($this->inicio,$this->fin);
        $this->dispatch('cargarGastosAdicionales', $listaGastosAdicionales);
    }
    public function storeTableDataGuardarDatosAdicionales($datos){
     
        try {
            CuadrilleroServicio::guardarGastosAdicionalesXGrupo($datos,$this->inicio,$this->rangoDias);
            $this->alert('success','Los gastos adicionales han sido registrados.');
        } catch (\Throwable $th) {
            $this->alert('error',$th->getMessage());
        }
    }
    public function render()
    {
        return view('livewire.gestion-cuadrilla.gestion-cuadrilla-gastos-adicionales-component');
    }
}