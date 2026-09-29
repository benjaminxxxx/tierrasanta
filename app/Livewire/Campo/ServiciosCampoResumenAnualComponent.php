<?php

namespace App\Livewire\Campo;

use App\Services\Campo\ServicioCampoServicio;
use Livewire\Component;

class ServiciosCampoResumenAnualComponent extends Component
{
    public $anio;
    public $tipoCosto = '';
    public $resumen = [];

    protected $listeners = ['serviciosCampoActualizados' => 'actualizarResumen'];

    public function mount()
    {
        $this->anio = now()->year;
        // Sin dispatch: el gráfico inicial lo dibuja Alpine en init() con estos datos.
        $this->cargarResumen();
    }

    public function updatedAnio()
    {
        $this->actualizarResumen();
    }

    public function updatedTipoCosto()
    {
        $this->actualizarResumen();
    }

    public function actualizarResumen()
    {
        $this->cargarResumen();
        $this->dispatch('actualizarGraficoServiciosCampo', resumen: $this->resumen);
    }

    private function cargarResumen(): void
    {
        $this->resumen = app(ServicioCampoServicio::class)->resumenAnual((int) $this->anio, $this->tipoCosto ?: null);
    }

    public function render()
    {
        return view('livewire.campo.servicios-campo-resumen-anual-component');
    }
}
