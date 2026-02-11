<?php

namespace App\Services\Handsontable;

use App\Models\CuadRegistroDiario;
use App\Models\CuadTramoLaboral;
use App\Services\Cuadrilla\TramoLaboralServicio;
use Carbon\Carbon;

class HSTCuadrillaRegistroDiarioActividades
{
    public function generar($fecha,$tramoSeleccionadoId)
    {
        $fecha = Carbon::parse($fecha)->format('Y-m-d');
        $tramoLaboral = CuadTramoLaboral::find($tramoSeleccionadoId);
        if(!$tramoLaboral){
            throw new \Exception("Tramo laboral no encontrado.");
        }
        $listaOficial = TramoLaboralServicio::obtenerListaOficial($tramoSeleccionadoId);
        
        if(!$listaOficial || $listaOficial->isEmpty()){
            return ['data'=>[],'total_columnas'=>0];
        }

        $resultados = [];
        $maxActividades = 0;

        foreach($listaOficial as $grupo){
            foreach($grupo->cuadrilleros as $cuadrillero){
                $informacionCuadrillero = $cuadrillero->cuadrillero;
                $registroDiario = CuadRegistroDiario::where('cuadrillero_id',$informacionCuadrillero->id)
                    ->whereDate('fecha',$fecha)
                    ->where('total_horas','>',0)
                    ->where('codigo_grupo',$grupo->codigo_grupo)
                    ->first();
                    
                if(!$registroDiario){
                    continue;
                }

                $todasActividades = collect();
                foreach ($registroDiario->detalleHoras as $detalle) {
                    
                    $todasActividades->push([
                        'campo' => $detalle->campo_nombre,
                        'labor' => $detalle->codigo_labor,
                        'hora_inicio' => Carbon::parse($detalle->hora_inicio)->format('G.i'),
                        'hora_fin' => Carbon::parse($detalle->hora_fin)->format('G.i'),
                    ]);
                }

                $todasActividades = $todasActividades->sortBy('hora_inicio')->values();
                $maxActividades = max($maxActividades, $todasActividades->count());


                $fila = [
                    'cuadrillero_id' => $informacionCuadrillero->id,
                    'codigo_grupo' => $grupo->codigo_grupo, 
                    'cuadrillero_nombres' => $informacionCuadrillero->nombres,
                    'cuadrillero_dni' => $informacionCuadrillero->dni
                ];

                $totalHoras = 0;

                foreach ($todasActividades as $index => $actividad) {
                    $n = $index + 1;
                    $fila["campo_$n"] = $actividad['campo'];
                    $fila["labor_$n"] = $actividad['labor'];
                    $fila["hora_inicio_$n"] = $actividad['hora_inicio'];
                    $fila["hora_fin_$n"] = $actividad['hora_fin'];

                    // Sumar diferencia en horas
                    $inicio = Carbon::createFromFormat('G.i', $actividad['hora_inicio']);
                    $fin = Carbon::createFromFormat('G.i', $actividad['hora_fin']);
                    $horas = $inicio->diffInMinutes($fin) / 60;
                    $totalHoras += $horas;
                }

                // Redondear a 2 decimales por si acaso
                $fila["total_horas"] = round($totalHoras, 2);
                $fila["total_horas_validado"] = $registroDiario->total_horas_validado;

                $resultados[] = $fila;
            }
        }
        return [
            'data' => $resultados,
            'total_columnas' => $maxActividades
        ];
    }
}