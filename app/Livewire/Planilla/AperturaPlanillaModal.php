<?php

namespace App\Livewire\Planilla;

use App\Models\PlanMensual;
use App\Services\Planilla\Parametro\PlanillaParametroConsulta;
use App\Services\Planilla\Parametro\PlanillaParametroReglas;
use App\Services\Planilla\PlanillaMensualServicio;
use App\Services\Planilla\PlanillaServicio;
use App\Traits\HandlesAlerts;
use Exception;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Apertura y generación de la planilla agraria del mes. Solo se guarda lo propio del mes (días laborables, sueldo
 * base). Los parámetros (RMV, EsSalud, AFP…) se muestran como referencia: se leen de Parámetros de planilla y se
 * pueden editar desde aquí (modal de parámetros); si falta alguno se dice cuál.
 */
class AperturaPlanillaModal extends Component
{
    use LivewireAlert, HandlesAlerts;

    public bool $showingAperturaPlanilla = false;

    public ?int $planMensualId = null;

    public int $mes = 1;
    public int $anio = 2026;

    // Editables por el usuario
    public ?int $dias_laborables = null;
    public ?float $remuneracion_basica = null;
    public ?string $excel = null;

    /** Parámetros del mes que usa la planilla agraria: codigo, nombre, texto, falta */
    public array $parametrosMes = [];
    /** Comisiones y primas AFP vigentes en el mes */
    public array $afpMes = [];
    /** AFP sin valores vigentes en el mes */
    public array $afpFaltan = [];

    protected function rules(): array
    {
        return [
            'dias_laborables' => ['required', 'integer', 'min:1', 'max:31'],
            'remuneracion_basica' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /** Se abre desde el listado: $this->dispatch('abrir-apertura-planilla', mes: $mes, anio: $anio) */
    #[On('abrir-apertura-planilla')]
    public function abrir(int $mes, int $anio): void
    {
        $this->resetValidation();
        $this->mes = $mes;
        $this->anio = $anio;

        $planMensual = PlanMensual::where('mes', $mes)->where('anio', $anio)->first();
        $this->planMensualId = $planMensual?->id;
        $this->dias_laborables = $planMensual?->dias_laborables;
        $this->remuneracion_basica = $planMensual?->remuneracion_basica !== null ? (float) $planMensual->remuneracion_basica : null;
        $this->excel = $planMensual?->excel;
        $this->cargarParametros();

        $this->showingAperturaPlanilla = true;
    }

    /** Resumen de los parámetros del mes (se refresca al editar uno desde el modal de parámetros). */
    #[On('parametroGuardado')]
    public function cargarParametros(): void
    {
        PlanillaParametroConsulta::olvidar();
        $valores = PlanillaParametroConsulta::delMes($this->mes, $this->anio);
        $this->parametrosMes = array_map(fn($c) => [
            'codigo' => $c,
            'nombre' => PlanillaParametroReglas::nombre($c),
            'texto' => PlanillaParametroReglas::formato($c, $valores[$c]),
            'falta' => $valores[$c] === null,
        ], PlanillaParametroReglas::codigos('agraria'));
        $afp = PlanillaParametroConsulta::afpDelMes($this->mes, $this->anio);
        $this->afpMes = $afp->map(fn($a) => ['referencia' => $a->referencia, 'comision_flujo' => (float) $a->comision_flujo,
            'comision_saldo' => (float) $a->comision_saldo, 'prima_seguros' => (float) $a->prima_seguros,
            'aporte_obligatorio' => (float) $a->aporte_obligatorio])->values()->all();
        $this->afpFaltan = array_values(array_diff(PlanillaParametroReglas::AFP, $afp->pluck('referencia')->all()));
    }

    /** Botón "Calcular" del sueldo base: RMV del mes / 30 × días del mes. */
    public function calcularSueldoBase(): void
    {
        try {
            $this->remuneracion_basica = PlanillaMensualServicio::obtenerSueldoBase($this->mes, $this->anio);
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    /** Guarda lo propio del mes antes de generar. */
    protected function guardarApertura(): PlanMensual
    {
        $this->validate();
        return PlanMensual::updateOrCreate(
            ['mes' => $this->mes, 'anio' => $this->anio],
            [
                'dias_laborables' => $this->dias_laborables,
                'total_horas' => $this->dias_laborables * 8,
                'remuneracion_basica' => $this->remuneracion_basica,
            ]
        );
    }

    /** Botón "Generar planilla": guarda la apertura, calcula a todos y arma el Excel. */
    public function generarPlanilla(): void
    {
        try {
            $planMensual = $this->guardarApertura();
            $this->planMensualId = $planMensual->id;

            $resultado = app(PlanillaServicio::class)->generarProyeccion($this->mes, $this->anio);

            if (!empty($resultado['errores']) || $resultado['procesados'] < $resultado['total']) {
                $detallesError = !empty($resultado['resumen'])
                    ? implode(' | ', array_column($resultado['resumen'], 'mensaje'))
                    : "Se procesaron {$resultado['procesados']} de {$resultado['total']} empleados.";
                throw new Exception("Error al generar la proyección de la planilla: {$detallesError}");
            }

            $excelPath = app(PlanillaServicio::class)->generarExcelPlanilla($this->mes, $this->anio);
            $planMensual->update(['excel' => $excelPath]);

            $this->alert('success', 'Proyección generada correctamente', [
                'html' => "<p><strong>{$resultado['procesados']}</strong> de <strong>{$resultado['total']}</strong> empleados proyectados correctamente.</p>",
                'position' => 'center', 'toast' => false, 'timer' => null, 'showConfirmButton' => true,
            ]);

            $this->showingAperturaPlanilla = false;
            $this->dispatch('planillaGnerada');
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    public function render()
    {
        return view('livewire.planilla.apertura-planilla-modal');
    }
}
