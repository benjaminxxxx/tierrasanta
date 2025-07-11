<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cuadrillero extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombres',
        'dni',
        'codigo_grupo',
        'estado'
    ];
    public function grupo(){
        return $this->belongsTo(CuaGrupo::class,'codigo_grupo');
    }
    public function determinarPago($fechaInicio, $fechaFin)
    {
        // Obtener los registros de CuadrillaHora relacionados con este cuadrillero
        $cuadrillaHoras = CuadrillaHora::whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->whereHas('asistenciaSemanalCuadrillero', function ($query) {
                $query->where('cua_id', $this->id); // Filtrar por el ID del cuadrillero actual
            })
            ->get();

        // Calcular el total de horas y el monto a pagar
        $totalHoras = $cuadrillaHoras->sum('horas');
        $montoAPagar = $cuadrillaHoras->sum(function ($hora) {
            return $hora->costo_dia + $hora->bono;
        });

        // Retornar los datos como un arreglo
        return [
            'total_horas' => $totalHoras,
            'monto_a_pagar' => $montoAPagar,
        ];
    }
    public function obtenerPago($fechaInicio, $fechaFin)
    {
        // Obtener los pagos realizados en el rango de fechas para este cuadrillero
        $pagosRealizados = PagoCuadrilla::where('cuadrillero_id', $this->id)
            ->whereDate('fecha_inicio', $fechaInicio)
            ->whereDate('fecha_fin', $fechaFin)
            ->orderBy('created_at', 'asc')
            ->get();

        // Inicializar variables
        $saldoAcumulado = 0;
        $estaCancelado = false;
        $montoPagado = 0;

        // Verificar si existen pagos realizados
        if ($pagosRealizados->isNotEmpty()) {
            // Calcular el saldo acumulado
            $saldoAcumulado = $pagosRealizados->sum('monto_pagado');

            // Buscar el primer pago con estado 'pago_completo'
            $pagoCompleto = $pagosRealizados->firstWhere('estado', 'pago_completo');

            // Determinar si está cancelado
            $estaCancelado = $pagoCompleto ? true : false;

            // Si está cancelado, el monto pagado es igual al saldo acumulado
            if ($estaCancelado) {
                $montoPagado = $saldoAcumulado;
            }
        }

        // Retornar los valores como array
        return [
            'lista_pagos' => $pagosRealizados,
            'saldo_acumulado' => $saldoAcumulado,
            'esta_cancelado' => $estaCancelado,
            'monto_pagado' => $montoPagado,
        ];
    }
    #region Atributos
    public function getGrupoActualAttribute(){
        return $this->grupo?->nombre??'-';
    }
    #endregion
}
