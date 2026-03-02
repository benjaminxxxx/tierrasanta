<?php

namespace App\Livewire;

use App\Models\RptDistribucionCombustible;
use App\Traits\Selectores\ConSelectorMes;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Illuminate\Support\Facades\Session;

class AlmacenSalidaProductosComponent extends Component
{
    use LivewireAlert, ConSelectorMes;
    public $destino;
    public $reporteMensualCombustible;
    protected $listeners = ['rptDistribucionesGeneradas'];
    public function mount($mes = null, $anio = null, $destino = 'productos')
    {
        $this->destino = $destino;
        $this->inicializarMesAnio();
        $this->obtenerReporte();
    }
    protected function despuesMesAnioModificado(string $mes, string $anio)
    {
        $this->obtenerReporte();
    }
    public function rptDistribucionesGeneradas()
    {
        $this->alert('success', 'Reporte generado exitosamente.');
        $this->obtenerReporte();
    }
    public function obtenerReporte()
    {
        $this->reporteMensualCombustible = RptDistribucionCombustible::where('mes', $this->mes)
            ->where('anio', $this->anio)
            ->first();
    }
    public function render()
    {
        return view('livewire.almacen-salida-productos-component');
    }
}
