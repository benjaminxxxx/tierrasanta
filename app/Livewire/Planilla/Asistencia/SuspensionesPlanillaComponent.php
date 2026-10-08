<?php

namespace App\Livewire\Planilla\Asistencia;

use App\Constants\Permisos;
use App\Models\PlanEmpleado;
use App\Services\Planilla\Asistencia\SugerenciaSuspensionServicio;
use App\Services\Planilla\Suspension\PlanillaSuspensionConsulta;
use App\Services\Planilla\Suspension\PlanillaSuspensionCrud;
use App\Traits\Selectores\ConSelectorMes;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

/**
 * Planilla → Permisos y suspensiones.
 *
 * - Suspensiones del mes agrupadas por trabajador (buscador y filtro por tipo de planilla), con la línea de días.
 * - Edición por trabajador en un modal: todos sus rangos se guardan y validan juntos (ver PlanillaSuspensionCrud).
 *   Se pueden registrar rangos antes de que estén en el registro diario (vacaciones, descanso médico ya sabido).
 * - Sugerencias desde el registro diario (se mantienen de la versión anterior).
 * - Estadísticas (subcomponente SuspensionesEstadisticasComponent): por mes del año, por día del mes o historial de un trabajador.
 */
class SuspensionesPlanillaComponent extends Component
{
    use LivewireAlert, ConSelectorMes;

    public string $vista = 'registro'; // registro | estadisticas
    public string $buscar = '';
    public string $tipoPlanilla = '';

    // Modal de un trabajador
    public bool $modal = false;
    public $modalEmpleadoId;
    public array $rangos = [];
    public array $idsEnModal = [];

    // Estadísticas
    public $estAnio;
    public $estMes = '';
    public $estEmpleadoId = '';

    /** Sugerencias del registro diario (panel izquierdo), códigos sin vínculo y conflictos (panel derecho). */
    public array $sugerencias = [];
    public array $porDecidir = [];
    public array $conflictos = [];
    /** Días A con parte del detalle en labores de suspensión (solo informativo). */
    public array $parciales = [];
    /** Claves de las sugerencias marcadas para aceptar (por defecto todas). */
    public array $seleccionadas = [];
    /** codigo de asistencia => id de tipo de suspensión elegido, o 'sin' (no genera suspensión). */
    public array $vinculos = [];

    public function mount(): void
    {
        $this->inicializarMesAnio();
        $this->estAnio = $this->anio ?: now()->year;
        $this->cargarSuspensionesPendientes();
    }

    protected function despuesMesAnioModificado(string $mes, string $anio)
    {
        $this->cargarSuspensionesPendientes();
    }

    // ------------------------------------------------------------------ modal por trabajador

    public function editarTrabajador(int $planEmpleadoId): void
    {
        $this->resetErrorBag();
        $this->modalEmpleadoId = $planEmpleadoId;
        $this->rangos = app(PlanillaSuspensionConsulta::class)->rangosParaEditar($planEmpleadoId, (int) $this->mes, (int) $this->anio);
        $this->idsEnModal = array_column($this->rangos, 'id');
        if (!$this->rangos) {
            $this->agregarRango();
        }
        $this->modal = true;
    }

    /** Botón "Agregar trabajador con suspensión": modal vacío con el selector de trabajador. */
    public function nuevoTrabajador(): void
    {
        $this->resetErrorBag();
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
            $existentes = app(PlanillaSuspensionConsulta::class)->rangosParaEditar((int) $valor, (int) $this->mes, (int) $this->anio);
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
        $this->alert('success', "Suspensiones guardadas: {$r['creados']} nuevas, {$r['actualizados']} editadas, {$r['eliminados']} eliminadas.");
        $this->cargarSuspensionesPendientes();
    }

    private function rangoVacio(): array
    {
        $dia = sprintf('%04d-%02d-01', (int) $this->anio, (int) $this->mes);
        return ['id' => null, 'tipo_suspension_id' => '', 'fecha_inicio' => $dia, 'fecha_fin' => $dia, 'observaciones' => ''];
    }

    // ------------------------------------------------------------------ sugerencias del registro diario

    public function cargarSuspensionesPendientes(): void
    {
        $mes = (int) $this->mes;
        $anio = (int) $this->anio;
        if ($mes < 1 || $mes > 12 || $anio < 2000) {
            $this->sugerencias = $this->porDecidir = $this->conflictos = $this->parciales = $this->seleccionadas = [];
            return;
        }

        $resultado = app(SugerenciaSuspensionServicio::class)->sugerir($mes, $anio);
        $this->sugerencias = $resultado['sugerencias'];
        $this->porDecidir = $resultado['por_decidir'];
        $this->conflictos = $resultado['conflictos'];
        $this->parciales = $resultado['parciales'];
        $this->seleccionadas = array_column($this->sugerencias, 'clave');
    }

    public function alternarTodas(): void
    {
        $this->seleccionadas = count($this->seleccionadas) === count($this->sugerencias)
            ? []
            : array_column($this->sugerencias, 'clave');
    }

    /** Registra de golpe las sugerencias marcadas. */
    public function aceptarSugerencias(): void
    {
        try {
            if (empty($this->seleccionadas)) {
                $this->alert('warning', 'No hay sugerencias marcadas.');
                return;
            }
            $r = app(SugerenciaSuspensionServicio::class)->aplicar((int) $this->mes, (int) $this->anio, $this->seleccionadas);
            $this->alert('success', "Suspensiones registradas: {$r['creadas']} nuevas, {$r['extendidas']} extendidas, {$r['unidas']} unidas.");
            $this->cargarSuspensionesPendientes();
        } catch (\Throwable $e) {
            $this->alert('error', 'No se pudieron registrar: ' . $e->getMessage());
        }
    }

    /** Vincula un código de asistencia con su suspensión (aplica a todos los meses). */
    public function vincularCodigo(string $codigo): void
    {
        $valor = $this->vinculos[$codigo] ?? null;
        if (!$valor) {
            $this->alert('warning', "Elige la suspensión para {$codigo}.");
            return;
        }
        app(SugerenciaSuspensionServicio::class)->vincular($codigo, $valor === 'sin' ? null : (int) $valor, $valor === 'sin');
        unset($this->vinculos[$codigo]);
        $this->alert('success', $valor === 'sin' ? "{$codigo} ya no genera suspensión." : "{$codigo} vinculado.");
        $this->cargarSuspensionesPendientes();
    }

    public function render()
    {
        $consulta = app(PlanillaSuspensionConsulta::class);
        $datos = ['tipos' => $consulta->tipos()];

        if ($this->vista === 'estadisticas') {
            // El gráfico y el historial los arma el subcomponente SuspensionesEstadisticasComponent
            $datos['empleadosTodos'] = PlanEmpleado::orderBy('apellido_paterno')->get()
                ->map(fn($e) => ['id' => $e->id, 'name' => $e->nombre_completo])->all();
        } else {
            $mesValido = (int) $this->mes >= 1 && (int) $this->anio >= 2000;
            $datos['trabajadores'] = $mesValido ? $consulta->porTrabajador((int) $this->mes, (int) $this->anio, $this->buscar, $this->tipoPlanilla ?: null) : collect();
            $datos['diasMes'] = $mesValido ? \Illuminate\Support\Carbon::create((int) $this->anio, (int) $this->mes, 1)->daysInMonth : 0;
            $datos['empleadosMes'] = $mesValido && $this->modal
                ? array_map(fn($e) => ['id' => $e['id'], 'name' => $e['label']], $consulta->empleadosDelMes((int) $this->mes, (int) $this->anio))
                : [];
            $datos['nombreModal'] = $this->modalEmpleadoId ? PlanEmpleado::find($this->modalEmpleadoId)?->nombre_completo : null;
        }

        return view('livewire.planilla.asistencia.suspensiones-planilla-component', $datos);
    }
}
