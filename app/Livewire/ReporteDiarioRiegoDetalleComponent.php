<?php

namespace App\Livewire;

use App\Models\Configuracion;
use App\Models\ConsolidadoRiego;
use App\Models\Cuadrillero;
use App\Models\Empleado;
use App\Models\HorasAcumuladas;
use Livewire\Component;
use App\Models\LaboresRiego;
use App\Models\Campo;
use App\Models\ReporteDiarioRiego;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Facades\DB;

class ReporteDiarioRiegoDetalleComponent extends Component
{
    use LivewireAlert;
    public $regador;
    public $tipoLabores;
    public $campos;
    public $fecha;
    public $registros;
    public $riego;
    public $noDescontarHoraAlmuerzo;
    
    protected $listeners = ["storeTableData"];
    public function mount()
    {
        $this->tipoLabores = LaboresRiego::pluck('nombre_labor')->toArray();
        $this->campos = Campo::pluck('nombre')->toArray();
        $this->obtenerRegadores();
    }
    public function render()
    {
        return view('livewire.reporte-diario-riego-detalle-component');
    }
    
    public function obtenerRegadores()
    {
        if (!$this->fecha || !$this->regador) {
            return;
        }
        $this->riego = ConsolidadoRiego::whereDate('fecha', $this->fecha)
            ->where('regador_documento', $this->regador)
            ->first();

        $this->noDescontarHoraAlmuerzo = $this->riego ? $this->riego->descuento_horas_almuerzo == 1 ? true : false : false;

        $this->registros = ReporteDiarioRiego::where('documento', $this->regador)
            ->whereDate('fecha', $this->fecha)
            ->orderByRaw("CASE WHEN LOWER(tipo_labor) = 'riego' THEN 0 ELSE 1 END, tipo_labor ASC")
            ->orderBy('hora_inicio')
            ->get() // Obtienes los resultados como una colección
            ->map(function ($registro) {
                return [
                    'campo' => $registro->campo,
                    'hora_inicio' => substr($registro->hora_inicio, 0, 5), // Solo los primeros 5 caracteres
                    'hora_fin' => substr($registro->hora_fin, 0, 5),       // Solo los primeros 5 caracteres
                    'total_horas' => substr($registro->total_horas, 0, 5), // Solo los primeros 5 caracteres
                    'tipo_labor' => $registro->tipo_labor,
                    'descripcion' => $registro->descripcion,
                    'sh' => $registro->sh ? true : false, // Convertir 0 o 1 a true o false
                ];
            })
            ->toArray();
    }
    public function updatedNoDescontarHoraAlmuerzo(){
        if (!$this->regador) {
            return $this->alert('error', 'Selecciona el regador primero');
        }

        if (!$this->fecha) {
            return $this->alert('error', 'Digite alguna fecha válida');
        }

        ConsolidadoRiego::where('regador_documento',$this->regador)->whereDate('fecha',$this->fecha)->update([
            'descuento_horas_almuerzo'=>$this->noDescontarHoraAlmuerzo?1:0
        ]);
        $this->resetear();
    }
    public function resetear()
    {
        $data = [
            'fecha'=>$this->fecha,
            'documento'=>$this->regador,
        ];
        $this->dispatch('Desconsolidar', $data);
    }
    public function storeTableData($data)
    {
        DB::beginTransaction(); // Inicia la transacción

        try {
            if (!is_array($data) || count($data) == 0) {
                throw new \Exception("Falta información");
            }

            // Eliminar registros previos
            ReporteDiarioRiego::where('documento', $this->regador)
                ->whereDate('fecha', $this->fecha)->delete();

            foreach ($data as $row) {
                if (empty($row[0])) {
                    continue; // Salta filas sin campo
                }

                $campo = $row[0] ?? null;
                $hora_inicio = isset($row[1]) ? $this->formatTime($row[1]) : '00:00';
                $hora_fin = isset($row[2]) ? $this->formatTime($row[2]) : '00:00';
                $total_horas = isset($row[3]) ? $this->formatTime($row[3]) : '00:00';
                $tipo_labor = $row[4] ?? null;
                $descripcion = $row[5] ?? null;
                $sin_hab = isset($row[6]) ? ($row[6] ? 1 : 0) : 0;

                $regadorNombre = $this->obtenerNombreRegador($this->regador);

                // Guardar nuevo registro
                ReporteDiarioRiego::create([
                    'campo' => $campo,
                    'hora_inicio' => $hora_inicio,
                    'hora_fin' => $hora_fin,
                    'total_horas' => $total_horas,
                    'documento' => $this->regador,
                    'regador' => $regadorNombre,
                    'fecha' => $this->fecha,
                    'sh' => $sin_hab,
                    'tipo_labor' => $tipo_labor,
                    'descripcion' => $descripcion,
                ]);
            }

            $this->consolidarRegador($this->regador, $this->fecha);
            $this->obtenerRegadores();

            DB::commit(); // Confirma la transacción

            $this->alert("success", "Registro Guardado");
        } catch (\Throwable $th) {
            DB::rollBack(); // Revierte la transacción en caso de error
            return $this->alert("error", $th->getMessage());
        }
    }
    private function obtenerNombreRegador($documento)
    {
        return optional(Empleado::where('documento', $documento)->first())->nombre_completo
            ?? Cuadrillero::where('dni', $documento)->value('nombre_completo')
            ?? 'NN';
    }
    public function consolidarRegador($documento, $fecha)
    {
        $consolidadoRiego = ConsolidadoRiego::whereDate('fecha', $fecha)
            ->where('regador_documento', $documento)
            ->first();

        if (!$consolidadoRiego) {
            return;
        }

        $total_horas_riego = 0;
        $total_minutos_jornal = 0;
        $total_minutos_observaciones = 0;
        $total_minutos_acumulados = 0;
        $hora_inicio = null;
        $hora_fin = null;
        $intervalos = [];

        $reporteDiarioRiego = ReporteDiarioRiego::whereDate('fecha', $fecha)
            ->where('documento', $documento)
            ->get();

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
                $total_horas_riego += $diff->h + ($diff->i / 60);
            } else {
                // Cálculo para observaciones (no "Riego")
                $total_minutos_observaciones += $diff->h * 60 + $diff->i;
            }
        }

        // Si existen intervalos válidos para el jornal, calculamos los minutos de jornal
        if (!empty($intervalos)) {
            $total_minutos_jornal = $this->calcularMinutosJornalParcial($intervalos);
        }

        // Conversión de horas de riego a formato HH:mm
        $total_horas_riego = gmdate('H:i', $total_horas_riego * 3600);

        // Obtener las horas acumuladas si existen
        $horasAcumuladas = HorasAcumuladas::whereDate('fecha_uso', $fecha)
            ->where('documento', $documento)
            ->get();

        if ($horasAcumuladas->count() > 0) {
            $total_minutos_acumulados = $horasAcumuladas->sum('minutos_acomulados');
        }

        // Calcular las horas totales del jornal con las observaciones y acumuladas
        $minutos_jornal = $this->calcularMinutosJornal($total_minutos_jornal, $total_minutos_acumulados, $fecha, $documento);

        if ($minutos_jornal < 0) {
            $minutos_jornal = 0;
        }

        // Máximo permitido para el jornal es de 8 horas (480 minutos)
        $horas_maxima_jornal = 480;

        if ($minutos_jornal > $horas_maxima_jornal) {
            $minutos_adicionales = $minutos_jornal - $horas_maxima_jornal;
            $minutos_jornal = $horas_maxima_jornal;
            $this->procesarHorasAcumuladas($documento, $fecha, $minutos_adicionales);
        } else {
            HorasAcumuladas::where('documento', $documento)
                ->whereDate('fecha_acumulacion', $fecha)
                ->delete();
        }

        // Convertir minutos a formato HH:mm para el jornal
        $total_horas_jornal = $this->convertirMinutosAHora($minutos_jornal);

        // Asignamos los valores calculados al consolidado de riego
        $consolidadoRiego->hora_inicio = $hora_inicio;
        $consolidadoRiego->hora_fin = $hora_fin;
        $consolidadoRiego->total_horas_riego = $total_horas_riego;
        $consolidadoRiego->total_horas_observaciones = $this->convertirMinutosAHora($total_minutos_observaciones);
        $consolidadoRiego->total_horas_acumuladas = $this->convertirMinutosAHora($total_minutos_acumulados);
        $consolidadoRiego->total_horas_jornal = $total_horas_jornal;
        $consolidadoRiego->estado = 'consolidado';

        // Guardamos o actualizamos el consolidado
        $consolidadoRiego->save();
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
    private function convertirMinutosAHora($minutos)
    {

        // Convertir minutos a horas y minutos restantes
        $horas = floor($minutos / 60);
        $minutos_restantes = $minutos % 60;

        // Devolver el resultado en formato hh:mm
        return sprintf('%02d:%02d', $horas, $minutos_restantes);
    }
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
    private function calcularMinutosJornal($total_minutos_jornal, $total_minutos_acumulados, $fecha, $documento)
    {
        if (!is_numeric($total_minutos_jornal) || !is_numeric($total_minutos_acumulados)) {
            throw new \InvalidArgumentException('Los parámetros $total_minutos_jornal y $total_minutos_acumulados deben ser numéricos.');
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

        return (int) $total_minutos_jornal + (int) $total_minutos_acumulados;
    }

    private function formatTime($time)
    {
        // Aquí puedes asegurarte de que el tiempo tenga el formato adecuado HH:mm:ss
        $date = \DateTime::createFromFormat('H:i', $time);
        return $date ? $date->format('H:i:s') : null;
    }
}
