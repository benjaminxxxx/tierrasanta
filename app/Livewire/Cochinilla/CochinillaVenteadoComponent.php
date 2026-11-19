<?php

namespace App\Livewire\Cochinilla;

use App\Models\CochinillaVenteado;
use App\Services\Cochinilla\VenteadoServicio;
use DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class CochinillaVenteadoComponent extends Component
{
    use WithPagination,LivewireAlert, \App\Traits\HandlesAlerts, \App\Livewire\Traits\ConCorreccionFilaCochinilla;
    private const CLASE_FILA = CochinillaVenteado::class;
    public $lote;
    public $anioSeleccionado;
    public $campoSeleccionado;
    public $aniosDisponibles = [];
    public $verLotesSinIngresos = false;
    protected $listeners = ["venteadoAgregado" => '$refresh'];
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
    public function eliminarVenteado($id)
    {
        $this->abrirCorreccion((int) $id, 'eliminar');
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
            return view('livewire.cochinilla.cochinilla-venteados-huerfanos-component', [
                'cochinillaVenteados' => $cochinillaVenteados,
            ]);
        }

        $cochinillaIngresos = $service->paginarIngresosConVenteados($filtros);
        return view('livewire.cochinilla.cochinilla-venteado-component', [
            'cochinillaIngresos' => $cochinillaIngresos,
        ]);
    }
}
