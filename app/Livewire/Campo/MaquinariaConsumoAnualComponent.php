<?php

namespace App\Livewire\Campo;

use App\Services\Campo\Maquinaria\CampoMaquinariaConsumoConsulta;
use App\Traits\HandlesAlerts;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Modal con el reporte anual de consumo de una máquina: galones recibidos vs horas distribuidas.
 * Se abre con el evento verConsumoMaquinaria (id de la máquina).
 */
class MaquinariaConsumoAnualComponent extends Component
{
    use LivewireAlert, HandlesAlerts;

    public bool $mostrar = false;
    public ?int $maquinariaId = null;
    public int $anio;
    public array $reporte = [];

    public function mount(): void
    {
        $this->anio = (int) now()->year;
    }

    #[On('verConsumoMaquinaria')]
    public function abrir(int $id): void
    {
        $this->maquinariaId = $id;
        $this->mostrar = true;
        $this->cargar();
    }

    public function updatedAnio(): void
    {
        $this->cargar();
    }

    private function cargar(): void
    {
        if (!$this->maquinariaId || !$this->anio) {
            return;
        }
        try {
            $this->reporte = app(CampoMaquinariaConsumoConsulta::class)->reporteAnual($this->maquinariaId, (int) $this->anio);
            $this->dispatch('graficoConsumoMaquinaria', reporte: $this->reporte);
        } catch (\Throwable $e) {
            $this->reporte = [];
            $this->errorAlert($e);
        }
    }

    public function render()
    {
        return view('livewire.campo.maquinaria-consumo-anual-component');
    }
}
