<?php

namespace App\Livewire\CochinillaVentas;

use App\Livewire\Traits\ConFechaReporte;
use App\Livewire\Traits\ListaCampos;
use App\Models\VentaCochinilla;
use App\Services\Cochinilla\VentaServicio;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Session;

class CochinillaVentaFacturadaComponent extends Component
{
    use LivewireAlert;
    use ConFechaReporte;
    use ListaCampos;
    public $ventasFacturadas = [];
    protected $listeners = ['storeTableDataRegistroVentas'];

    public function mount()
    {
        $this->cargarCampos();
        $this->cargarFechaDesdeSession();
        $this->cargarReporte();
    }
    public function updatedMes($valor)
    {
        $this->actualizarSesionMes($valor);
        $this->cargarReporte();
    }
    public function updatedAnio($valor)
    {
        $this->actualizarSesionAnio($valor);
        $this->cargarReporte();
    }
    public function actualizarDesdeReporte(){
         try {
            $ventasFacturadasDesdeReporte = VentaServicio::listarVentasPorAnioYMesMasReporte($this->anio, $this->mes);
            $this->dispatch('cargarTabla', ['ventas' => $ventasFacturadasDesdeReporte]);
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function cargarReporte()
    {
        try {
            $this->ventasFacturadas = VentaServicio::listarVentasPorAnioYMes($this->anio, $this->mes);
            $this->dispatch('cargarTabla', ['ventas' => $this->ventasFacturadas]);
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function storeTableDataRegistroVentas($datos)
    {
        try {
            VentaServicio::registrarVentasPorMes($this->mes, $this->anio, $datos);
            $this->cargarReporte();
            $this->alert('success', 'Ventas registradas correctamente');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function render()
    {
        return view('livewire.cochinilla_ventas.venta-facturada-component');
    }
}
