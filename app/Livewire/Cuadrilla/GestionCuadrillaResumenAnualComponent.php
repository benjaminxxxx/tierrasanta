<?php

namespace App\Livewire\Cuadrilla;

use App\Services\Cuadrilla\Resumen\CuadrillaResumenAnualConsulta;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

/** Cuadrilla → Resumen anual: jornales y bonos por mes, en vivo desde el registro diario. */
class GestionCuadrillaResumenAnualComponent extends Component
{
    public $aniosDisponibles = [];
    public $anioSeleccionado = null;
    public $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

    public function mount()
    {
        $this->aniosDisponibles = range(\App\Services\Sistema\Vigencia\SistemaVigenciaReglas::anioMinimo(2017), now()->year);
        $this->anioSeleccionado = (int) Session::get('anio', now()->year);
    }

    public function updatedAnioSeleccionado()
    {
        $this->dispatch('actualizarGraficoCuadrilla', array_values($this->resumen()['meses']));
    }

    private function resumen(): array
    {
        return app(CuadrillaResumenAnualConsulta::class)->delAnio((int) $this->anioSeleccionado);
    }

    public function render()
    {
        $resumen = $this->resumen();

        return view('livewire.cuadrilla.gestion-cuadrilla-resumen-anual-component', [
            'resumenMensual' => array_values($resumen['meses']),
            'totales' => $resumen['totales'],
        ]);
    }
}
