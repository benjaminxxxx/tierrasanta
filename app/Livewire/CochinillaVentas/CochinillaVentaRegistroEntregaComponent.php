<?php

namespace App\Livewire\CochinillaVentas;

use App\Models\VentaCochinilla;
use App\Services\Cochinilla\VentaServicio;
use DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class CochinillaVentaRegistroEntregaComponent extends Component
{
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
    protected $listeners = ['registroEntregaVentaExitoso'];
    public function registroEntregaVentaExitoso(){
        $this->alert('success','Registro de entrega de venta exitoso');
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
            'anio_venta' => $this->filtroAnioVenta,
            'mes_venta' => $this->filtroMesVenta,
        ];

        $registroEntregas = VentaServicio::listarParaEntregadorPaginado($filtros);

        return view('livewire.cochinilla_ventas.registro-entrega-component', [
            'registroEntregas' => $registroEntregas
        ]);
    }
}
