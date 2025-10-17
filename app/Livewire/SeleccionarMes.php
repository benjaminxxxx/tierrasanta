<?php

namespace App\Livewire;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class SeleccionarMes extends Component
{
    public $mes;
    public $anio;

    public function mount($mes = null, $anio = null)
    {
        // Recuperar de sesión si existe
        $this->mes = $mes ?? Session::get('mes_trabajo', now()->format('m'));
        $this->anio = $anio ?? Session::get('anio_trabajo', now()->year);
        $this->dispatch('mes-actualizado', mes: $this->mes, anio: $this->anio);
    }

    public function mesAnterior()
    {
        $fecha = Carbon::createFromDate($this->anio, $this->mes, 1)->subMonth();
        $this->mes = $fecha->format('m');
        $this->anio = $fecha->year;
        $this->guardarYEmitir();
    }

    public function mesSiguiente()
    {
        $fecha = Carbon::createFromDate($this->anio, $this->mes, 1)->addMonth();
        $this->mes = $fecha->format('m');
        $this->anio = $fecha->year;
        $this->guardarYEmitir();
    }

    public function updatedMes()
    {
        $this->guardarYEmitir();
    }

    public function updatedAnio()
    {
        $this->guardarYEmitir();
    }

    private function guardarYEmitir()
    {
        // Guardar en sesión
        Session::put('mes_trabajo', $this->mes);
        Session::put('anio_trabajo', $this->anio);

        // Emitir para otros componentes o para wire:model
        $this->dispatch('mes-actualizado', mes: $this->mes, anio: $this->anio);
    }

    public function render()
    {
        return view('livewire.seleccionar-mes');
    }
}
