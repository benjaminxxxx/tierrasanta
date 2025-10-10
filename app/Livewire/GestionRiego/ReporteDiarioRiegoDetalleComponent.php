<?php

namespace App\Livewire\GestionRiego;

use App\Models\ConsolidadoRiego;
use App\Models\Cuadrillero;
use App\Models\PlanEmpleado;
use App\Services\Campo\Riego\RiegoServicio;
use Illuminate\Support\Str;
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
    public $idTable;

    protected $listeners = ["storeTableData", "registroConsolidado"];
    public function mount()
    {
        $this->idTable = 'componenteTable' . Str::random(5);
        $this->tipoLabores = LaboresRiego::pluck('nombre_labor')->toArray();
        array_unshift($this->tipoLabores, '');

        $this->campos = Campo::pluck('nombre')->toArray();
        array_unshift($this->campos, '');

        $this->obtenerRegadores();
    }
    public function registroConsolidado()
    {
        $this->obtenerRegadores();
        $this->dispatch('actualizarGrilla-' . $this->idTable, $this->registros);
    }
    public function render()
    {
        return view('livewire.gestion-riego.reporte-diario-riego-detalle-component');
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
                    'hora_inicio' => str_replace(':', '.', substr($registro->hora_inicio, 0, 5)), // Cambia ":" por "."
                    'hora_fin' => str_replace(':', '.', substr($registro->hora_fin, 0, 5)),       // Cambia ":" por "."
                    'total_horas' => str_replace(':', '.', substr($registro->total_horas, 0, 5)), // Cambia ":" por "."
                    'tipo_labor' => $registro->tipo_labor,
                    'descripcion' => $registro->descripcion,
                    'sh' => $registro->sh ? true : false, // Convertir 0 o 1 a true o false
                ];
            })
            ->toArray();
    }
    public function updatedNoDescontarHoraAlmuerzo()
    {
        if (!$this->regador) {
            return $this->alert('error', 'Selecciona el regador primero');
        }

        if (!$this->fecha) {
            return $this->alert('error', 'Digite alguna fecha válida');
        }

        ConsolidadoRiego::where('regador_documento', $this->regador)->whereDate('fecha', $this->fecha)->update([
            'descuento_horas_almuerzo' => $this->noDescontarHoraAlmuerzo ? 1 : 0
        ]);
        $this->resetear();
    }
    public function resetear()
    {
        $data = [
            'fecha' => $this->fecha,
            'documento' => $this->regador,
        ];
        $this->dispatch('Desconsolidar', $data);
    }
    public function storeTableData($data)
    {
        DB::beginTransaction();

        try {
            if (!is_array($data) || count($data) == 0) {
                $data = [];
            }

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
                $tipo_labor =  isset($row[4]) ? (trim($row[4]) != '' ? $row[4] : 'Riego') : 'Riego';
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
            $this->dispatch('consolidarRegador', $this->regador, $this->fecha);
            //$this->consolidarRegador($this->regador, $this->fecha);
            //$this->obtenerRegadores(); despues de consolidarRegador este emitira une vento y esperar ese evento para traer obtener regadores
            //

            DB::commit();

            $this->alert("success", "Registro Guardado");
        } catch (\Throwable $th) {
            DB::rollBack(); // Revierte la transacción en caso de error
            return $this->alert("error", $th->getMessage());
        }
    }
    private function obtenerNombreRegador($documento)
    {
        return optional(PlanEmpleado::where('documento', $documento)->first())->nombre_completo
            ?? Cuadrillero::where('dni', $documento)->value('nombres')
            ?? 'NN';
    }

    private function formatTime($time)
    {
        // Aquí puedes asegurarte de que el tiempo tenga el formato adecuado HH:mm:ss
        $date = \DateTime::createFromFormat('H.i', $time);
        return $date ? $date->format('H:i:s') : null;
    }
    public function eliminarRegador($riegoId){
        try {
            RiegoServicio::eliminarRegistroRegador($riegoId);
            $this->dispatch('registroRiegoEliminado',$riegoId);
        } catch (\Throwable $th) {
            $this->alert('error',$th->getMessage());
        }
    }
}
