<?php

namespace App\Livewire\Planilla\Asistencia;

use App\Constants\Permisos;
use App\Models\PlanEmpleado;
use App\Services\Planilla\Asistencia\SugerenciaSuspensionServicio;
use App\Services\Planilla\Suspension\PlanillaSuspensionConsulta;
use App\Traits\Selectores\ConSelectorMes;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

/**
 * Planilla → Permisos y suspensiones.
 *
 * - Suspensiones del mes agrupadas por trabajador (buscador y filtro por tipo de planilla), con la línea de días.
 * - Edición por trabajador en un modal (SuspensionTrabajadorModalComponent, también lo usa el registro diario).
 *   Se pueden registrar rangos antes de que estén en el registro diario (vacaciones, descanso médico ya sabido).
 * - Sugerencias desde el registro diario (se mantienen de la versión anterior).
 * - Estadísticas (subcomponente SuspensionesEstadisticasComponent): por mes del año, por día del mes o historial de un trabajador.
 */
class SuspensionesPlanillaComponent extends Component
{
    use LivewireAlert, ConSelectorMes, \App\Traits\HandlesAlerts;

    public string $vista = 'registro'; // registro | estadisticas
    public string $buscar = '';
    public string $tipoPlanilla = '';

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

    /** El modal del trabajador (SuspensionTrabajadorModalComponent) guardó: refrescar sugerencias y lista. */
    #[\Livewire\Attributes\On('suspensionesGuardadas')]
    public function suspensionesGuardadas(): void
    {
        $this->cargarSuspensionesPendientes();
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
            $this->errorAlert($e, 'No se pudieron registrar');
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
            $datos['empleadosTodos'] = PlanEmpleado::alfabetico()->get()
                ->map(fn($e) => ['id' => $e->id, 'name' => $e->nombre_completo])->all();
        } else {
            $mesValido = (int) $this->mes >= 1 && (int) $this->anio >= 2000;
            $datos['trabajadores'] = $mesValido ? $consulta->porTrabajador((int) $this->mes, (int) $this->anio, $this->buscar, $this->tipoPlanilla ?: null) : collect();
            $datos['diasMes'] = $mesValido ? \Illuminate\Support\Carbon::create((int) $this->anio, (int) $this->mes, 1)->daysInMonth : 0;
        }

        return view('livewire.planilla.asistencia.suspensiones-planilla-component', $datos);
    }
}
