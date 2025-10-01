<?php

namespace App\Livewire\GestionCuadrilla;
use App\Services\Cuadrilla\CuadrilleroServicio;
use App\Services\Cuadrilla\TramoLaboralServicio;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class GestionCuadrillaGastosAdicionalesComponent extends Component
{
    use LivewireAlert;

    public $mostrarFormularioGastosAdicionales = false;
    public $tramoLaboral;
    public $inicio;
    public $grupos = [];
    public $gastos = [];
    public $fechaDefault;
    protected $listeners = ['abrirGastosAdicionales'];
    public function mount($tramoId)
    {
        $this->tramoLaboral = app(TramoLaboralServicio::class)->encontrarTramoPorId($tramoId);
        $this->obtenerGrupos();
    }
    public function obtenerGrupos(){
        $this->grupos = $this->tramoLaboral->grupos()->get()->pluck('nombre')->toArray();
    }
    public function abrirGastosAdicionales()
    {
        $this->obtenerGrupos();
        $inicio = $this->tramoLaboral->fecha_inicio;
        $fin = $this->tramoLaboral->fecha_fin;

        $this->mostrarFormularioGastosAdicionales = true;
        $this->gastos = CuadrilleroServicio::listarHandsontableGastosAdicionales($inicio, $fin);
        

        $inicio = Carbon::parse($inicio);
        $fin = Carbon::parse($fin);
        $hoy = Carbon::today();
        $fechaDefault = null;

        if ($inicio->equalTo($fin)) {
            $fechaDefault = $inicio;
        } elseif ($hoy->between($inicio, $fin)) {
            $fechaDefault = $hoy;
        } else {
            $fechaDefault = $inicio;
        }
        $this->fechaDefault = $fechaDefault->toDateString();
        $this->gastos[] = [
            'grupo' => '',
            'descripcion' => '',
            'fecha' => $this->fechaDefault,
            'monto' => ''
        ];
    }
    public function storeTableDataGuardarDatosAdicionales($datos)
    {

        try {
            CuadrilleroServicio::guardarGastosAdicionalesXGrupo($this->tramoLaboral->id, $datos, $this->tramoLaboral->fecha_inicio, $this->tramoLaboral->fecha_fin);
            $this->mostrarFormularioGastosAdicionales = false;
            $this->alert('success', 'Los gastos adicionales han sido registrados.');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function render()
    {
        return view('livewire.gestion-cuadrilla.gestion-cuadrilla-gastos-adicionales-component');
    }
}