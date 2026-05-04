<?php

namespace App\Livewire\CochinillaVentas;

use App\Services\Cochinilla\VentaServicio;
use App\Traits\Selectores\ConSelectorMes;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class CochinillaVentaRegistroEntregaComponent extends Component
{
    use ConSelectorMes;
    use LivewireAlert;
    use WithPagination;
    public $totalVenta;
    protected $listeners = ['registroEntregaVentaExitoso'];
    public function mount(){
        $this->inicializarMesAnio();
    }
    protected function despuesMesAnioModificado($anio,$mes){
        $this->resetPage(); // Reiniciar a la primera página al cambiar mes o año
    }
    public function eliminarEntrega($grupoVenta){
        try {
            
            VentaServicio::eliminarRegistroEntrega($grupoVenta);
            $this->alert('success','Detalle de registro de venta eliminado correctamente.');
        } catch (\Throwable $th) {
            $this->alert('error',$th->getMessage());
        }
    }
    public function registroEntregaVentaExitoso()
    {
        $this->alert('success', 'Registro de entrega de venta exitoso');
        $this->resetPage();
    }
    public function render()
    {
        $filtros = [
            'anio_venta' => $this->anio,
            'mes_venta' => $this->mes,
        ];

        $resultado = VentaServicio::listarParaEntregadorPaginado($filtros);
        $registroEntregas = $resultado['paginado'];
        $this->totalVenta = $resultado['total_venta'];

        return view('livewire.cochinilla_ventas.registro-entrega-component', [
            'registroEntregas' => $registroEntregas
        ]);
    }
}
