<?php

namespace App\Livewire\Cuadrilla;

use App\Models\CuadTramoLaboral;
use App\Services\Cuadrilla\ResumenTramoInformativoServicio;
use App\Traits\HandlesAlerts;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;

/**
 * Cuadro resumen (historial) del tramo, que se abre con "Ver resumen acumulado" en el reporte semanal.
 * Se calcula al abrirlo desde el estado de pago del desglose: siempre muestra lo último pagado, sin tener que
 * "recalcular". Recalcular solo hace falta al cambiar hasta dónde se calcula el bono.
 */
class GestionCuadrillaResumenAcumuladoComponent extends Component
{
    use LivewireAlert;
    use HandlesAlerts;

    public $tramoLaboralId;
    public $resumenes = [];
    public $fechaHastaBono;
    public bool $mostrarCuadroResumenCuadrilleroSemanal = false;

    protected $listeners = ['abrirResumenAcumuladoCuadrilla'];

    public function mount($tramoLaboralId, $fechaHastaBono)
    {
        $this->tramoLaboralId = $tramoLaboralId;
        $this->fechaHastaBono = $fechaHastaBono;
    }

    public function abrirResumenAcumuladoCuadrilla(): void
    {
        $this->calcular();
        $this->mostrarCuadroResumenCuadrilleroSemanal = true;
    }

    /** Guarda hasta dónde se calcula el bono y vuelve a calcular. */
    public function recalcularResumenTramo(): void
    {
        try {
            CuadTramoLaboral::where('id', $this->tramoLaboralId)->update(['fecha_hasta_bono' => $this->fechaHastaBono ?: null]);
            $this->calcular();
            $this->successAlert('Resumen actualizado correctamente.');
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    private function calcular(): void
    {
        $this->resumenes = app(ResumenTramoInformativoServicio::class)->generar($this->tramoLaboralId)->toArray();
    }

    public function render()
    {
        return view('livewire.cuadrilla.gestion-cuadrilla-resumen-acumulado-component');
    }
}
