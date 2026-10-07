<?php

namespace App\Livewire\Evaluacion;

use App\Constants\Permisos;
use App\Models\CampoCampania;
use App\Models\Cuadrillero;
use App\Models\EvalBrotesPorPiso;
use App\Models\PlanEmpleado;
use App\Services\Campania\CampaniaServicio;
use App\Services\Evaluacion\BrotesPorPisoServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Evaluaciones de brotes por piso de una campaña:
 * 1. Campo y campaña.
 * 2. Metros de cama/ha (los mismos para todas las evaluaciones de la campaña) y todas las evaluaciones por fecha,
 *    con sus promedios y la diferencia contra la anterior (para ver la evolución).
 * 3. Al elegir una (o "Nueva evaluación"): fecha, evaluador y la tabla de camas.
 *
 * Hay una sola tabla de camas a la vez. Sus eventos llevan el id de este componente ($idTable) para no
 * confundirse con otra tabla de la página.
 */
class EvaluacionBrotesFormComponent extends Component
{
    use LivewireAlert;

    public $mostrarFormulario = false;
    public $idTable;
    public $evaluadoresNombres = [];

    public $campoSeleccionado;
    public $campaniasDisponibles = [];
    public $campaniaSeleccionada;
    public $metros_cama_ha;

    /** Evaluación abierta en el editor: null = ninguna; 'nueva' o el id */
    public $editando = null;
    public $fecha;
    public $evaluador;

    public function mount()
    {
        $this->idTable = 'brotes' . Str::random(10);
        $planilla = PlanEmpleado::pluck('nombres')->toArray();
        $cuadrilla = Cuadrillero::pluck('nombres')->toArray();
        $this->evaluadoresNombres = array_values(array_unique(array_merge($planilla, $cuadrilla)));
    }

    private function servicio(): BrotesPorPisoServicio
    {
        return app(BrotesPorPisoServicio::class);
    }

    private function puede(string $permiso): bool
    {
        return auth()->user()?->can($permiso) ?? false;
    }

    // ------------------------------------------------------------------ abrir

    /** Desde la lista o desde la campaña: con campaña, abre directo su panel y una evaluación nueva. */
    #[On('agregarEvaluacionBrote')]
    public function agregarEvaluacionBrote($campaniaId = null)
    {
        abort_unless($this->puede(Permisos::BROTE_EVALUACION_CREAR), 403);
        $this->limpiar();
        $this->mostrarFormulario = true;
        if ($campania = $campaniaId ? CampoCampania::find($campaniaId) : null) {
            $this->elegirCampania($campania);
            $this->nuevaEvaluacion();
        }
    }

    /** Editar desde la lista: abre la campaña con esa evaluación en el editor (y las demás en el panel). */
    #[On('editarEvaluacionBrotesPorPiso')]
    public function editarEvaluacionBrotesPorPiso($evaluacionBrotesXPisoId)
    {
        abort_unless($this->puede(Permisos::BROTE_EVALUACION_EDITAR), 403);
        $this->limpiar();
        $evaluacion = EvalBrotesPorPiso::with('campania')->findOrFail($evaluacionBrotesXPisoId);
        $this->mostrarFormulario = true;
        $this->elegirCampania($evaluacion->campania);
        $this->abrirEvaluacion($evaluacion->id);
    }

    private function elegirCampania(CampoCampania $campania): void
    {
        $this->campoSeleccionado = $campania->campo;
        $this->campaniasDisponibles = app(CampaniaServicio::class)->buscarCampaniasPorCampo($campania->campo);
        $this->campaniaSeleccionada = $campania->id;
        $this->metros_cama_ha = $this->servicio()->metrosCamaDeCampania($campania->id);
    }

    public function updatedCampoSeleccionado()
    {
        $this->reset(['campaniaSeleccionada', 'metros_cama_ha', 'editando', 'fecha', 'evaluador']);
        $this->campaniasDisponibles = $this->campoSeleccionado
            ? app(CampaniaServicio::class)->buscarCampaniasPorCampo($this->campoSeleccionado) : [];
        $this->cargarTabla([]);
    }

    public function updatedCampaniaSeleccionada()
    {
        $this->reset(['editando', 'fecha', 'evaluador']);
        $this->resetErrorBag();
        $this->metros_cama_ha = $this->campaniaSeleccionada ? $this->servicio()->metrosCamaDeCampania((int) $this->campaniaSeleccionada) : null;
        $this->cargarTabla([]);
    }

    // ------------------------------------------------------------------ editor

    public function abrirEvaluacion(int $id): void
    {
        $evaluacion = EvalBrotesPorPiso::findOrFail($id);
        abort_unless((int) $evaluacion->campania_id === (int) $this->campaniaSeleccionada, 404);
        $this->resetErrorBag();
        $this->editando = $evaluacion->id;
        $this->fecha = $evaluacion->fecha?->toDateString();
        $this->evaluador = $evaluacion->evaluador;
        $this->cargarTabla($this->servicio()->detallesDeEvaluacion($evaluacion->id));
    }

    public function nuevaEvaluacion(): void
    {
        abort_unless($this->puede(Permisos::BROTE_EVALUACION_CREAR), 403);
        $this->resetErrorBag();
        $this->editando = 'nueva';
        $this->fecha = Carbon::now()->toDateString();
        $this->evaluador = null;
        $this->cargarTabla([]);
    }

    public function cerrarEditor(): void
    {
        $this->reset(['editando', 'fecha', 'evaluador']);
        $this->resetErrorBag();
        $this->cargarTabla([]);
    }

    /** La tabla de camas manda sus filas (Alpine) para guardar la evaluación abierta. */
    public function guardarEvaluacion(array $filas): void
    {
        $esNueva = $this->editando === 'nueva';
        abort_unless($this->puede($esNueva ? Permisos::BROTE_EVALUACION_CREAR : Permisos::BROTE_EVALUACION_EDITAR), 403);
        try {
            $id = $this->servicio()->registrar([
                'id' => $esNueva ? null : $this->editando,
                'campania_id' => $this->campaniaSeleccionada,
                'fecha' => $this->fecha,
                'evaluador' => $this->evaluador,
                'metros_cama_ha' => $this->metros_cama_ha,
                'detalles' => $filas,
            ]);
            $this->resetErrorBag();
            $this->dispatch('brotesPorPisoRegistrado');
            $this->abrirEvaluacion($id); // la tabla vuelve con los valores por hectárea recalculados
            $this->alert('success', $esNueva ? 'Evaluación registrada.' : 'Evaluación actualizada.');
        } catch (ValidationException $ve) {
            $this->alert('error', 'No se guardó: ' . implode(' ', $ve->validator->errors()->all()), ['timer' => 6000]);
            throw $ve;
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    public function eliminarEvaluacion(int $id): void
    {
        abort_unless($this->puede(Permisos::BROTE_EVALUACION_ELIMINAR), 403);
        try {
            abort_unless((int) EvalBrotesPorPiso::whereKey($id)->value('campania_id') === (int) $this->campaniaSeleccionada, 404);
            $this->servicio()->eliminar($id);
            if ((string) $this->editando === (string) $id) {
                $this->cerrarEditor();
            }
            $this->dispatch('brotesPorPisoRegistrado');
            $this->alert('success', 'Evaluación eliminada (queda en la auditoría).');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    private function cargarTabla(array $filas): void
    {
        $this->dispatch("cargarDataBrotesXPiso-{$this->idTable}", filas: $filas);
    }

    private function limpiar(): void
    {
        $this->resetErrorBag();
        $this->reset(['campoSeleccionado', 'campaniasDisponibles', 'campaniaSeleccionada', 'metros_cama_ha', 'editando', 'fecha', 'evaluador']);
        $this->cargarTabla([]);
    }

    public function render()
    {
        $campania = $this->campaniaSeleccionada ? CampoCampania::find($this->campaniaSeleccionada) : null;

        return view('livewire.evaluacion.evaluacion-brotes-form-component', [
            'campania' => $campania,
            'evaluaciones' => $campania ? $this->servicio()->evaluacionesDeCampania($campania->id) : [],
            'promedios' => BrotesPorPisoServicio::PROMEDIOS,
            'puedeCrear' => $this->puede(Permisos::BROTE_EVALUACION_CREAR),
            'puedeEditar' => $this->puede(Permisos::BROTE_EVALUACION_EDITAR),
            'puedeEliminar' => $this->puede(Permisos::BROTE_EVALUACION_ELIMINAR),
        ]);
    }
}
