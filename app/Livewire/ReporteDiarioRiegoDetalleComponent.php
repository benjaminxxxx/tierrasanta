<?php

namespace App\Livewire;

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
    protected $listeners = ["storeTableData"];
    public function mount(){
        $this->tipoLabores = LaboresRiego::pluck('nombre_labor')->toArray();
        $this->campos = Campo::pluck('nombre')->toArray();
        $this->obtenerRegadores();
    }
    public function render()
    {
        return view('livewire.reporte-diario-riego-detalle-component');
    }
    public function obtenerRegadores(){
        if(!$this->fecha || !$this->regador){
            return;
        }
        $this->registros = ReporteDiarioRiego::where('documento', $this->regador)
            ->whereDate('fecha', $this->fecha)
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

                // Guardar nuevo registro
                ReporteDiarioRiego::create([
                    'campo' => $campo,
                    'hora_inicio' => $hora_inicio,
                    'hora_fin' => $hora_fin,
                    'total_horas' => $total_horas,
                    'documento' => $this->regador,
                    'fecha' => $this->fecha,
                    'sh' => $sin_hab,
                    'tipo_labor' => $tipo_labor,
                    'descripcion' => $descripcion,
                ]);
            }

            DB::commit(); // Confirma la transacción

            $this->alert("success", "Registro Guardado");
        } catch (\Throwable $th) {
            DB::rollBack(); // Revierte la transacción en caso de error
            return $this->alert("error", $th->getMessage());
        }
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
    private function formatTime($time)
    {
        // Aquí puedes asegurarte de que el tiempo tenga el formato adecuado HH:mm:ss
        $date = \DateTime::createFromFormat('H:i', $time);
        return $date ? $date->format('H:i:s') : null;
    }
}
