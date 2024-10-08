<?php

namespace App\Livewire;

use App\Models\Configuracion;
use App\Models\ConsolidadoRiego;
use App\Models\Cuadrillero;
use App\Models\DetalleRiego;
use App\Models\Empleado;
use App\Models\HorasAcumuladas;
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

    private function eliminarConsolidadoExistente($fecha)
    {
        ConsolidadoRiego::where('fecha', $fecha)
            ->delete();
    }
    public function consolidarRegador($documento, $fecha)
    {
        $consolidadoRiego = ConsolidadoRiego::whereDate('fecha', $fecha)->where('regador_documento', $documento)->first();

        if (!$consolidadoRiego) {
            return;
        }

        $total_horas_riego = 0;
        $total_horas_jornal = 0;
        $total_horas_observaciones = null;
        $total_horas_acumuladas = null;
        $total_minutos_jornal = 0;
        $total_minutos_observaciones = 0;
        $total_minutos_acumulados = 0;
        $hora_inicio = null;
        $hora_fin = null;
        $intervalos = [];

        $observaciones = Observacion::whereDate('fecha', $fecha)->where('documento', $documento)->get();
        $horasAcumuladas = HorasAcumuladas::whereDate('fecha_uso', $fecha)->where('documento', $documento)->get();

        $detalles = DetalleRiego::whereDate('fecha', $fecha)->where('regador', $documento)->get();
        //$total_minutos = DetalleRiego::whereDate('fecha', $fecha)->where('sh','0')->where('regador', $documento)->selectRaw('SUM(TIME_TO_SEC(total_horas) / 60) as total_minutos')->value('total_minutos');
        //$total_horas_riego_verificacion = gmdate('H:i', $total_minutos * 60);

        if ($detalles->count() > 0) {
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

                
                if($registro->sh=='0'){
                    //Solo las horas con haberes deben ser consideradas para el jornal
                    $intervalos[] = [
                        'hora_inicio'=>$registro->hora_inicio,
                        'hora_fin'=>$registro->hora_fin,
                    ];
                }

                $inicio = new \DateTime($registro->hora_inicio);
                $fin = new \DateTime($registro->hora_fin);
                $diff = $inicio->diff($fin);
                $total_horas_riego += $diff->h + ($diff->i / 60);
            }

            $hora_inicio = $horaInicioMinima;
            $hora_fin = $horaFinMaxima;
            $total_minutos_jornal = $this->calcularMinutosJornalParcial($intervalos);
           

            $total_horas_riego = gmdate('H:i', $total_horas_riego * 3600);
            /*
            Se quita la siguiente verificacion
            Ahora con el campo sh los trabajadores podran tener horas de riego registrado sin considerar sus horas de jornal
            if ($total_horas_riego != $total_horas_riego_verificacion) {
                throw new \Exception("Las horas de riego no coinciden para el regador. Verifica los detalles de riego.");
            }*/

        }
        if ($observaciones->count() > 0) {

            $horaInicioMinima = $hora_inicio;
            $horaFinMaxima = $hora_fin;

            foreach ($observaciones as $observacion) {
                // Comparamos y actualizamos los máximos si es necesario
                if ($horaInicioMinima === null || $horaInicioMinima > $observacion->hora_inicio) {
                    $horaInicioMinima = $observacion->hora_inicio;
                }
                if ($horaFinMaxima === null || $observacion->hora_fin > $horaFinMaxima) {
                    $horaFinMaxima = $observacion->hora_fin;
                }
            }

            $hora_inicio = $horaInicioMinima;
            $hora_fin = $horaFinMaxima;

            $total_minutos_observaciones = Observacion::whereDate('fecha', $fecha)->where('documento', $documento)
                ->selectRaw('SUM(TIME_TO_SEC(horas) / 60) as total_minutos')->value('total_minutos');

            $total_horas_observaciones = $this->convertirMinutosAHora($total_minutos_observaciones);
        }
        if ($horasAcumuladas->count() > 0) {
            $total_minutos_acumulados = HorasAcumuladas::whereDate('fecha_uso', $fecha)->where('documento', $documento)
                ->sum('minutos_acomulados');

            $total_horas_acumuladas = $this->convertirMinutosAHora($total_minutos_acumulados);
        }

        $minutos_jornal = $this->calcularMinutosJornal($total_minutos_jornal, $total_minutos_observaciones, $total_minutos_acumulados, $fecha, $documento);

        if ($minutos_jornal < 0) {
            $minutos_jornal = 0;
        }

        $horas_maxima_jornal = 480;

        if ($minutos_jornal > $horas_maxima_jornal) {
            $minutos_adicionales = $minutos_jornal - $horas_maxima_jornal;
            $minutos_jornal = $horas_maxima_jornal;
            $this->procesarHorasAcumuladas($documento, $fecha, $minutos_adicionales);
        } else {
            HorasAcumuladas::where('documento', $documento)->whereDate('fecha_acumulacion', $fecha)->delete();
        }

        $total_horas_jornal = $this->convertirMinutosAHora($minutos_jornal);

        // Asigna los valores a los campos, si ya existe un registro, estos campos serán actualizados
        $consolidadoRiego->hora_inicio = $hora_inicio;
        $consolidadoRiego->hora_fin = $hora_fin;
        $consolidadoRiego->total_horas_riego = $total_horas_riego;
        $consolidadoRiego->total_horas_observaciones = $total_horas_observaciones;
        $consolidadoRiego->total_horas_acumuladas = $total_horas_acumuladas;
        $consolidadoRiego->total_horas_jornal = $total_horas_jornal; // Sumar horas adicionales
        $consolidadoRiego->estado = 'consolidado';

        // Guarda o actualiza el registro
        $consolidadoRiego->save();
    }
    public function calcularMinutosJornalParcial($intervalos) {
        // Convertir los intervalos de tiempo a minutos
        if(count($intervalos)==0){
            return 0;
        }
        
        $minutos = [];
        foreach ($intervalos as $intervalo) {
            $inicio = strtotime($intervalo['hora_inicio']);
            $fin = strtotime($intervalo['hora_fin']);
            $minutos[] = [$inicio, $fin];
        }
        
    
        // Ordenar los intervalos por la hora de inicio
        usort($minutos, function($a, $b) {
            return $a[0] <=> $b[0];
        });
    
        // Unir intervalos superpuestos
        $horasUnidas = [];
        $horasUnidas[] = $minutos[0];
    
        for ($i = 1; $i < count($minutos); $i++) {
            $ultimoIntervalo = &$horasUnidas[count($horasUnidas) - 1];
    
            // Si los intervalos se superponen o tocan, los fusionamos
            if ($minutos[$i][0] <= $ultimoIntervalo[1]) {
                $ultimoIntervalo[1] = max($ultimoIntervalo[1], $minutos[$i][1]);
            } else {
                // Si no se superponen, simplemente añadimos el nuevo intervalo
                $horasUnidas[] = $minutos[$i];
            }
        }
    
        // Calcular el total de horas a partir de los intervalos unidos
        $totalMinutos = 0;
        foreach ($horasUnidas as $intervalo) {
            $totalMinutos += ($intervalo[1] - $intervalo[0]) / 60; // Convertir a minutos
        }
    
        return $totalMinutos; // Convertir minutos a horas
    }
    public function ConsolidarRegadores($fecha)
    {

        try {
            $consolidados = ConsolidadoRiego::whereDate('fecha', $fecha)->get();
            foreach ($consolidados as $consolidado) {
                $this->consolidarRegador($consolidado->regador_documento, $fecha);
            }
         
            $this->dispatch('RefrescarMapa');
            $this->alert('success', "Detalles Consolidados con éxito");

        } catch (\Throwable $th) {
            $this->alert('error', "Ocurrió un error: " . $th->getMessage());
        }


    }
    private function convertirMinutosAHora($minutos)
    {

        // Convertir minutos a horas y minutos restantes
        $horas = floor($minutos / 60);
        $minutos_restantes = $minutos % 60;

        // Devolver el resultado en formato hh:mm
        return sprintf('%02d:%02d', $horas, $minutos_restantes);
    }

    private function calcularMinutosJornal($total_minutos_jornal, $total_minutos_observaciones, $total_minutos_acumulados, $fecha, $documento)
    {
        if (!is_numeric($total_minutos_jornal) || !is_numeric($total_minutos_observaciones) || !is_numeric($total_minutos_acumulados)) {
            throw new \InvalidArgumentException('Los parámetros $total_minutos_jornal, $total_minutos_observaciones y $total_minutos_acumulados deben ser numéricos.');
        }

        // Verificar si el día es sábado
        $esSabado = \Carbon\Carbon::parse($fecha)->isSaturday();

        // Obtener el tiempo de almuerzo desde la configuración
        $tiempo_almuerzo = Configuracion::find('tiempo_almuerzo');
        $minutos_almuerzo = $tiempo_almuerzo && is_numeric($tiempo_almuerzo->valor) ? (int) $tiempo_almuerzo->valor : 0;

        if ($esSabado) {
            // Si es sábado, no descontar el almuerzo y agregar 60 minutos
            //$total_minutos_jornal += 60;
        } else {
            $consolidado = ConsolidadoRiego::where('regador_documento', $documento)
                ->whereDate('fecha', $fecha)
                ->first();

            if ($consolidado && $consolidado->descuento_horas_almuerzo != 1) {
                // Si el consolidado existe y no se ha descontado el almuerzo
                $total_minutos_jornal -= $minutos_almuerzo;
            }

        }

        return (int) $total_minutos_jornal + (int) $total_minutos_observaciones + (int) $total_minutos_acumulados;
    }

    private function procesarHorasAcumuladas($documento, $fecha, $minutos_extras)
    {
        $horasAcumuladas = HorasAcumuladas::where('documento', $documento)
            ->where('fecha_acumulacion', $fecha)
            ->first();

        if ($horasAcumuladas) {
            // Si ya existe un registro de horas acumuladas en esa fecha
            if (!$horasAcumuladas->fecha_uso) {
                // Si no ha sido usado, actualizamos el valor
                $horasAcumuladas->minutos_acomulados = $minutos_extras;
                $horasAcumuladas->save();
            } else {
                throw new \Exception('Existe un registro con horas acumuladas en la fecha: ' . $horasAcumuladas->fecha_uso);
            }
        } else {
            // Si no existe, creamos un nuevo registro de horas acumuladas
            HorasAcumuladas::create([
                'documento' => $documento,
                'fecha_acumulacion' => $fecha,
                'minutos_acomulados' => $minutos_extras
            ]);
        }
    }

    public function Desconsolidar($fecha)
    {
        ConsolidadoRiego::where('fecha', $fecha)->update(['estado' => 'noconsolidado']);
        $this->dispatch('desconsolidacion');
    }
}
