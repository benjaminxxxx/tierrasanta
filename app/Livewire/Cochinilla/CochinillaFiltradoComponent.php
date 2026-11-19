<?php

namespace App\Livewire\Cochinilla;

use App\Models\CochinillaFiltrado;
use App\Services\Cochinilla\FiltradoServicio;
use DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class CochinillaFiltradoComponent extends Component
{
    use WithPagination, WithoutUrlPagination,LivewireAlert, \App\Traits\HandlesAlerts, \App\Livewire\Traits\ConCorreccionFilaCochinilla;
    private const CLASE_FILA = CochinillaFiltrado::class;
    public $lote;
    public $anioSeleccionado;
    public $campoSeleccionado;
    public $aniosDisponibles = [];
    public $verLotesSinIngresos = false;
    protected $listeners = ["filtradoAgregado" => '$refresh'];
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
    /** Fila suelta: eliminar o corregir su lote pide motivo y queda en auditoría */
    public function eliminarFiltrado($id)
    {
        $this->abrirCorreccion((int) $id, 'eliminar');
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
            return view('livewire.cochinilla.cochinilla-filtrado-huerfanos-component', [
                'cochinillaFiltrados' => $cochinillaFiltrados,
            ]);
        }
        
        $cochinillaIngresos = $service->paginarIngresosConFiltrados($filtros);
        return view('livewire.cochinilla.cochinilla-filtrado-component', [
            'cochinillaIngresos' => $cochinillaIngresos,
        ]);
    }
}
