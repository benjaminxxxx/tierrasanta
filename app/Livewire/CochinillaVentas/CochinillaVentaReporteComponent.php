<?php

namespace App\Livewire\CochinillaVentas;

use App\Livewire\Traits\ConFechaReporte;
use App\Models\VentaCochinilla;
use App\Services\Cochinilla\VentaServicio;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Session;

class CochinillaVentaReporteComponent extends Component
{
    use LivewireAlert;
    use ConFechaReporte;
    //Variables Existentes $mes,$anio
    public $condicionSugerencia = [];
    public $clienteSugerencia = [];
    public $datosParaReporte = [];
    public $puedeVincular = false;
    public $registroVinculado = false;
    public $totalVenta = 0;
    public $reporteCargado;
    public $totalVentaEntrega = 0;
    protected $listeners = ['storeTableAgruparPorIngresos', 'storeTableDataEnviarAContabilidad'];
    public function mount()
    {
        $this->cargarFechaDesdeSession();
        $this->cargarReporte();
        $this->condicionSugerencia = VentaCochinilla::query()
            ->select('condicion')
            ->distinct()
            ->whereNotNull('condicion')
            ->pluck('condicion')
            ->toArray();

        $this->clienteSugerencia = VentaCochinilla::query()
            ->select('cliente')
            ->distinct()
            ->whereNotNull('cliente')
            ->pluck('cliente')
            ->toArray();
    }
    public function updatedMes(){
        $this->cargarReporte();
    }
    public function updatedAnio(){
        $this->cargarReporte();
    }
    public function cargarReporte(){
        try {
            $this->reporteCargado = VentaServicio::obtenerReporte($this->mes,$this->anio);
            $this->datosParaReporte = [];
            $this->registroVinculado = false;
            $this->puedeVincular = false;
            $this->dispatch('cargarTabla', ['entregas' => $this->reporteCargado]);
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function obtenerParaReporte()
    {
        if (!$this->mes || !$this->anio) {
            return $this->alert('error', 'Debe seleccionar el mes y el año para generar la entrega.');
        }
        $this->datosParaReporte = VentaServicio::datosDeEntrega($this->mes, $this->anio);
        $this->registroVinculado = false;
        $this->puedeVincular = count($this->datosParaReporte) > 0;
        if($this->puedeVincular==0){
            $this->totalVentaEntrega = 0;
            return$this->alert('warning','No hay datos en el registro de entrega');
        }
        $this->totalVentaEntrega = $this->puedeVincular
        ? collect($this->datosParaReporte)->sum(fn ($item) => (float) ($item['proceso_cantidad_seca'] ?? 0))
        : 0;
        $this->dispatch('cargarTabla', ['entregas' => $this->datosParaReporte]);
    }
    public function cancelarGenerarReporte(){
        $this->puedeVincular = false;
        $this->totalVentaEntrega = 0;
        $this->datosParaReporte = [];
        $this->cargarReporte();
    }
    public function vincularIngreso()
    {
        try {
            $this->datosParaReporte = VentaServicio::vincularIngreso($this->datosParaReporte);
            $this->registroVinculado = true;
            $this->dispatch('cargarTabla', ['entregas' => $this->datosParaReporte]);
            $this->alert('success', 'Vinculación exitosa');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function storeTableAgruparPorIngresos($datos)
    {
        try {
            $this->datosParaReporte = VentaServicio::agruparPorIngreso($datos);
            $this->dispatch('cargarTabla', ['entregas' => $this->datosParaReporte]);
            $this->alert('success', 'Agrupación realizada exitosamente.');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    public function storeTableDataEnviarAContabilidad($datos)
    {
        try {
            $this->datosReporte = VentaServicio::registrarReporteVenta($datos,$this->mes,$this->anio);
            $this->dispatch('cargarTabla', ['entregas' => $this->datosReporte]);
            $this->alert('success', 'Agrupación realizada exitosamente.');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function render()
    {
        return view('livewire.cochinilla_ventas.reporte-component');
    }
}
