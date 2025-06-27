<?php

namespace App\Livewire\CochinillaVentas;

use App\Livewire\Traits\ConFechaReporte;
use App\Models\VentaCochinilla;
use App\Services\Cochinilla\VentaServicio;
use DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class CochinillaVentaRegistroEntregaComponent extends Component
{
    use ConFechaReporte;
    use LivewireAlert;
    use WithPagination;
    // Filtros para búsqueda
    public $filtroCampo;
    public $filtroCliente;
    public $filtroCondicion;
    public $filtroFecha;         // formato Y-m-d
    public $filtroAnio;
    public $filtroMes;
    public $filtroFechaVenta;    // formato Y-m-d
    public $filtroAnioVenta;
    public $filtroMesVenta;
    public $totalVenta;
    protected $listeners = ['registroEntregaVentaExitoso'];
    public function mount(){
        $this->cargarFechaDesdeSession();
    }
    
    public function registroEntregaVentaExitoso()
    {
        $this->alert('success', 'Registro de entrega de venta exitoso');
    }
    public function render()
    {
        $filtros = [
            'campo' => $this->filtroCampo,
            'cliente' => $this->filtroCliente,
            'condicion' => $this->filtroCondicion,
            'fecha_filtrado' => $this->filtroFecha,
            'anio_filtrado' => $this->filtroAnio,
            'mes_filtrado' => $this->filtroMes,
            'fecha_venta' => $this->filtroFechaVenta,
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
