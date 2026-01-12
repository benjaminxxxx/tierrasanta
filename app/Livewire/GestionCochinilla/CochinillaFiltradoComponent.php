<?php

namespace App\Livewire\GestionCochinilla;

use App\Models\CochinillaFiltrado;
use App\Services\Cochinilla\FiltradoServicio;
use DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class CochinillaFiltradoComponent extends Component
{
    use WithPagination, WithoutUrlPagination,LivewireAlert;
    public $lote;
    public $anioSeleccionado;
    public $campoSeleccionado;
    public $aniosDisponibles = [];
    public $verLotesSinIngresos = false;
    protected $listeners = ["filtradoAgregado" => '$refresh','eliminarFiltradoConfirmado'];
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
    public function eliminarFiltrado($id){
        $this->confirm('¿Estás seguro de eliminar este registro de filtrado?', [
            'confirmButtonText' => 'Sí, eliminar',
            'cancelButtonText' => 'Cancelar',
            'onConfirmed' => 'eliminarFiltradoConfirmado',
            'data' => ['id' => $id],
        ]);
    }
    public function eliminarFiltradoConfirmado($data){
        $filtrado = CochinillaFiltrado::find($data['id']);
       
        if($filtrado){
            $filtrado->delete();
            $this->alert('success', 'Registro de filtrado eliminado correctamente.');
            $this->resetPage();
        }else{
            $this->alert('error', 'El registro de filtrado no existe.');
        }
    }
    public function render(FiltradoServicio $service)
    {
        $filtros = [
            'lote' => $this->lote,
            'anio' => $this->anioSeleccionado,
            'campo' => $this->campoSeleccionado,
        ];

        if ($this->verLotesSinIngresos) {
            $cochinillaFiltrados = $service->paginarFiltradosHuerfanos($filtros);
            return view('livewire.gestion-cochinilla.cochinilla-filtrado-huerfanos-component', [
                'cochinillaFiltrados' => $cochinillaFiltrados,
            ]);
        }
        
        $cochinillaIngresos = $service->paginarIngresosConFiltrados($filtros);
        return view('livewire.gestion-cochinilla.cochinilla-filtrado-component', [
            'cochinillaIngresos' => $cochinillaIngresos,
        ]);
    }
}
