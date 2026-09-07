<?php

namespace App\Livewire\GestionPlanilla;

use App\Models\PlanMensualPersonal;
use App\Services\Planilla\PlanillaServicio;
use App\Traits\HandlesAlerts;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class PlanillaCostoComponent extends Component
{
    use LivewireAlert, HandlesAlerts;
    public $empleados = [];
    public $mes;
    public $anio;
    protected $listeners = ['planillaGnerada' => 'cargarProyeccion'];
    public function mount($mes, $anio)
    {
        $this->mes = $mes;
        $this->anio = $anio;
        $this->cargarProyeccion();
    }
    public function cargarProyeccion()
    {
        $this->empleados = app(PlanillaServicio::class)->obtenerProyeccion($this->mes, $this->anio);
    }
    public function mostrarExplicacionSueldo(int $empleadoId): void
    {
        $empleado = PlanMensualPersonal::with('planMensual')->find($empleadoId);

        if (!$empleado) {
            return;
        }

        $horasMeta = $empleado->planMensual?->total_horas ?? 0;
        $horasTrabajadas = $empleado->plame_total_horas ?? 0;
        $sueldoAcordado = number_format($empleado->proyectado_sueldo_neto_total ?? 0, 2);
        $sueldoPagado = number_format($empleado->sueldo_pagado, 2);

        $explicacion = "
        <div class='text-left text-sm space-y-1'>
            <p>El cálculo del sueldo pagado se realiza de manera proporcional a las horas trabajadas:</p>
            <ul class='list-disc pl-5 space-y-1'>
                <li><b>Sueldo Acordado (100%):</b> S/ {$sueldoAcordado}</li>
                <li><b>Horas Meta del Mes:</b> {$horasMeta} hrs</li>
                <li><b>Horas Trabajadas:</b> {$horasTrabajadas} hrs</li>
            </ul>
            <p class='font-mono bg-gray-100 rounded text-xs'>
                Fórmula: (S/ {$sueldoAcordado} / {$horasMeta} hrs) × {$horasTrabajadas} hrs
            </p>
            <p class='text-base font-bold text-green-600'>
                Total a Pagar: S/ {$sueldoPagado}
            </p>
        </div>
    ";

        $this->infoAlert($explicacion, 'Desglose de Calculo');
    }
    public function mostrarExplicacionAportesTrabajador(int $empleadoId): void
    {
        $emp = PlanMensualPersonal::find($empleadoId);
        if (!$emp)
            return;

        $afpCom = number_format($emp->plame_descuento_0601_comision_afp_pct ?? 0, 2);
        $r5ta = number_format($emp->plame_descuento_0605_renta_5ta_retenida ?? 0, 2);
        $afpSeg = number_format($emp->plame_descuento_0606_prima_seguro_afp ?? 0, 2);
        $snp = number_format($emp->plame_descuento_0607_snp ?? 0, 2);
        $afpApo = number_format($emp->plame_descuento_0608_spp_aporte_obligatorio ?? 0, 2);
        $total = number_format($emp->aportes_trabajador, 2);

        $html = "
        <div class='text-left text-sm space-y-2'>
            <p>Retenciones de Ley (Asumidas/Retenidas en PLAME):</p>
            <ul class='list-disc pl-5 space-y-1 text-xs font-mono'>
                <li>0601 Comision AFP Porcentual: S/ {$afpCom}</li>
                <li>0605 Renta 5ta Categoría: S/ {$r5ta}</li>
                <li>0606 Prima de Seguro AFP: S/ {$afpSeg}</li>
                <li>0607 ONP / SNP: S/ {$snp}</li>
                <li>0608 SPP Aporte Obligatorio: S/ {$afpApo}</li>
            </ul>
            <hr class='my-2'>
            <p class='text-base font-bold text-blue-600'>Total Aportes Trabajador: S/ {$total}</p>
        </div>
    ";

        $this->infoAlert($html, 'Desglose: Aportes del Trabajador');
    }

    public function mostrarExplicacionAportesEmpleador(int $empleadoId): void
    {
        $emp = PlanMensualPersonal::find($empleadoId);
        if (!$emp)
            return;

        $poliza = number_format($emp->plame_aporte_empleador_0803_poliza ?? 0, 2);
        $essalud = number_format($emp->plame_aporte_empleador_0804_essalud ?? 0, 2);
        $sctr = number_format($emp->plame_aporte_empleador_0805_sctr ?? 0, 2);
        $eps = number_format($emp->plame_aporte_empleador_0810_eps ?? 0, 2);
        $total = number_format($emp->aportes_empleador, 2);

        $html = "
        <div class='text-left text-sm space-y-2'>
            <p>Aportaciones a cargo del Empleador:</p>
            <ul class='list-disc pl-5 space-y-1 text-xs font-mono'>
                <li>0803 Póliza de Seguro (D.L. 688): S/ {$poliza}</li>
                <li>0804 EsSalud / CBSSP / Agrario: S/ {$essalud}</li>
                <li>0805 SCTR Pensiones: S/ {$sctr}</li>
                <li>0810 EPS / Seguro Comp.: S/ {$eps}</li>
            </ul>
            <hr class='my-2'>
            <p class='text-base font-bold text-orange-600'>Total Aportes Empleador: S/ {$total}</p>
        </div>
    ";

        $this->infoAlert($html, 'Desglose: Aportes del Empleador');
    }
    public function render()
    {
        return view('livewire.gestion-planilla.planilla-costo-component');
    }
}