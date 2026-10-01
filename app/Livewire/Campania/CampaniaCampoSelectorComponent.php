<?php

namespace App\Livewire\Campania;

use App\Traits\HandlesAlerts;
use Livewire\Attributes\Title;
use App\Models\CampoCampania;
use App\Services\Campania\CampaniaServicio;
use App\Services\Campania\Registro\CampaniaRegistroProceso;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Illuminate\Support\Facades\Session;

#[Title('Campaña por Campo')]
class CampaniaCampoSelectorComponent extends Component
{
    use LivewireAlert, HandlesAlerts;
    public $breadcrumb = [];
    public $campoSeleccionado;
    public $campaniaSeleccionada; // El ID
    public $campanias = [];
    public $campania; // El Objeto Model

    public const PESTANIAS = ['informe' => 'Informe', 'costos' => 'Costos de producción'];

    /** Pestaña activa (se recuerda en la sesión). */
    public string $pestania = 'informe';

    protected $listeners = ['campaniaInsertada' => 'relistarNuevaCampania'];

    public function updatedPestania(string $valor): void
    {
        $this->pestania = array_key_exists($valor, self::PESTANIAS) ? $valor : 'informe';
        session(['campania_por_campo.pestania' => $this->pestania]);
    }

    /**
     * Página completa: /campanias_x_campo/{campania?}. Si la campaña no existe, vuelve al listado.
     */
    public function mount($campania = null, $campaniaId = null)
    {
        $pestania = session('campania_por_campo.pestania', 'informe');
        $this->pestania = array_key_exists($pestania, self::PESTANIAS) ? $pestania : 'informe';

        $campaniaId = $campaniaId ?? $campania;
        if ($campaniaId && !CampoCampania::whereKey($campaniaId)->exists()) {
            $this->redirectRoute('campania.por_campo');
            return;
        }

        $this->breadcrumb = [
            ['route' => 'campania.resumen', 'label' => 'Resúmen General de Campañas'],
            ['label' => 'Campañas por Campo']
        ];
        // 1. Determinar el campo inicial
        if ($campaniaId) {
            $campaniaModel = CampoCampania::find($campaniaId);
            if ($campaniaModel) {
                $this->campoSeleccionado = $campaniaModel->campo;
                Session::put('campo', $this->campoSeleccionado);
            }
        } else {
            $this->campoSeleccionado = Session::get('campo');
        }

        // 2. Cargar lista y seleccionar campaña
        if ($this->campoSeleccionado) {
            $this->cargarYSeleccionar($this->campoSeleccionado, $campaniaId ?: Session::get('campania'));
        }
    }

    /**
     * Centraliza la carga de la lista y la selección de la campaña activa
     */
    private function cargarYSeleccionar($campo, $campaniaIdDeseada = null)
    {
        $this->campoSeleccionado = $campo;

        // Cargar lista de campañas
        $this->campanias = CampoCampania::where('campo', $campo)
            ->orderBy('nombre_campania', 'desc')
            ->pluck('nombre_campania', 'id')
            ->toArray();

        if (empty($this->campanias)) {
            $this->resetearSeleccion();
            return;
        }

        // Determinar ID a seleccionar: 1. El pedido, 2. El de sesión, 3. El primero de la lista
        $idFinal = $campaniaIdDeseada;
        if (!$idFinal || !array_key_exists($idFinal, $this->campanias)) {
            $idFinal = array_key_first($this->campanias);
        }

        $this->setCampaniaActiva($idFinal);
    }

    /**
     * Esta es la ÚNICA función que debe cambiar el estado de la campaña
     */
    private function setCampaniaActiva($id)
    {
        $this->campaniaSeleccionada = $id;
        $this->campania = CampoCampania::find($id);

        if ($this->campania) {
            Session::put('campania', $id);
            $this->dispatch('campania-cambiada', id: $id);
        } else {
            $this->resetearSeleccion();
        }
    }

    private function resetearSeleccion()
    {
        $this->campaniaSeleccionada = null;
        $this->campania = null;
        Session::forget('campania');
    }

    // --- Eventos de UI ---

    public function updatedCampoSeleccionado($campo)
    {
        Session::put('campo', $campo);
        $this->cargarYSeleccionar($campo);
    }

    public function updatedCampaniaSeleccionada($id)
    {
        $this->setCampaniaActiva($id);
    }

    public function relistarNuevaCampania(array $datos)
    {
        $this->cargarYSeleccionar($datos['campo'] ?? null, $datos['id'] ?? null);
    }

    // --- Acciones ---
    public function generarReporteConsumo($campaniaId)
    {
        try {
            $campaniaServicio = new \App\Services\Campania\CampaniaHistorialServicio($campaniaId);

            $campaniaServicio->actualizarConsumo();
            $this->alert('success', 'Reporte de consumo generado correctamente.');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    /**
     * @param bool $regenerar antes de armar el Excel, regenera la BDD de costos solo de este campo en el rango de
     *                        la campaña (mismas fuentes que la consolidación mensual, filtradas por campo)
     */
    public function generarBdd($campaniaId, bool $regenerar = false)
    {
        try {
            $mensaje = 'Datos generados correctamente.';
            
            if ($regenerar) {
                $r = app(\App\Services\Costos\Consolidacion\CostosConsolidacionCampaniaProceso::class)->regenerar((int) $campaniaId);
                $mensaje = 'Costos regenerados del ' . formatear_fecha($r['desde']) . ' al ' . formatear_fecha($r['hasta'])
                    . " (mano de obra: {$r['mano_obra']} filas, insumos: {$r['insumos']}, servicios: {$r['servicios']}, "
                    . "maquinaria: {$r['maquinaria']}, gastos generales: {$r['gastos_generales']}) y reporte BDD generado.";
                if ($r['avisos']) {
                    $mensaje .= ' Revisar: ' . implode(' ', array_unique($r['avisos']));
                }
            }
            app(CampaniaServicio::class)->generarBddMensual($campaniaId);
            $this->successAlert($mensaje);
            // Refrescar el objeto por si cambió la ruta del archivo
            $this->campania = CampoCampania::find($campaniaId);
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    public function eliminarCampania($id)
    {
        try {
            // Auditado: queda en `auditorias` quién eliminó y la campaña completa
            app(CampaniaRegistroProceso::class)->eliminar((int) $id);
            $this->cargarYSeleccionar($this->campoSeleccionado);
            $this->alert('success', 'Campaña Eliminada.');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.campania.campania-x-campo-selector');
    }
}