<?php

namespace App\Livewire\Costos;

use App\Constants\Permisos;
use App\Models\CampoCampania;
use App\Services\Campania\CampaniaServicio;
use App\Services\Costos\Campania\CostosCampaniaConsulta;
use App\Services\Costos\Consolidacion\CostosConsolidacionCampaniaProceso;
use App\Traits\HandlesAlerts;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

/**
 * /costos/campania/{campaniaId?}: costos de una campaña leídos solo de la BDD (resumen_costo_diarios), mes a mes y
 * por sección, con el aviso de los meses incompletos (sin costos fijos/operativos distribuidos o sin mano de obra
 * indirecta calculada). Descarga el Reporte BDD de la campaña (el mismo Excel de /costos/campo y de Campañas por
 * campo), opcionalmente regenerando antes la BDD del campo en el rango de la campaña.
 */
class CostosCampaniaComponent extends Component
{
    use LivewireAlert, HandlesAlerts;

    public ?string $campoSeleccionado = null;
    public $campaniaSeleccionada = null;

    public function mount($campaniaId = null): void
    {
        abort_if($campaniaId && !CampoCampania::whereKey($campaniaId)->exists(), 404);
        $this->campoSeleccionado = Session::get('campo_seleccionado');
        if ($campaniaId) {
            $campania = CampoCampania::find($campaniaId);
            $this->campoSeleccionado = $campania->campo;
            $this->campaniaSeleccionada = (int) $campaniaId;
            Session::put('campo_seleccionado', $this->campoSeleccionado);
        } elseif ($this->campoSeleccionado) {
            $this->campaniaSeleccionada = CampoCampania::where('campo', $this->campoSeleccionado)->orderByDesc('fecha_inicio')->value('id');
        }
    }

    public function updatedCampoSeleccionado(): void
    {
        Session::put('campo_seleccionado', $this->campoSeleccionado);
        $this->campaniaSeleccionada = CampoCampania::where('campo', $this->campoSeleccionado)->orderByDesc('fecha_inicio')->value('id');
    }

    /** Arma el Reporte BDD de la campaña con la BDD tal como está y lo descarga. */
    public function descargarBdd(bool $regenerar = false)
    {
        try {
            abort_unless($this->campaniaSeleccionada, 404);
            if ($regenerar) {
                abort_unless(auth()->user()->can(Permisos::CAMPAÑA_POR_CAMPO_GESTIONAR), 403);
                app(CostosConsolidacionCampaniaProceso::class)->regenerar((int) $this->campaniaSeleccionada);
            }
            app(CampaniaServicio::class)->generarBddMensual((int) $this->campaniaSeleccionada);
            $ruta = CampoCampania::find($this->campaniaSeleccionada)?->gasto_resumen_bdd_file;
            if (!$ruta || !Storage::disk('public')->exists($ruta)) {
                throw new \App\Exceptions\ErrorControlado('No se pudo generar el Excel de la BDD.');
            }
            return Storage::disk('public')->download($ruta);
        } catch (\Throwable $e) {
            $this->errorAlert($e);
            return null;
        }
    }

    public function render()
    {
        $campania = $this->campaniaSeleccionada ? CampoCampania::find($this->campaniaSeleccionada) : null;
        return view('livewire.costos.costos-campania-component', [
            'campanias' => $this->campoSeleccionado
                ? CampoCampania::where('campo', $this->campoSeleccionado)->orderByDesc('fecha_inicio')->get(['id', 'nombre_campania', 'fecha_inicio', 'fecha_fin'])
                : collect(),
            'campania' => $campania,
            'datos' => $campania ? app(CostosCampaniaConsulta::class)->consultar($campania) : null,
            'puedeRegenerar' => auth()->user()->can(Permisos::CAMPAÑA_POR_CAMPO_GESTIONAR),
        ]);
    }
}
