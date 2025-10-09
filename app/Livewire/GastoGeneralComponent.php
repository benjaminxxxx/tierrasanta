<?php

namespace App\Livewire;

use App\Models\CompraProducto;
use App\Models\GastoAdicionalPorGrupoCuadrilla;
use Carbon\Carbon;
use Exception;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class GastoGeneralComponent extends Component
{
    use LivewireAlert;
    public $mes, $anio;
    public $pagoCuadrilleros;
    public $compraCombustibleNegro = 0;
    public $compraCombustibleBlanco = 0;
    public $compraInsumosNegro = 0;
    public $compraInsumosBlanco = 0;
    public $gastosCuadrilla = 0;
    public function mount()
    {
        $this->mes = Carbon::now()->month;
        $this->anio = Carbon::now()->year;
        $this->cargarGastos();
    }
    public function updatedAnio()
    {
        $this->cargarGastos();
    }
    public function updatedMes()
    {
        $this->cargarGastos();
    }
    public function cargarGastos()
    {
        try {
            if (!$this->mes) {
                throw new Exception("No se ha elegido el mes");
            }
            if (!$this->anio) {
                throw new Exception("No se ha elegido el año");
            }

            $inicioMes = Carbon::createFromDate($this->anio, $this->mes, 1)->startOfMonth();
            $finMes = Carbon::createFromDate($this->anio, $this->mes, 1)->endOfMonth();


            $this->pagoCuadrilleros = 0;
            $this->compraCombustibleNegro = CompraProducto::calcularCompras($this->mes, $this->anio, 'negro', true);
            $this->compraCombustibleBlanco = CompraProducto::calcularCompras($this->mes, $this->anio, 'blanco', true);
            $this->compraInsumosNegro = CompraProducto::calcularCompras($this->mes, $this->anio, 'negro', false);
            $this->compraInsumosBlanco = CompraProducto::calcularCompras($this->mes, $this->anio, 'blanco', false);
            $this->gastosCuadrilla = GastoAdicionalPorGrupoCuadrilla::where('anio_contable', $this->anio)->where('mes_contable', $this->mes)->sum('monto');

        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Ha ocurrido un error alcargar los gastos.');
        }
    }
    public function render()
    {
        return view('livewire.gasto-general-component');
    }
}
