<?php

namespace App\Livewire\GestionCochinilla;

use App\Models\CochinillaVenteado;
use App\Services\Cochinilla\VenteadoServicio;
use DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class CochinillaVenteadoComponent extends Component
{
    use WithPagination,LivewireAlert;
    public $lote;
    public $anioSeleccionado;
    public $campoSeleccionado;
    public $aniosDisponibles = [];
    public $verLotesSinIngresos = false;
    protected $listeners = ["venteadoAgregado" => '$refresh','eliminarVenteadoConfirmado'];
    public function updatedLote()
    {
        $this->resetPage();
    }
    public function updatedCampoSeleccionado()
    {
        $this->resetPage();
    }
    public function updatedAnioSeleccionado()
    {
        $this->resetPage();
    }
    public function eliminarVenteado($id){
       $this->confirm('¿Estás seguro de eliminar este registro?', [
            'confirmButtonText' => 'Sí, eliminar',
            'cancelButtonText' => 'Cancelar',
            'onConfirmed' => 'eliminarVenteadoConfirmado',
            'data' => ['id' => $id],
        ]);
    }
    public function eliminarVenteadoConfirmado($data){
        $venteado = CochinillaVenteado::find($data['id']);
       
        if($venteado){
            $venteado->delete();
            $this->alert('success', 'Registro de venteado eliminado correctamente.');
            $this->resetPage();
        }else{
            $this->alert('error', 'El registro de venteado no existe.');
        }
    }
    public function render(VenteadoServicio $service)
    {
        $filtros = [
            'lote' => $this->lote,
            'anio' => $this->anioSeleccionado,
            'campo' => $this->campoSeleccionado,
        ];

        if ($this->verLotesSinIngresos) {
            $cochinillaVenteados = $service->paginarFiltradosHuerfanos($filtros);
            return view('livewire.gestion-cochinilla.cochinilla-venteados-huerfanos-component', [
                'cochinillaVenteados' => $cochinillaVenteados,
            ]);
        }

        $cochinillaIngresos = $service->paginarIngresosConVenteados($filtros);
        return view('livewire.gestion-cochinilla.cochinilla-venteado-component', [
            'cochinillaIngresos' => $cochinillaIngresos,
        ]);
    }
}
