<?php

namespace App\Livewire\Campania;

use App\Models\CampaniaCostoProduccion;
use App\Services\Campania\CostoProduccion\CampaniaCostoProduccionConsulta;
use App\Services\Campania\CostoProduccion\CampaniaCostoProduccionProceso;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

/**
 * Pestaña "Costos de producción" de /campania/por-campo: muestra la última versión guardada (o una anterior) y
 * "Volver a consultar" guarda una versión nueva con lo que hay hoy en la BDD de costos.
 */
class CampaniaCostoProduccionComponent extends Component
{
    use LivewireAlert;

    public int $campaniaId;
    public ?int $versionId = null;

    public function mount(int $campaniaId): void
    {
        $this->campaniaId = $campaniaId;
        $this->versionId = $this->versiones()->first()?->id;
    }

    public function consultar(): void
    {
        try {
            $version = app(CampaniaCostoProduccionProceso::class)->generar($this->campaniaId);
            $this->versionId = $version->id;
            $this->alert('success', $version->filas_origen
                ? 'Costos consultados: se guardó una versión nueva.'
                : 'No hay costos de esta campaña en la BDD de costos (consolida los meses de la campaña).');
        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    private function versiones()
    {
        return CampaniaCostoProduccion::with('generadoPor:id,name')
            ->where('campo_campania_id', $this->campaniaId)
            ->latest('id')
            ->get(['id', 'created_at', 'generado_por', 'total_soles', 'filas_origen']);
    }

    public function render()
    {
        $versiones = $this->versiones();
        $version = $this->versionId
            ? CampaniaCostoProduccion::with(['detalles', 'campania'])->where('campo_campania_id', $this->campaniaId)->find($this->versionId)
            : null;

        return view('livewire.campania.campania-costo-produccion-component', [
            'versiones' => $versiones,
            'version' => $version,
            'reporte' => $version ? app(CampaniaCostoProduccionConsulta::class)->estructura($version) : null,
        ]);
    }
}
