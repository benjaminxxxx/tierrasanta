<?php

namespace App\Livewire\Campo;

use App\Constants\Permisos;
use App\Models\Labores;
use App\Models\ManoObra;
use App\Models\PlanTipoAsistencia;
use App\Services\Campo\Labor\CampoLaborVigenciaConsulta;
use App\Services\Campo\Labor\CampoLaborVigenciaProceso;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Modal "Reasignar código": desde una fecha, el código pasa a ser otra labor. Lo anterior queda en su historia y
 * los registros antiguos se siguen leyendo con la labor de entonces. Se abre con reasignarCodigoLabor y avisa con
 * laborGuardada.
 */
class LaborReasignarComponent extends Component
{
    use LivewireAlert;

    public bool $mostrar = false;
    public ?int $laborId = null;
    public $desde;
    public $nombre_labor;
    public $codigo_mano_obra;
    public $unidades;
    public $estandar_produccion;
    public $tipo_asistencia_codigo;
    public $motivo;

    #[On('reasignarCodigoLabor')]
    public function abrir(int $id): void
    {
        abort_unless(auth()->user()?->can(Permisos::CAMPO_LABOR_GESTIONAR), 403);
        $labor = Labores::withTrashed()->findOrFail($id);
        $this->resetErrorBag();
        $this->reset(['nombre_labor', 'codigo_mano_obra', 'unidades', 'estandar_produccion', 'tipo_asistencia_codigo', 'motivo']);
        $this->laborId = $labor->id;
        // Por defecto: hoy, o el día después de su último registro si fuera posterior
        $ultimo = app(CampoLaborVigenciaConsulta::class)->ultimosUsos([(int) $labor->codigo])[(int) $labor->codigo] ?? null;
        $this->desde = max(now()->toDateString(), $ultimo ? Carbon::parse($ultimo)->addDay()->toDateString() : '');
        $this->mostrar = true;
    }

    public function guardar(): void
    {
        abort_unless(auth()->user()?->can(Permisos::CAMPO_LABOR_GESTIONAR), 403);
        try {
            $labor = app(CampoLaborVigenciaProceso::class)->reasignar($this->laborId, (string) $this->desde, [
                'nombre_labor' => (string) $this->nombre_labor,
                'codigo_mano_obra' => $this->codigo_mano_obra,
                'unidades' => $this->unidades,
                'estandar_produccion' => $this->estandar_produccion,
                'tipo_asistencia_codigo' => $this->tipo_asistencia_codigo,
            ], $this->motivo);
            $this->resetErrorBag();
            $this->mostrar = false;
            $this->alert('success', "Código {$labor->codigo} reasignado a \"{$labor->nombre_labor}\" desde el " . $labor->vigente_desde->format('d/m/Y') . '.');
            $this->dispatch('laborGuardada');
        } catch (ValidationException $e) {
            $this->alert('error', 'No se reasignó: ' . implode(' ', $e->validator->errors()->all()), ['timer' => 7000]);
            throw $e;
        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function render()
    {
        $labor = $this->mostrar && $this->laborId ? Labores::withTrashed()->with('manoObra')->find($this->laborId) : null;
        $consulta = app(CampoLaborVigenciaConsulta::class);

        return view('livewire.campo.labor-reasignar-component', [
            'labor' => $labor,
            'ultimoUso' => $labor ? ($consulta->ultimosUsos([(int) $labor->codigo])[(int) $labor->codigo] ?? null) : null,
            'historia' => $labor ? $consulta->historia((int) $labor->codigo) : [],
            'manoObras' => $labor ? ManoObra::orderBy('descripcion')->pluck('descripcion', 'codigo') : collect(),
            'tiposAsistencia' => $labor ? PlanTipoAsistencia::where('codigo', '<>', 'A')->orderBy('codigo')->get(['codigo', 'descripcion']) : collect(),
        ]);
    }
}
