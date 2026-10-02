<?php

namespace App\Livewire\Caja;

use App\Models\CajaMovimiento;
use App\Services\Caja\Historial\CajaHistorialConsulta;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Historial de caja (solo lectura): cierres y reaperturas por mes, y la actividad de movimientos y arqueos
 * (quién registró, editó o eliminó, cuándo, qué cambió y por qué).
 */
#[Title('Historial de caja')]
class CajaHistorialComponent extends Component
{
    use WithPagination;

    #[Url] public string $pestana = 'cierres';
    #[Url] public $anio;

    #[Url] public string $desde = '';
    #[Url] public string $hasta = '';
    #[Url] public string $tipo = '';
    #[Url] public string $accion = '';
    #[Url] public string $usuario = '';
    #[Url] public string $buscar = '';

    public function mount(): void
    {
        $this->anio = (int) ($this->anio ?: now()->year);
    }

    public function updated($propiedad): void
    {
        if (in_array($propiedad, ['desde', 'hasta', 'tipo', 'accion', 'usuario', 'buscar'], true)) {
            $this->resetPage();
        }
    }

    public function limpiarFiltros(): void
    {
        $this->reset(['desde', 'hasta', 'tipo', 'accion', 'usuario', 'buscar']);
        $this->resetPage();
    }

    public function render()
    {
        $consulta = app(CajaHistorialConsulta::class);
        $anios = CajaMovimiento::withTrashed()->selectRaw('DISTINCT YEAR(fecha) as a')->orderByDesc('a')->pluck('a')->all() ?: [now()->year];

        return view('livewire.caja.caja-historial-component', [
            'anios' => $anios,
            'cierres' => $this->pestana === 'cierres' ? $consulta->cierres((int) $this->anio) : [],
            'actividad' => $this->pestana === 'actividad' ? $consulta->actividad($this->only(['desde', 'hasta', 'tipo', 'accion', 'usuario', 'buscar'])) : null,
            'usuarios' => $this->pestana === 'actividad' ? $consulta->usuarios() : [],
            'tipos' => CajaHistorialConsulta::MODELOS,
        ]);
    }
}
