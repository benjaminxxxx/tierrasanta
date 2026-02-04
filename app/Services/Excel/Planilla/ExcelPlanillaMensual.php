<?php

namespace App\Services\Excel\Planilla;

use App\Exports\PlanillaExport;
use App\Models\PlanDescuentoSp;
use App\Models\PlanGrupo;
use App\Services\PlanillaMensualServicio;
use App\Services\PlanillaServicio;
use App\Services\RecursosHumanos\Planilla\PlanillaAsistenciaServicio;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use Exception;

class ExcelPlanillaMensual
{
    // Constructor
    public function generarPlanillaMensual(array $params)
    {
        // Extraer variables del array
        $mes = $params['mes'];
        $anio = $params['anio'];

        // VARIABLES ADICIONALES
        $asignacionFamiliar = $params['asignacionFamiliar'] ?? 0;

        // --- OBTENER DESCUENTOS AGRUPADOS ---
        $codigos = ['HAB F', 'INT F', 'PRI F', 'PRO F', 'SNP', 'HAB M', 'INT M', 'PRI M', 'PRO M'];
        $descuentosAgrupados = [];

        foreach ($codigos as $codigo) {
            $descuento = PlanDescuentoSp::buscarDescuentoSegun($codigo, $mes, $anio);

            if (!$descuento) {
                throw new Exception("No se encontró un descuento para el código: $codigo");
            }

            $descuentosAgrupados[$codigo] = $descuento->toArray();
        }

        // --- OBTENER PLANILLA DETALLADA ---
        $grupoColores = PlanGrupo::get()->pluck("color", "codigo")->toArray();

        $planillaDetalles = app(PlanillaMensualServicio::class)
            ->obtenerPlanillaXMesAnio($mes, $anio)
            ->map(function ($detalleMensual) use ($mes, $anio, $descuentosAgrupados, $grupoColores, $asignacionFamiliar) {

                $empleadoData = $detalleMensual->empleado;

                if (!$empleadoData) {
                    throw new Exception("No se encontró el empleado con DNI: {$detalleMensual->documento}");
                }

                $contrato = $empleadoData->contratos?->first();
                $sueldo = $empleadoData->sueldos?->first();

                if (!$contrato) {
                    throw new Exception("No se encontró contrato para {$empleadoData->nombres}");
                }

                if (!$sueldo) {
                    throw new Exception("No se encontró sueldo para {$empleadoData->nombres}");
                }

                $grupoCodigo = $contrato->grupo_codigo;
                $edad = $empleadoData->edadContableSegun($mes, $anio);
                $asignacionFamiliar = $empleadoData->asignacionFamiliar->count() > 0 ? $asignacionFamiliar : 0;
                $descuentoSeguro = $this->obtenerDescuentoEmpleado($empleadoData, $edad, $contrato, $descuentosAgrupados);
                $grupoColor = $grupoColores[$grupoCodigo] ?? '#ffffff';
                $sueldoPersonal = $sueldo->sueldo;

                return [
                    'dni' => $detalleMensual->documento,
                    'nombres' => $detalleMensual->nombres,
                    'edad' => $edad,
                    'sppSnp' => $contrato->plan_sp_codigo,
                    'bonificacion' => $detalleMensual->bonificacion,
                    'asignacionFamiliar' => $asignacionFamiliar,
                    'compensacionVacacional' => $contrato->compensacion_vacacional,
                    'descuentoSeguro' => $descuentoSeguro,
                    'grupoColor' => $grupoColor,
                    'sueldoPersonal' => $sueldoPersonal,
                    'totalHoras' => 0,
                    'estaJubilado' => $contrato->esta_jubilado == '1' ? 'SI' : '',
                    'color' => $descuentosAgrupados[$contrato->plan_sp_codigo]['descuento_sp']['color'],
                ];
            });

        // --- ARMAR DATA FINAL ---
        $asistencias = collect($planillaDetalles)->sortBy('nombres')->values()->toArray();
        $bonos = PlanillaServicio::obtenerBonosPlanilla($anio, $mes);
        
        $horas = app(PlanillaAsistenciaServicio::class)->obtenerHorasCompleto($mes,$anio);
         
        $data = [
            ...$params, // <<-- incluye todo el array original
            'empleados' => $asistencias,
            'descuentosAfp' => $descuentosAgrupados,
            'horas' => $horas,
            'bonos' => $bonos,
        ];

        $filePath = 'planilla/' . date('Y-m') . '/planilla_' . Str::slug("{$mes}_{$anio}") . '.xlsx';
        Excel::store(new PlanillaExport($data), $filePath, 'public');
        //dd($filePath);
        return $filePath;
    }
    function obtenerDescuentoEmpleado($empleadoData, $edad, $contrato, $descuentosAgrupados)
    {
        $empleadoEstaJubilado = $contrato->esta_jubilado;
        $codigo = $contrato->plan_sp_codigo;
        if (trim($codigo) == '') {
            throw new Exception("Contratación Inválida para un trabajador");
        }
        $descuento = $descuentosAgrupados[$codigo] ?? null;

        if (!$descuento) {
            throw new Exception("No se encontró un descuento precargado para el código: {$codigo}");
        }

        if ($empleadoEstaJubilado == '1') {
            return [
                'explicacion' => 'POR SER PENSIONISTA NO TIENE RETENCIÓN',
                'descuento' => 0,
            ];
        } elseif ($edad > 65) {
            if ($codigo == 'SNP') {
                return [
                    'explicacion' => 'MAYOR DE 65 EXONERADOS DE PRIMA, POR SER ONP NO TIENE PRIMA',
                    'descuento' => $descuento['porcentaje_65'],
                ];
            }
            return [
                'explicacion' => 'MAYOR DE 65 EXONERADOS DE PRIMA',
                'descuento' => $descuento['porcentaje_65'],
            ];
        } else {
            return [
                'explicacion' => '',
                'descuento' => $descuento['porcentaje'],
            ];
        }
    }
}