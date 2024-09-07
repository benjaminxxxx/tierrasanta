<?php

namespace App\Livewire;

use App\Models\ConsolidadoRiego;
use App\Models\Cuadrillero;
use App\Models\DetalleRiego;
use App\Models\Empleado;
use App\Models\Observacion;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ConsolidarRegadoresComponent extends Component
{
    use LivewireAlert;
    protected $listeners = ['ConsolidarRegadores', 'Desconsolidar'];
    public function render()
    {
        return view('livewire.consolidar-regadores-component');
    }
    private function obtenerDetallesGlobales()
    {
        return DetalleRiego::where('fecha', $this->fecha)
            ->groupBy('regador')
            ->select('regador')
            ->get();
    }
    private function obtenerNombreRegador($documento)
    {
        return optional(Empleado::where('documento', $documento)->first())->nombre_completo
            ?? Cuadrillero::where('dni', $documento)->value('nombre_completo')
            ?? 'NN';
    }
    private function eliminarConsolidadoExistente($documento, $fecha)
    {
        ConsolidadoRiego::where('regador_documento', $documento)
            ->where('fecha', $fecha)
            ->delete();
    }
    public function ConsolidarRegadores($fecha)
    {
        $this->fecha = $fecha;
        try {
            $detallesGlobal = $this->obtenerDetallesGlobales();

            if ($detallesGlobal->isNotEmpty()) {
                foreach ($detallesGlobal as $detalle) {
                    $documento = $detalle->regador;
                    $fecha = $this->fecha;
                    $nombre = $this->obtenerNombreRegador($documento);

                    $query = DetalleRiego::query()
                        ->where('regador', $documento)
                        ->whereDate('fecha', $fecha)
                        ->orderBy('hora_inicio');

                    // Obtener los detalles
                    $detalles = $query->get();
                    $total_minutos = $query->selectRaw('SUM(TIME_TO_SEC(total_horas) / 60) as total_minutos')->value('total_minutos');
                    $total_horas_riego_verificacion = gmdate('H:i', $total_minutos * 60);




                    $this->eliminarConsolidadoExistente($documento, $this->fecha);

                    if ($detalles->count() == 0) {
                        continue;
                    }

                    $horaInicioMinima = null;
                    $horaFinMaxima = null;

                    foreach ($detalles as $registro) {
                        // Comparamos y actualizamos los máximos si es necesario
                        if ($horaInicioMinima === null || $horaInicioMinima > $registro->hora_inicio) {
                            $horaInicioMinima = $registro->hora_inicio;
                        }
                        if ($horaFinMaxima === null || $registro->hora_fin > $horaFinMaxima) {
                            $horaFinMaxima = $registro->hora_fin;
                        }
                    }

                    $hora_inicio = $horaInicioMinima;
                    $hora_fin = $horaFinMaxima;

                    // Cálculo del total de horas riego considerando los solapamientos
                    $total_horas_riego = 0;

                    $total_minutos_jornal = 0;
                    $total_horas_jornal = 0;
                    $intervalos = [];

                    foreach ($detalles as $detalle) {
                        $inicio = new \DateTime($detalle->hora_inicio);
                        $fin = new \DateTime($detalle->hora_fin);
                        $diff = $inicio->diff($fin);
                        $total_horas_riego += $diff->h + ($diff->i / 60);

                        // Verificar si hay solapamientos
                        $nuevo_intervalo = ['inicio' => $inicio, 'fin' => $fin];



                        $solapado = false;

                        foreach ($intervalos as $key => $intervalo) {
                            if ($inicio <= $intervalo['fin'] && $fin >= $intervalo['inicio']) {
                                // Actualizar el intervalo solapado
                                $intervalos[$key]['inicio'] = min($intervalo['inicio'], $inicio);
                                $intervalos[$key]['fin'] = max($intervalo['fin'], $fin);
                                $solapado = true;
                                break;
                            }
                        }

                        if (!$solapado) {
                            // Añadir un nuevo intervalo si no hay solapamientos
                            $intervalos[] = $nuevo_intervalo;
                        }
                    }

                    $total_horas_riego = gmdate('H:i', $total_horas_riego * 3600);

                    if ($total_horas_riego != $total_horas_riego_verificacion) {
                        throw new \Exception("Las horas de riego no coinciden para el regador {$nombre}. Verifica los detalles de riego.");
                    }

                    // Sumar los intervalos no solapados
                    foreach ($intervalos as $intervalo) {
                        // Obtener la diferencia entre el inicio y el fin en horas y minutos
                        $diff = $intervalo['inicio']->diff($intervalo['fin']);

                        // Convertir todo a minutos (horas * 60) + minutos
                        $total_minutos_jornal += ($diff->h * 60) + $diff->i;
                    }

                    $observacionesMinutos = Observacion::where('documento', $documento)
                        ->where('fecha', $fecha)
                        ->selectRaw('SUM(TIME_TO_SEC(horas) / 60) as total_minutos')->value('total_minutos');


                    $total_horas_jornal = (int) $total_minutos_jornal + (int) $observacionesMinutos; // Suma total en minutos

                    $horas = floor($total_horas_jornal / 60); // Horas completas
                    $minutos_restantes = $total_horas_jornal % 60; // Minutos restantes

                    // Formatear en HH:MM
                    $total_horas_jornal_formateado = sprintf('%02d:%02d', $horas, $minutos_restantes);


                    ConsolidadoRiego::create([
                        'regador_documento' => $documento,
                        'regador_nombre' => $nombre, // Asumiendo que tienes el nombre del regador
                        'fecha' => $fecha,
                        'hora_inicio' => $hora_inicio,
                        'hora_fin' => $hora_fin,
                        'total_horas_riego' => $total_horas_riego, // Convertir a formato H:i
                        'total_horas_jornal' => $total_horas_jornal_formateado, // Sumar horas adicionales
                        'estado' => 'consolidado',
                    ]);
                }
                $this->dispatch('RefrescarMapa');
                $this->alert('success', "Detalles Consolidados con éxito");
            } else {
                $this->alert('error', "No hay detalles para Consolidar");
            }
        } catch (\Throwable $th) {
            $this->alert('error', "Ocurrió un error: " . $th->getMessage());
        }
    }
    public function Desconsolidar($fecha)
    {
        ConsolidadoRiego::where('fecha', $fecha)->update(['estado' => 'noconsolidado']);
        $this->dispatch('desconsolidacion');
    }
}
