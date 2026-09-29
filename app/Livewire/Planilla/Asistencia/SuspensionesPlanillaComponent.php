<?php

namespace App\Livewire\Planilla\Asistencia;

use App\Models\PlanEmpleado;
use App\Models\PlanTipoSuspension;
use App\Services\Planilla\PlanillaSuspensionProceso;
use App\Services\Planilla\PlanillaSuspensionServicio;
use App\Services\Planilla\PlanillaEmpleadoServicio;
use App\Services\Planilla\Asistencia\SugerenciaSuspensionServicio;
use App\Traits\Selectores\ConSelectorMes;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class SuspensionesPlanillaComponent extends Component
{
    use LivewireAlert, WithPagination, ConSelectorMes;

    // Propiedades de Estado
    public array $suspensiones = [];
    public array $filtros = [];
    public array $listaEmpleados = [];
    public array $listaSuspensiones = [];

    protected $listeners = [];


    protected PlanillaSuspensionServicio $servicio;
    protected PlanillaSuspensionProceso $proceso;
    public function boot(
        PlanillaSuspensionServicio $servicio,
        PlanillaSuspensionProceso $proceso
    ) {
        $this->servicio = $servicio;
        $this->proceso = $proceso;
    }

    public function mount()
    {
        $this->inicializarMesAnio();
        $this->cargarSuspensiones(false);
        $this->cargarEmpleados();
        $this->cargarSuspensionesPendientes();

        $this->listaSuspensiones = PlanTipoSuspension::get()
            ->map(function ($q) {
                return [
                    'id' => $q->id,
                    'label' => $q->codigo . ' - ' . $q->descripcion
                ];
            })
            ->toArray();
    }
    public function cargarEmpleados()
    {
        if (!$this->mes || !$this->anio) {
            $this->listaEmpleados = PlanEmpleado::get()
                ->map(function ($q) {
                    return [
                        'id' => $q->id,
                        'label' => $q->nombre_completo
                    ];
                })
                ->toArray();
            return;
        }
        $this->listaEmpleados = app(PlanillaEmpleadoServicio::class)->obtenerPlanillaAgraria($this->mes, $this->anio)
            ->map(function ($q) {
                return [
                    'id' => $q->id,
                    'label' => $q->nombre_completo
                ];
            })
            ->toArray();
    }
    /** Sugerencias del registro diario (panel izquierdo), códigos sin vínculo y conflictos (panel derecho). */
    public array $sugerencias = [];
    public array $porDecidir = [];
    public array $conflictos = [];
    /** Claves de las sugerencias marcadas para aceptar (por defecto todas). */
    public array $seleccionadas = [];
    /** codigo de asistencia => id de tipo de suspensión elegido, o 'sin' (no genera suspensión). */
    public array $vinculos = [];

    public function cargarSuspensionesPendientes()
    {
        $mes = $this->normalizarMes($this->mes);
        $anio = $this->normalizarAnio($this->anio);
        if (!$mes || !$anio) {
            $this->sugerencias = $this->porDecidir = $this->conflictos = $this->seleccionadas = [];
            return;
        }

        $resultado = app(SugerenciaSuspensionServicio::class)->sugerir($mes, $anio);
        $this->sugerencias = $resultado['sugerencias'];
        $this->porDecidir = $resultado['por_decidir'];
        $this->conflictos = $resultado['conflictos'];
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
            $r = app(SugerenciaSuspensionServicio::class)->aplicar(
                $this->normalizarMes($this->mes),
                $this->normalizarAnio($this->anio),
                $this->seleccionadas
            );
            $this->alert('success', "Suspensiones registradas: {$r['creadas']} nuevas, {$r['extendidas']} extendidas, {$r['unidas']} unidas.");
            $this->cargarSuspensiones();
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
    protected function despuesMesAnioModificado(string $mes, string $anio)
    {
        $this->cargarSuspensiones();
    }
    public function guardarRegistrosSuspensiones($datos)
    {

        try {
            $resultado = $this->proceso->guardarHandsontable(
                $datos,
                $this->mes,
                $this->anio
            );

            $mensaje = sprintf(
                'Creados: %d | Actualizados: %d | Eliminados: %d',
                $resultado['creados'],
                $resultado['actualizados'],
                $resultado['eliminados']
            );

            if (!empty($resultado['errores'])) {
                $mensaje .= ' | Errores: ' . count($resultado['errores']);
            }

            $this->alert('success', 'Suspensiones guardadas', [
                'text' => $mensaje,
                'position' => 'top-end',
                'timer' => 4000,
            ]);

            $this->cargarSuspensiones();
        } catch (\Exception $e) {
            $this->alert('error', 'Error al guardar', [
                'text' => $e->getMessage(),
                'position' => 'top-end',
                'timer' => 5000,
            ]);
        }
    }
    public function cargarSuspensiones($dispatched = true)
    {
        $mes = $this->normalizarMes($this->mes);
        $anio = $this->normalizarAnio($this->anio);

        $this->suspensiones = $this->servicio->prepararParaHandsontable($mes, $anio);
        $this->cargarEmpleados();
        $this->cargarSuspensionesPendientes();

        if ($dispatched) {
            $this->dispatch('refrescarTablaSuspensiones', data: $this->suspensiones, empleados: $this->listaEmpleados);
        }
    }
    private function normalizarMes($valor): ?int
    {
        // Caso vacío o null
        if ($valor === '' || $valor === null) {
            return null;
        }

        // convertir a entero
        $mes = intval($valor);

        // validar rango real
        return ($mes >= 1 && $mes <= 12) ? $mes : null;
    }

    private function normalizarAnio($valor): ?int
    {
        if ($valor === '' || $valor === null) {
            return null;
        }

        $anio = intval($valor);

        // ajusta el rango según tu sistema
        return ($anio >= 2000 && $anio <= 2100) ? $anio : null;
    }

    public function render()
    {
        return view('livewire.planilla.asistencia.suspensiones-planilla-component');
    }
}