<?php

/**
 * LEGACY — estaba en App\Services\Planilla\PlanillaServicio.
 *
 * "Recalcular pagos en planilla" de la asistencia mensual: llenaba plan_registros_diarios.costo_dia y
 * plan_mensual_detalles.sueldo_real_proyectado / sueldo_real_liquidado a mano, con el valor hora
 * sueldo / (dias_laborables * 8).
 *
 * Nunca llegó a guardar el sueldo liquidado: esas dos columnas no existen en plan_mensual_detalles, así que
 * fallaba en el primer empleado (solo alcanzaba a escribir sus costo_dia).
 *
 * Reemplazado por PlanillaServicio::actualizarCostosDiarios(), que corre solo al generar la planilla
 * (generarProyeccion) solo llena costo_dia y usa las horas del mes (plan_mensuales.total_horas), igual que el sueldo pagado.
 */
    public function calcularGastosMensuales($mes, int $anio)
    {
        $planillaMensual = PlanMensual::where('mes', $mes)
            ->where('anio', $anio)
            ->with(['detalle.empleado', 'detalle.registrosDiarios'])
            ->first();

        if (!$planillaMensual) {
            throw new Exception('No hay planilla mensual generada');
        }

        $horasEsperadasMes = $planillaMensual->dias_laborables * 8;

        foreach ($planillaMensual->detalle as $detalleMensual) {
            $empleado = $detalleMensual->empleado;

            if (!$empleado) {
                continue;
            }

            // 1. Obtener Sueldo Pactado / Proyectado
            $sueldoProyectado = $empleado->sueldo($mes, $anio);

            if ($sueldoProyectado && $horasEsperadasMes > 0) {
                // Valor de 1 hora de trabajo con precisión decimal
                $valorHora = $sueldoProyectado / $horasEsperadasMes;

                // 2. Procesar y actualizar los costos diarios
                $this->procesarCostosDiarios($detalleMensual, $valorHora);

                // 3. Calcular Sueldos de Planilla (Real Proyectado vs. Liquidado)
                $sueldoLiquidado = $this->calcularSueldoRealLiquidado($detalleMensual, $valorHora);

                // 4. Guardar datos consolidados en el detalle mensual
                $detalleMensual->sueldo_real_proyectado = $sueldoProyectado;
                $detalleMensual->sueldo_real_liquidado = $sueldoLiquidado;
                $detalleMensual->save();
            }
        }
    }

    /**
     * Calcula el costo exacto de cada día según sus horas y actualiza el registro diario.
     * Retorna la suma total de esos días para control general.
     */
    private function procesarCostosDiarios($detalleMensual, float $valorHora): float
    {
        $sumaCostosDiarios = 0;

        foreach ($detalleMensual->registrosDiarios as $registroDiario) {
            // Costo del día redondeado a 2 decimales para el registro diario individual
            $costoDia = round($valorHora * $registroDiario->total_horas, 2);

            $registroDiario->costo_dia = $costoDia;
            $registroDiario->save();

            $sumaCostosDiarios += $costoDia;
        }

        return round($sumaCostosDiarios, 2);
    }

    /**
     * Calcula el sueldo liquidado directo a partir del total exacto de horas
     * para evitar imprecisiones por redondeos acumulados.
     */
    private function calcularSueldoRealLiquidado($detalleMensual, float $valorHora): float
    {
        $totalHorasTrabajadas = $detalleMensual->registrosDiarios->sum('total_horas');

        return round($valorHora * $totalHorasTrabajadas, 2);
    }
