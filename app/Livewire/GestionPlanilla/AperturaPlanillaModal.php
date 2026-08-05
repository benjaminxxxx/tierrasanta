<?php

namespace App\Livewire\GestionPlanilla;

use App\Models\PlanMensual;
use App\Services\Planilla\PlanillaServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;
use App\Services\PlanillaMensualServicio;

class AperturaPlanillaModal extends Component
{
    use LivewireAlert;
    public bool $showingAperturaPlanilla = false;

    public ?int $planMensualId = null;

    public int $mes;
    public int $anio;

    // Editables por el usuario
    public ?int $dias_laborables = null;
    public ?float $remuneracion_basica = null;
    public ?string $excel = null;

    // Snapshot de configuración (bloqueados en la vista, se copian tal cual al modelo)
    public ?float $rmv = null;
    public ?float $asignacion_familiar = null;
    public ?float $gratificaciones = null;
    public ?float $essalud_gratificaciones = null;
    public ?float $beta30 = null;
    public ?float $essalud = null;
    public ?float $vida_ley = null;
    public ?float $pension_sctr = null;
    public ?float $essalud_eps = null;
    public ?float $rem_basica_essalud = null;
    public ?float $cts = null;
    
    public ?array $configuracionPendiente = null;
    public ?array $descuentosPreview = null;

    protected function rules(): array
    {
        return [
            'dias_laborables' => ['required', 'integer', 'min:1', 'max:31'],
            'remuneracion_basica' => ['nullable', 'numeric', 'min:0'],
        ];
    }


    public function actualizarDesdeConfiguracion(): void
    {
        try {
            $config = PlanillaMensualServicio::obtenerConfiguracionDesdeParametros($this->mes, $this->anio);

            $this->rmv = $config['rmv'];
            $this->asignacion_familiar = $config['asignacion_familiar'];
            $this->gratificaciones = $config['gratificaciones'];
            $this->essalud_gratificaciones = $config['essalud_gratificaciones'];
            $this->beta30 = $config['beta30'];
            $this->essalud = $config['essalud'];
            $this->vida_ley = $config['vida_ley'];
            $this->pension_sctr = $config['pension_sctr'];
            $this->essalud_eps = $config['essalud_eps'];
            $this->rem_basica_essalud = $config['rem_basica_essalud'];
            $this->cts = $config['cts'];

            // guardamos el array crudo para persistirlo recién en generarPlanilla()
            $this->configuracionPendiente = $config;

            $this->alert('success', 'Valores obtenidos desde configuración. Aún no se han guardado.');
        } catch (\Exception $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function actualizarDesdeDescuentos(): void
    {
        try {
            $registros = PlanillaMensualServicio::obtenerDescuentosVigentes($this->mes, $this->anio);

            if ($registros->isEmpty()) {
                $this->descuentosPreview = null;
                $this->alert('warning', 'No hay comisiones/primas AFP configuradas para este periodo.');
                return;
            }

            $this->descuentosPreview = $registros->toArray();

            $this->alert('success', 'Vista previa de descuentos AFP cargada. Aún no se han guardado.');
        } catch (\Exception $e) {
            $this->alert('error', $e->getMessage());
        }
    }
    /**
     * Se dispara desde el listado principal, ej:
     * $this->dispatch('abrir-apertura-planilla', mes: $mes, anio: $anio);
     */
    #[On('abrir-apertura-planilla')]
    public function abrir(int $mes, int $anio): void
    {
        $this->configuracionPendiente = null;
        $this->descuentosPreview = null;

        $this->resetValidation();

        $this->mes = $mes;
        $this->anio = $anio;

        // Si ya existe la planilla mensual de ese periodo, la carga.
        // Si no existe, queda "nueva" pero sin persistir hasta que se guarde.
        $planMensual = PlanMensual::where('mes', $mes)
            ->where('anio', $anio)
            ->first() ?? new PlanMensual(['mes' => $mes, 'anio' => $anio]);

        $this->planMensualId = $planMensual->id;
        $this->llenarDatosBase($planMensual);
        $this->llenarSnapshotConfiguracion($planMensual);

        $this->showingAperturaPlanilla = true;
    }

    protected function llenarDesdeModelo(PlanMensual $planMensual): void
    {
        $this->dias_laborables = $planMensual->dias_laborables;
        $this->remuneracion_basica = $planMensual->remuneracion_basica;
        $this->excel = $planMensual->excel;

        $this->rmv = $planMensual->rmv;
        $this->asignacion_familiar = $planMensual->asignacion_familiar;
        $this->gratificaciones = $planMensual->gratificaciones;
        $this->essalud_gratificaciones = $planMensual->essalud_gratificaciones;
        $this->beta30 = $planMensual->beta30;
        $this->essalud = $planMensual->essalud;
        $this->vida_ley = $planMensual->vida_ley;
        $this->pension_sctr = $planMensual->pension_sctr;
        $this->essalud_eps = $planMensual->essalud_eps;
        $this->rem_basica_essalud = $planMensual->rem_basica_essalud;
        $this->cts = $planMensual->cts;
    }

    /**
     * Botón "Calcular" / "Recalcular" del sueldo base.
     * Ajusta la llamada al servicio real que ya tienes para esto.
     */
    public function calcularSueldoBase(): void
    {
        $sueldoBase = PlanillaMensualServicio::obtenerSueldoBase($this->mes, $this->anio);

        $this->remuneracion_basica = $sueldoBase;
    }

    protected function llenarDatosBase(PlanMensual $planMensual): void
    {
        $this->dias_laborables = $planMensual->dias_laborables;
        $this->remuneracion_basica = $planMensual->remuneracion_basica;
        $this->excel = $planMensual->excel;
    }

    protected function llenarSnapshotConfiguracion(PlanMensual $planMensual): void
    {
        $this->rmv = $planMensual->rmv;
        $this->asignacion_familiar = $planMensual->asignacion_familiar;
        $this->gratificaciones = $planMensual->gratificaciones;
        $this->essalud_gratificaciones = $planMensual->essalud_gratificaciones;
        $this->beta30 = $planMensual->beta30;
        $this->essalud = $planMensual->essalud;
        $this->vida_ley = $planMensual->vida_ley;
        $this->pension_sctr = $planMensual->pension_sctr;
        $this->essalud_eps = $planMensual->essalud_eps;
        $this->rem_basica_essalud = $planMensual->rem_basica_essalud;
        $this->cts = $planMensual->cts;
    }
    /**
     * Persiste la apertura (días laborables, sueldo base, snapshot completo)
     * antes de disparar la generación real de la planilla.
     */
    protected function guardarApertura(): PlanMensual
    {
        $this->validate();
        return PlanMensual::updateOrCreate(
            ['mes' => $this->mes, 'anio' => $this->anio],
            [
                'dias_laborables' => $this->dias_laborables,
                'total_horas' => $this->dias_laborables * 8,
                'remuneracion_basica' => $this->remuneracion_basica,
                'rmv' => $this->rmv,
                'asignacion_familiar' => $this->asignacion_familiar,
                'gratificaciones' => $this->gratificaciones,
                'essalud_gratificaciones' => $this->essalud_gratificaciones,
                'beta30' => $this->beta30,
                'essalud' => $this->essalud,
                'vida_ley' => $this->vida_ley,
                'pension_sctr' => $this->pension_sctr,
                'essalud_eps' => $this->essalud_eps,
                'rem_basica_essalud' => $this->rem_basica_essalud,
                'cts' => $this->cts,
            ]
        );
    }

    /**
     * Botón final "Generar Planilla". Primero persiste la apertura,
     * luego dispara tu servicio de generación real.
     * Deja aquí tu llamada a PlanillaServicio::generarProyeccion() o similar.
     */
    public function generarPlanilla(): void
    {
        try {
            $planMensual = $this->guardarApertura();
            $this->planMensualId = $planMensual->id;

            // Persistir configuración general (si el usuario la trajo con el botón)
            if ($this->configuracionPendiente) {
                PlanillaMensualServicio::guardarConfiguracionEnPlanMensual($planMensual->id, $this->configuracionPendiente);
            }

            // Persistir snapshot de descuentos AFP/SNP (siempre, es obligatorio para calcular)
            PlanillaMensualServicio::snapshotDescuentosSp($planMensual->id, $this->mes, $this->anio);

            $resultado = app(PlanillaServicio::class)->generarProyeccion($this->mes, $this->anio);

            $excelPath = app(PlanillaServicio::class)->generarExcelPlanilla($this->mes, $this->anio);
            $planMensual->update(['excel'=>$excelPath]);

            $tieneErrores = !empty($resultado['errores']);

            $html = "<div style='text-align:left'>";
            $html .= "<p><strong>{$resultado['procesados']}</strong> de <strong>{$resultado['total']}</strong> empleados proyectados correctamente.</p>";

            if ($tieneErrores) {
                $html .= "<hr style='margin:10px 0'>";
                $html .= "<p style='margin-bottom:6px'><strong>No se pudieron proyectar:</strong></p>";
                $html .= "<ul style='padding-left:18px; margin:0'>";
                foreach ($resultado['resumen'] as $grupo) {
                    $html .= "<li style='margin-bottom:6px'>{$grupo['mensaje']}</li>";
                }
                $html .= "</ul>";
            }

            $html .= "</div>";

            $this->alert(
                $tieneErrores ? 'warning' : 'success',
                $tieneErrores ? 'Proyección generada con observaciones' : 'Proyección generada correctamente',
                [
                    'html' => $html,
                    'position' => 'center',
                    'toast' => false,
                    'timer' => null,
                    'showConfirmButton' => true,
                ]
            );

            $this->showingAperturaPlanilla = false;
            $this->dispatch('planillaGnerada');
        } catch (\Throwable $th) {
            $this->alert('error', 'Error al generar la proyección', [
                'text' => $th->getMessage(),
                'position' => 'center',
                'toast' => false,
            ]);
        }
    }

    public function render()
    {
        return view('livewire.gestion-planilla.apertura-planilla-modal');
    }
}