<?php

namespace App\Livewire\Almacen\Kardex;

use App\Models\InsKardexReporte;
use App\Services\Almacen\Kardex\KardexReporteServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class InsumoKardexReporteDetalleComponent extends Component
{
    use LivewireAlert;
    public $insumoKardexReporteId;
    public ?InsKardexReporte $insumoKardexReporte = null;
    /** Por qué el Excel guardado ya no refleja los kardex (vacío = al día). */
    public array $motivosDesactualizado = [];
    public array $advertencias = [];

    public function mount($insumoKardexReporteId)
    {
        $this->insumoKardexReporte = InsKardexReporte::with('detalles')->find($insumoKardexReporteId);
        if (!$this->insumoKardexReporte) {
            session()->flash('error', 'El reporte de kardex no existe');
            $this->redirectRoute('almacen.kardex.reportes');
            return;
        }
        $this->motivosDesactualizado = app(KardexReporteServicio::class)->motivosDesactualizado($this->insumoKardexReporte);
    }

    /**
     * Pone al día los kardex, recalcula el índice y genera el Excel (con una hoja por producto).
     */
    public function procesarKardexConsolidado()
    {
        try {
            $servicio = app(KardexReporteServicio::class);
            $this->advertencias = $servicio->generar($this->insumoKardexReporte);
            $this->insumoKardexReporte->refresh()->load('detalles');
            $this->motivosDesactualizado = [];

            $this->alert('success', 'Resumen y Excel generados' . ($this->advertencias ? ' (con advertencias).' : '.'));
        } catch (\Throwable $e) {
            logger()->error("Error al generar el reporte de kardex {$this->insumoKardexReporte->id}: " . $e->getMessage());
            $this->alert('error', $e->getMessage(), ['timer' => 20000, 'toast' => false, 'position' => 'center']);
        }
    }

    /**
     * Un kardex se editó con el modal (lápiz del índice): el índice y el Excel guardados
     * siguen con los datos anteriores hasta volver a generar el resumen.
     */
    #[\Livewire\Attributes\On('kardexGuardado')]
    public function kardexGuardado($kardexId = null, $editado = true)
    {
        $kardex = $kardexId ? \App\Models\InsKardex::with('producto')->find($kardexId) : null;
        $nombre = $kardex ? "{$kardex->codigo_existencia} {$kardex->producto?->nombre_comercial}" : 'El kardex';

        $this->motivosDesactualizado = app(KardexReporteServicio::class)->motivosDesactualizado($this->insumoKardexReporte);
        $this->alert('success', "{$nombre} se guardó. Usa \"Generar resumen\" para ver los cambios en este reporte y en el Excel.", [
            'timer' => 8000,
        ]);
    }

    public function render()
    {
        return view('livewire.almacen.kardex.insumo-kardex-reporte-detalle-component', [
            'totalesGrupo' => app(KardexReporteServicio::class)->totalesPorGrupo($this->insumoKardexReporte),
        ]);
    }
}
