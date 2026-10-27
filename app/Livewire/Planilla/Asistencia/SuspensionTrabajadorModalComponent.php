<?php

namespace App\Livewire\Planilla\Asistencia;

use App\Constants\Permisos;
use App\Models\PlanEmpleado;
use App\Services\Planilla\Suspension\PlanillaSuspensionConsulta;
use App\Services\Planilla\Suspension\PlanillaSuspensionCrud;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Modal "Suspensiones de un trabajador": todos sus rangos del mes (y los programados después) se editan y validan
 * juntos (PlanillaSuspensionCrud::guardarRangos). Lo usan Permisos y suspensiones y el registro diario.
 *
 * Eventos: editarSuspensionesTrabajador(planEmpleadoId, mes, anio) · nuevaSuspensionTrabajador(mes, anio).
 * Al guardar emite suspensionesGuardadas(planEmpleadoId).
 */
class SuspensionTrabajadorModalComponent extends Component
{
    use LivewireAlert, \App\Traits\HandlesAlerts;

    public bool $modal = false;
    public $modalEmpleadoId;
    public array $rangos = [];
    public array $idsEnModal = [];
    public int $mes;
    public int $anio;

    public function mount(): void
    {
        $this->mes = (int) now()->month;
        $this->anio = (int) now()->year;
    }

    #[On('editarSuspensionesTrabajador')]
    public function editarTrabajador(int $planEmpleadoId, ?int $mes = null, ?int $anio = null): void
    {
        $this->resetErrorBag();
        $this->mes = $mes ?: $this->mes;
        $this->anio = $anio ?: $this->anio;
        $this->modalEmpleadoId = $planEmpleadoId;
        $this->rangos = app(PlanillaSuspensionConsulta::class)->rangosParaEditar($planEmpleadoId, $this->mes, $this->anio);
        $this->idsEnModal = array_column($this->rangos, 'id');
        if (!$this->rangos) {
            $this->agregarRango();
        }
        $this->modal = true;
    }

    /** "Agregar trabajador con suspensión": modal vacío con el selector de trabajador. */
    #[On('nuevaSuspensionTrabajador')]
    public function nuevoTrabajador(?int $mes = null, ?int $anio = null): void
    {
        $this->resetErrorBag();
        $this->mes = $mes ?: $this->mes;
        $this->anio = $anio ?: $this->anio;
        $this->modalEmpleadoId = null;
        $this->rangos = [];
        $this->idsEnModal = [];
        $this->agregarRango();
        $this->modal = true;
    }

    /** Al elegir el trabajador en el modal se cargan los rangos que ya tenga. */
    public function updatedModalEmpleadoId($valor): void
    {
        if ($valor) {
            $existentes = app(PlanillaSuspensionConsulta::class)->rangosParaEditar((int) $valor, $this->mes, $this->anio);
            $nuevos = array_values(array_filter($this->rangos, fn($r) => empty($r['id']) && (!empty($r['tipo_suspension_id']) || !empty($r['fecha_inicio']))));
            $this->rangos = array_merge($existentes, $nuevos ?: [$this->rangoVacio()]);
            $this->idsEnModal = array_column($existentes, 'id');
        }
    }

    public function agregarRango(): void
    {
        $this->rangos[] = $this->rangoVacio();
    }

    public function quitarRango(int $i): void
    {
        unset($this->rangos[$i]);
        $this->rangos = array_values($this->rangos);
    }

    public function guardarRangos(): void
    {
        $this->authorize(Permisos::PLANILLA_SUSPENSION_GESTIONAR);
        $this->validate(['modalEmpleadoId' => 'required|exists:plan_empleados,id'], [], ['modalEmpleadoId' => 'trabajador']);

        $r = app(PlanillaSuspensionCrud::class)->guardarRangos((int) $this->modalEmpleadoId, $this->rangos, $this->idsEnModal);
        $this->modal = false;
        $texto = "Suspensiones guardadas: {$r['creados']} nuevas, {$r['actualizados']} editadas, {$r['eliminados']} eliminadas.";
        if ($r['registrados'] || $r['revertidos']) {
            $texto .= " Registro diario: {$r['registrados']} día(s) marcados, {$r['revertidos']} quitados.";
        }
        if ($r['conflictos']) {
            // Días ya registrados con otra asistencia: no se pisan, se avisa para revisarlos
            $this->alert('warning', $texto . ' Revisa en el registro diario: ' . implode('; ', array_slice($r['conflictos'], 0, 5))
                . (count($r['conflictos']) > 5 ? '…' : '.'), ['timer' => 12000]);
        } else {
            $this->alert('success', $texto);
        }
        $this->dispatch('suspensionesGuardadas', planEmpleadoId: (int) $this->modalEmpleadoId);
    }

    private function rangoVacio(): array
    {
        $dia = sprintf('%04d-%02d-01', $this->anio, $this->mes);
        return ['id' => null, 'tipo_suspension_id' => '', 'fecha_inicio' => $dia, 'fecha_fin' => $dia, 'observaciones' => ''];
    }

    public function render()
    {
        $consulta = app(PlanillaSuspensionConsulta::class);

        return view('livewire.planilla.asistencia.suspension-trabajador-modal-component', [
            'tipos' => $this->modal ? $consulta->tipos() : [],
            'empleadosMes' => $this->modal && !$this->idsEnModal
                ? array_map(fn($e) => ['id' => $e['id'], 'name' => $e['label']], $consulta->empleadosDelMes($this->mes, $this->anio))
                : [],
            'nombreModal' => $this->modalEmpleadoId ? PlanEmpleado::find($this->modalEmpleadoId)?->nombre_completo : null,
        ]);
    }
}
