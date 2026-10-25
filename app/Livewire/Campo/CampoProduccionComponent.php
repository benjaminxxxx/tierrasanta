<?php

namespace App\Livewire\Campo;

use App\Constants\Permisos;
use App\Exceptions\ErrorControlado;
use App\Models\Actividad;
use App\Models\Labores;
use App\Models\ProduccionRegistro;
use App\Services\Campo\Produccion\CampoProduccionConsulta;
use App\Services\Campo\Produccion\CampoProduccionProceso;
use App\Services\Campo\Produccion\CampoProduccionReglas;
use Illuminate\Support\Facades\Gate;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Producción de campo (DESIGN.md): todas las cantidades por día, campo y labor (con bono, de bonificaciones; sin bono,
 * registradas aquí), totales por campo y cuadre de lo cosechado contra el ingreso de cochinilla con advertencias.
 */
class CampoProduccionComponent extends Component
{
    use LivewireAlert, \App\Traits\HandlesAlerts;

    #[Url]
    public string $pestania = 'registro';
    #[Url]
    public ?string $desde = null;
    #[Url]
    public ?string $hasta = null;
    #[Url]
    public ?string $campo = null;
    #[Url]
    public ?string $labor = null;
    public string $buscar = '';

    /** Actividad abierta en el panel (detalle con bono + edición sin bono) */
    public ?int $actividadId = null;
    /** Formulario "Registrar día sin bono" */
    public bool $nuevoDia = false;
    public array $dia = ['fecha' => null, 'campo' => null, 'labor' => null];

    public function mount(): void
    {
        $this->desde ??= now()->startOfMonth()->toDateString();
        $this->hasta ??= now()->toDateString();
    }

    public function abrir(int $actividadId): void
    {
        $this->actividadId = $actividadId;
        $this->nuevoDia = false;
    }

    public function cerrar(): void
    {
        $this->reset(['actividadId', 'nuevoDia', 'dia']);
    }

    public function abrirNuevoDia(): void
    {
        $this->actividadId = null;
        $this->nuevoDia = true;
        $this->dia = ['fecha' => now()->toDateString(), 'campo' => $this->campo, 'labor' => $this->labor];
    }

    /** Día completo sin bono: busca o crea la actividad (fecha + campo + labor) y abre su registro. */
    public function crearDia(CampoProduccionProceso $proceso): void
    {
        try {
            if (!Gate::allows(Permisos::CAMPO_PRODUCCION_REGISTRAR)) {
                throw new ErrorControlado('No tienes permiso para registrar producción (permiso: ' . Permisos::CAMPO_PRODUCCION_REGISTRAR . ').');
            }
            if (blank($this->dia['fecha']) || blank($this->dia['campo']) || blank($this->dia['labor'])) {
                throw new ErrorControlado('Indica la fecha, el campo y la labor.');
            }
            $actividad = $proceso->asegurarActividad($this->dia['fecha'], $this->dia['campo'], (int) $this->dia['labor']);
            $this->abrir($actividad->id);
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    #[On('produccionActualizada')]
    public function refrescar(): void
    {
        // Se vuelve a calcular en render
    }

    public function render()
    {
        $desde = $this->desde ?: now()->startOfMonth()->toDateString();
        $hasta = $this->hasta ?: now()->toDateString();
        $labor = $this->labor ? (int) $this->labor : null;
        $datos = match ($this->pestania) {
            'campos' => ['totales' => CampoProduccionConsulta::totalesPorCampo($desde, $hasta, $this->campo ?: null)],
            'cuadre' => ['cuadre' => CampoProduccionConsulta::cuadreIngreso($desde, $hasta, $this->campo ?: null)],
            default => ['resumen' => CampoProduccionConsulta::resumen($desde, $hasta, $this->campo ?: null, $labor, trim($this->buscar) ?: null)],
        };
        $actividad = $this->actividadId ? Actividad::find($this->actividadId) : null;

        return view('livewire.campo.campo-produccion-component', $datos + [
            'actividad' => $actividad,
            'conBono' => $actividad ? ProduccionRegistro::with(['cuadrillero:id,nombres', 'empleado'])->where('actividad_id', $actividad->id)
                ->where('con_bono', true)->orderBy('numero_recojo')->get()->groupBy(fn($p) => CampoProduccionConsulta::clave($p->cuadrillero_id, $p->plan_empleado_id)) : collect(),
            // Labores que producen algo (tienen unidad), para filtrar y registrar días sin bono
            'labores' => Labores::whereNotNull('unidades')->where('unidades', '<>', '')->where('unidades', '<>', '0')->orderBy('codigo')->get(['codigo', 'nombre_labor', 'unidades']),
            'puedeRegistrar' => Gate::allows(Permisos::CAMPO_PRODUCCION_REGISTRAR),
            'pctAviso' => CampoProduccionReglas::PCT_AVISO,
            'pctAnomalia' => CampoProduccionReglas::PCT_ANOMALIA,
        ])->title('Producción de campo');
    }
}
