<?php

namespace App\Livewire\CochinillaVentas;

use App\Livewire\Traits\ListaCampos;
use App\Services\Cochinilla\VentaServicio;
use App\Traits\Selectores\ConSelectorMes;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Session;

class CochinillaVentaFacturadaComponent extends Component
{
    use LivewireAlert;
    use ConSelectorMes;
    use ListaCampos;
    public $ventasFacturadas = [];
    protected $listeners = ['storeTableDataRegistroVentas'];

    public function mount()
    {
        $this->cargarCampos();
        $this->inicializarMesAnio();
        $this->cargarReporte();
    }
    protected function despuesMesAnioModificado($anio, $mes)
    {
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
