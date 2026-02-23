<?php

namespace App\Livewire;

use App\Models\Configuracion;
use App\Models\PlanDescuentoSp;
use App\Models\PlanGrupo;
use App\Models\PlanMensual;
use App\Models\PlanMensualDetalle;
use App\Models\PlanRegistroDiario;
use App\Services\Modulos\Planilla\GestionPlanilla;
use App\Services\Planilla\GenerarPlanillaMensualProceso;
use App\Services\PlanillaServicio;
use App\Services\PlanSueldoServicio;
use App\Services\RecursosHumanos\Planilla\PlanillaRegistroDiarioServicio;
use App\Support\CalculoHelper;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\QueryException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Illuminate\Support\Str;

class PlanillaBlancoDetalleComponent extends Component
{
    use LivewireAlert;
    public $mostrarDescuentosBeneficiosPlanilla = false;
    //INFORMACION DE TABLA PLANILLA BLANCO

    public $planillaMensual;
    public $mesTitulo;
    public $planillaMensualDetalle;
    public $horasExtraMaximas;
    public $porcentajeHoraExtra;
    public $porcentajeBonificacion;
    public $porcentajeAfp;
    public $porcentajeOnp;
    #region TABLA PLANILLA BLANCA
    public $mes;
    public $anio;
    public $diasLaborables;
    public $totalHoras;
    public $factorRemuneracionBasica;
    public $asignacionFamiliar;
    public $ctsPorcentaje;
    public $gratificaciones;
    public $essaludGratificaciones;
    public $rmv;
    public $beta30;
    public $essalud;
    public $vidaLey;
    public $vidaLeyPorcentaje;
    public $pensionSctr;
    public $pensionSctrPorcentaje;
    public $essaludEps;
    public $porcentajeConstante;
    public $remBasicaEssalud;
    #endregion
    public $diasMes;
    public $descuentoColores;
    public $grupoColores;
    //public $reporteTotalHorasPorMes;
    protected $listeners = ['GuardarInformacion'];
    public function mount($mes, $anio)
    {
        $this->mes = $mes;
        $this->anio = $anio;
        $this->descuentoColores = PlanDescuentoSp::get()->pluck("color", "codigo")->toArray();
        $this->grupoColores = PlanGrupo::get()->pluck("color", "codigo")->toArray();
        $this->obtenerInformacionMensual();
    }
    public function obtenerInformacionMensual()
    {
        if (!$this->mes || !$this->anio) {
            return;
        }

        // Días del mes
        $this->diasMes = Carbon::createFromDate($this->anio, $this->mes)->daysInMonth;

        // Buscar planilla blanca del mes
        $this->planillaMensual = PlanMensual::where('mes', $this->mes)
            ->where('anio', $this->anio)
            ->first();
        if (!$this->planillaMensual) {
            return;
        }

        $this->diasLaborables = $this->planillaMensual->dias_laborables;
        $this->totalHoras = $this->planillaMensual->total_horas;
        $this->factorRemuneracionBasica = $this->planillaMensual->factor_remuneracion_basica;
        $this->asignacionFamiliar = $this->planillaMensual->asignacion_familiar;
        $this->ctsPorcentaje = $this->planillaMensual->cts_porcentaje;
        $this->gratificaciones = $this->planillaMensual->gratificaciones;
        $this->essaludGratificaciones = $this->planillaMensual->essalud_gratificaciones;
        $this->rmv = $this->planillaMensual->rmv;
        $this->beta30 = $this->planillaMensual->beta30;
        $this->essalud = $this->planillaMensual->essalud;
        $this->vidaLey = $this->planillaMensual->vida_ley;
        $this->vidaLeyPorcentaje = $this->planillaMensual->vida_ley_porcentaje;
        $this->pensionSctr = $this->planillaMensual->pension_sctr;
        $this->pensionSctrPorcentaje = $this->planillaMensual->pension_sctr_porcentaje;
        $this->essaludEps = $this->planillaMensual->essalud_eps;
        $this->porcentajeConstante = $this->planillaMensual->porcentaje_constante;
        $this->remBasicaEssalud = $this->planillaMensual->rem_basica_essalud;
        $this->planillaMensualDetalle = $this->planillaMensual->detalle()->with(['planillaMensual'])->get()->map(function ($detalle) {
            $data = $detalle->toArray();
            $planilla = $detalle->planillaMensual;

            // Campos simples a redondear
            $campos = [
                'remuneracion_basica',
                'asignacion_familiar',
                //'blanco_descuento_por_faltas',
                'blanco_remuneracion_bruta',
                'blanco_descuento_onp_afp_prima',
                'blanco_cts',
                'blanco_gratificaciones',
                'blanco_essalud_gratificaciones',
                'blanco_essalud',
                'blanco_beta30',
                'blanco_vida_ley',
                'blanco_pension_sctr',
                'blanco_essalud_eps',
                'blanco_sueldo_neto',

                'sueldo_negro_total',
                'costo_total_blanco',
                'costo_total_negro',
            ];

            foreach ($campos as $campo) {
                $data[$campo] = round($data[$campo] ?? 0, 2);
            }

            // Campos con formato especial (vienen del padre)
            $data['cts_porcentaje'] = round($planilla->cts_porcentaje, 2) . '%';
            $data['gratificaciones'] = round($planilla->gratificaciones, 2) . '%';
            $data['beta30'] = round($planilla->beta30, 2);

            return $data;
        });

    }


    public function guardarPlanillaDatos()
    {
        if (!$this->planillaMensual) {
            return;
        }

        try {
            $this->planillaMensual->update([
                'dias_laborables' => $this->diasLaborables,
                'total_horas' => $this->totalHoras,
            ]);

            $this->mostrarDescuentosBeneficiosPlanilla = false;
            $this->alert("success", "Información actualizada con éxito");
        } catch (\Throwable $th) {
            $this->alert("error", "Ocurrió un error: " . $th->getMessage());
        }
    }

    public function refrescarPlanilla()
    {
        try {
            $this->obtenerInformacionMensual();
            $this->dispatch("renderTable", data: $this->planillaMensualDetalle);
        } catch (QueryException $th) {
            throw $th;
        }
    }
    public function generarPlanilla()
    {
        /*
        try {

            $parametros = [
                'mes' => $this->mes,
                'anio' => $this->anio,

                // Variables principales
                'diasLaborables' => $this->diasLaborables,
                'factorRemuneracionBasica' => $this->factorRemuneracionBasica,

                // Porcentajes
                'asignacionFamiliar' => $this->asignacionFamiliar,
                'ctsPorcentaje' => $this->ctsPorcentaje,
                'gratificacionesPorcentaje' => $this->gratificaciones,
                'essaludGratificacionesPorcentaje' => $this->essaludGratificaciones,
                'beta30Porcentaje' => $this->beta30,
                'essaludPorcentaje' => $this->essalud,
                'vidaLeyPorcentaje' => $this->vidaLeyPorcentaje,
                'pensionSctrPorcentaje' => $this->pensionSctrPorcentaje,
                'essaludEpsPorcentaje' => $this->essaludEps,
                'porcentajeConstante' => $this->porcentajeConstante,

                // Valores fijos o montos
                'rmv' => $this->rmv,
                'vidaLey' => $this->vidaLey,
                'pensionSctr' => $this->pensionSctr,
                'essaludEps' => $this->essaludEps,
                'rem_basica_essalud' => $this->remBasicaEssalud,
            ];

            $excelPath = app(GestionPlanilla::class)->generarPlanilla($parametros);

            $this->planillaMensual->excel = $excelPath;
            $this->planillaMensual->save();

            PlanillaServicio::procesarExcelPlanillaDetalle($this->planillaMensual);

            $this->obtenerInformacionMensual();
            $this->dispatch('actualizado');
            $this->dispatch("renderTable", $this->planillaMensualDetalle);
            $this->alert('success', "Planilla generada correctamente");
        } catch (QueryException $th) {
            throw $th;
        }*/
    }
    public function generarPlanillaMensual2($datos)
    {
        try {
            app(GenerarPlanillaMensualProceso::class)->ejecutar($datos, $this->mes, $this->anio);
            $this->refrescarPlanilla();
            $this->alert('success', 'Planilla Generada Correctamente');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function generarPlanillaMensual($datos)
    {
        try {
            if (!$this->planillaMensual) {
                throw new Exception("Aún no hay información");
            }
            $totalHorasMap = app(PlanillaRegistroDiarioServicio::class)
                ->obtenerTotalHorasPorMes($this->mes, $this->anio)
                ->pluck('total_horas_mes', 'plan_empleado_id');
            ;
            dd($totalHorasMap);
            $dataIds = collect($datos)->pluck('plan_empleado_id')->unique()->toArray();
            $sueldosPactados = app(PlanSueldoServicio::class)->obtenerSueldosPorMes($dataIds, $this->mes, $this->anio);
            $totalHorasBaseMes = $this->totalHoras;

            //guardamos las bonificaciones
            foreach ($datos as $data) {
                $empleadoId = $data['plan_empleado_id'];

                // Obtenemos datos de apoyo desde los mapas creados
                $horasTrabajadas = $totalHorasMap[$empleadoId] ?? 0;
                $sueldoManoMes = $sueldosPactados[$empleadoId] ?? 0;
                $sueldoPagado = CalculoHelper::calcularSueldoManoProporcional(
                    $sueldoManoMes,
                    $horasTrabajadas,
                    $totalHorasBaseMes
                );

                PlanMensualDetalle::updateOrCreate(
                    [
                        'id' => $data['id'],
                        'documento' => $data['documento'],
                        'plan_mensual_id' => $this->planillaMensual->id
                    ],
                    [
                        'bonificacion' => (float) ($data['bonificacion'] ?? 0),
                        'sueldo_negro_pagado' => $sueldoPagado,
                        'sueldo_blanco_pagado' => $data['sueldo_blanco_pagado'],
                        'total_horas' => $horasTrabajadas
                    ]
                );
            }

            $this->generarPlanilla();

            $this->alert('success', 'Registros Actualizados Correctamente.');
        } catch (Exception $ex) {
            return $this->alert('error', $ex->getMessage());
        } catch (QueryException $ex) {
            return $this->alert('error', $ex->getMessage());
        }
    }


    public function render()
    {
        return view('livewire.planilla-blanco-detalle-component');
    }
}
