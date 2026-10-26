<?php

namespace App\Livewire\Campania;

use App\Models\CampoCampania;
use App\Services\Campania\CostoProduccion\CampaniaCostoProduccionConsulta;
use Livewire\Component;

/**
 * Pestaña "Costos de producción" de /campania/por-campo: lee en vivo la BDD de costos (resumen_costo_diarios),
 * con el área y el tipo de cambio actuales de la campaña. No guarda copias.
 */
class CampaniaCostoProduccionComponent extends Component
{
    public int $campaniaId;

    public function mount(int $campaniaId): void
    {
        $this->campaniaId = $campaniaId;
    }

    public function render()
    {
        $campania = CampoCampania::findOrFail($this->campaniaId);

        return view('livewire.campania.campania-costo-produccion-component', [
            'reporte' => app(CampaniaCostoProduccionConsulta::class)->estructura($campania),
        ]);
    }
}
