<?php

namespace App\Livewire\GestionRiego;

use App\Models\Configuracion;
use App\Models\ConsolidadoRiego;
use App\Models\HorasAcumuladas;
use App\Models\ReporteDiarioRiego;
use App\Support\CalculoHelper;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ConsolidarRegadoresComponent extends Component
{
    use LivewireAlert;
    protected $listeners = ['consolidarRegador', 'Desconsolidar', 'consolidarRegadorMasivo'];
    public function render()
    {
        return view('livewire.consolidar-regadores-component');
    }
    public function consolidarRegadorMasivo($data)
    {

        foreach ($data as $fechaKey => $documentosArray) {
            if (is_array($documentosArray) && count($documentosArray) > 0) {
                foreach ($documentosArray as $documentoValor => $opcional) {
                    $this->consolidarRegador($documentoValor, $fechaKey);
                }
            }
        }
    }
    public function consolidarRegador($resumenId)
    {
        $consolidadoRiego = ConsolidadoRiego::find($resumenId);

        if (!$consolidadoRiego) {
            return;
        }

        $minutos_regados = 0;
        $total_minutos_jornal = 0;
        $total_minutos_observaciones = 0;
        $total_minutos_acumulados = 0;
        $hora_inicio = null;
        $hora_fin = null;
        $intervalos = [];

        $reporteDiarioRiego = $consolidadoRiego->registrosDiarios;

        foreach ($reporteDiarioRiego as $registro) {
            $inicio = new \DateTime($registro->hora_inicio);
            $fin = new \DateTime($registro->hora_fin);
            $diff = $inicio->diff($fin);

            // Definir el mínimo de hora de inicio y máximo de hora fin
            if ($hora_inicio === null || $hora_inicio > $registro->hora_inicio) {
                $hora_inicio = $registro->hora_inicio;
            }
            if ($hora_fin === null || $registro->hora_fin > $hora_fin) {
                $hora_fin = $registro->hora_fin;
            }

            if ($registro->sh == '0') {
                // Solo las horas con sh = 0 se consideran para el jornal
                $intervalos[] = [
                    'hora_inicio' => $registro->hora_inicio,
                    'hora_fin' => $registro->hora_fin,
                ];
            }

            if (mb_strtolower($registro->tipo_labor) === 'riego') {
                $minutos_regados += $diff->h * 60 + $diff->i;
            } else {
                // Cálculo para observaciones (no "Riego")
                $total_minutos_observaciones += $diff->h * 60 + $diff->i;
            }
        }

        // Si existen intervalos válidos para el jornal, calculamos los minutos de jornal
        if (!empty($intervalos)) {
            $total_minutos_jornal = CalculoHelper::calcularMinutosJornalParcial($intervalos);
        }

       

        // Calcular las horas totales del jornal con las observaciones y acumuladas
        $minutos_jornal = $this->calcularMinutosJornal($total_minutos_jornal, $total_minutos_acumulados, $consolidadoRiego);

        if ($minutos_jornal < 0) {
            $minutos_jornal = 0;
        }

        // Máximo permitido para el jornal es de 8 horas (480 minutos)
        $horas_maxima_jornal = 480;
        $minutos_adicionales = 0;

        if ($minutos_jornal > $horas_maxima_jornal) {
            $minutos_adicionales = $minutos_jornal - $horas_maxima_jornal;
            $minutos_jornal = $horas_maxima_jornal;
        } 

        $total_horas_jornal = $this->convertirMinutosAHora($minutos_jornal);

        $consolidadoRiego->hora_inicio = $hora_inicio;
        $consolidadoRiego->hora_fin = $hora_fin;
        $consolidadoRiego->minutos_regados = $minutos_regados;
        $consolidadoRiego->total_horas_observaciones = $this->convertirMinutosAHora($total_minutos_observaciones);
        $consolidadoRiego->minutos_acumulados = $minutos_adicionales;
        $consolidadoRiego->total_horas_jornal = $total_horas_jornal;
        $consolidadoRiego->estado = 'consolidado';
        $consolidadoRiego->save();
        $this->dispatch('registroConsolidado');
    }
    private function convertirMinutosAHora($minutos)
    {
        $horas = floor($minutos / 60);
        $minutos_restantes = $minutos % 60;
        return sprintf('%02d:%02d', $horas, $minutos_restantes);
    }
    /*
    public function calcularMinutosJornalParcial($intervalos)
    {
        // Convertir los intervalos de tiempo a minutos
        if (count($intervalos) == 0) {
            return 0;
        }

        $minutos = [];
        foreach ($intervalos as $intervalo) {
            $inicio = strtotime($intervalo['hora_inicio']);
            $fin = strtotime($intervalo['hora_fin']);
            $minutos[] = [$inicio, $fin];
        }


        // Ordenar los intervalos por la hora de inicio
        usort($minutos, function ($a, $b) {
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
  */
    private function calcularMinutosJornal($total_minutos_jornal, $total_minutos_acumulados, $consolidadoRiego)
    {
        if (!is_numeric($total_minutos_jornal) || !is_numeric($total_minutos_acumulados)) {
            throw new \InvalidArgumentException('Los parámetros $total_minutos_jornal y $total_minutos_acumulados deben ser numéricos.');
        }

        // Obtener el tiempo de almuerzo desde la configuración
        $tiempo_almuerzo = Configuracion::find('tiempo_almuerzo');
        $minutos_almuerzo = $tiempo_almuerzo && is_numeric($tiempo_almuerzo->valor) ? (int) $tiempo_almuerzo->valor : 0;


        if ($consolidadoRiego && $consolidadoRiego->descuento_horas_almuerzo != false) {
            // Si el consolidado existe y no se ha descontado el almuerzo
            $total_minutos_jornal -= $minutos_almuerzo;
        }

        return (int) $total_minutos_jornal + (int) $total_minutos_acumulados;
    }
    private function procesarHorasAcumuladas($documento, $fecha, $minutos_extras)
    {
        $horasAcumuladas = HorasAcumuladas::where('documento', $documento)
            ->where('fecha_acumulacion', $fecha)
            ->first();

        if ($horasAcumuladas) {
            if (!$horasAcumuladas->fecha_uso) {
                $horasAcumuladas->minutos_acomulados = $minutos_extras;
                $horasAcumuladas->save();
            } else {
                throw new \Exception('Existe un registro con horas acumuladas en la fecha: ' . $horasAcumuladas->fecha_uso);
            }
        } else {
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
