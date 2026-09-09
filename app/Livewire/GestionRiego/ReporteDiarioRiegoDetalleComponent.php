<?php

namespace App\Livewire\GestionRiego;

use App\Models\AcumulacionUso;
use App\Models\ConsolidadoRiego;
use App\Models\ParametroTemporal;
use App\Services\Campo\Riego\RiegoServicio;
use App\Services\Riego\ConsolidadorServicio;
use App\Services\Riego\ConsolidarJornadaRiegoProceso;
use App\Traits\HandlesAlerts;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Component;
use App\Models\LaboresRiego;
use App\Models\Campo;
use App\Models\ReporteDiarioRiego;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Facades\DB;

class ReporteDiarioRiegoDetalleComponent extends Component
{
    use LivewireAlert, HandlesAlerts;
    //public $regador;
    public $tipoLabores;
    public $campos;
    public $fecha;
    public $registros;
    public $riego;
    public $idTable;
    public $resumenRiego;
    public $horasAcumuladas;
    public $registroDiarioAcumulado = null;
    public $noAcumularHoras = false;
    public bool $mostrarDetalleAcumulado = false;
    public array $detalleAcumulado = [];
    public $hora_inicio_almuerzo;
    public $hora_fin_almuerzo;
    public $mostrarExplicacion = false;
    protected $listeners = ["registroConsolidado"];
    public function mount($resumenId)
    {
        $this->resumenRiego = ConsolidadoRiego::find($resumenId);
        $this->sincronizarAcumulado();
        $this->idTable = 'componenteTable' . Str::random(5);
        $this->tipoLabores = LaboresRiego::pluck('nombre_labor')->toArray();
        array_unshift($this->tipoLabores, '');

        $this->campos = Campo::pluck('nombre')->toArray();
        array_unshift($this->campos, '');

        $this->obtenerRegistrosDiarios(false);
        if ($this->resumenRiego) {

            $this->hora_inicio_almuerzo = $this->resumenRiego->hora_inicio_almuerzo;
            $this->hora_fin_almuerzo = $this->resumenRiego->hora_fin_almuerzo;
            $this->horasAcumuladas = $this->resumenRiego->disponible_formateado;
        }

    }
    public function verDetalleAcumulado(): void
    {
        if (!$this->registroDiarioAcumulado)
            return;

        $this->detalleAcumulado = AcumulacionUso::where('consolidado_destino_id', $this->resumenRiego->id)
            ->with('consolidadoOrigen') // eager load
            ->get()
            ->map(function ($uso) {
                $origen = $uso->consolidadoOrigen;
                $horas = intdiv($uso->minutos_consumidos, 60);
                $mins = $uso->minutos_consumidos % 60;
                return [
                    'fecha' => $origen->fecha,
                    'trabajador' => $origen->trabajador_nombre,
                    'minutos' => $uso->minutos_consumidos,
                    'formateado' => $horas > 0 ? "{$horas}h {$mins}min" : "{$mins}min",
                ];
            })
            ->toArray();

        $this->mostrarDetalleAcumulado = true;
    }
    protected function sincronizarAcumulado(): void
    {
        $this->registroDiarioAcumulado = $this->resumenRiego
            ->registrosDiarios()
            ->where('por_acumulacion', true)
            ->first();
    }


    public function quitarAcumulado(int $registroId)
    {
        try {
            DB::transaction(function () use ($registroId) {
                $registro = ReporteDiarioRiego::where('id', $registroId)
                    ->where('consolidado_id', $this->resumenRiego->id)
                    ->where('por_acumulacion', true)
                    ->firstOrFail();

                // Devolver minutos a cada origen
                AcumulacionUso::where('consolidado_destino_id', $this->resumenRiego->id)
                    ->each(function ($uso) {
                        $uso->consolidadoOrigen->decrement('minutos_utilizados', $uso->minutos_consumidos);
                        $uso->delete();
                    });

                $registro->delete();
                $horaInicioAlmuerzo = $this->resumenRiego->hora_inicio_almuerzo;
                $horaFinAlmuerzo = $this->resumenRiego->hora_fin_almuerzo;
                app(ConsolidadorServicio::class)->consolidar($this->resumenRiego, $horaInicioAlmuerzo, $horaFinAlmuerzo);
            });

            $this->resumenRiego->refresh();
            $this->sincronizarAcumulado();
            $this->mostrarDetalleAcumulado = false; // ← cerrar modal
            $this->detalleAcumulado = [];           // ← limpiar datos

            $this->dispatch('registroConsolidado');
            $this->alert('success', 'Horas acumuladas liberadas correctamente.');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function registroConsolidado($resumenRiegoId = null)
    {
        if ($resumenRiegoId !== null && (int) $resumenRiegoId !== (int) $this->resumenRiego->id) {
            return; // no es mi consolidado, ignorar
        }
        $this->sincronizarAcumulado();
        $this->obtenerRegistrosDiarios();
    }

    public function obtenerRegistrosDiarios($dispatchEvent = true)
    {
        if (!$this->fecha || !$this->resumenRiego) {
            return;
        }

        $this->noAcumularHoras = $this->resumenRiego->no_acumular_horas;

        $this->registros = $this->resumenRiego->registrosDiarios()
            ->whereDate('fecha', $this->fecha)
            ->where('por_acumulacion', false)
            ->orderByRaw("CASE WHEN LOWER(tipo_labor) = 'riego' THEN 0 ELSE 1 END, tipo_labor ASC")
            ->orderBy('hora_inicio')
            ->get() // Obtienes los resultados como una colección
            ->map(function ($registro) {
                return [
                    'id' => $registro->id,
                    'campo' => $registro->campo,
                    'hora_inicio' => $registro->hora_inicio ? substr($registro->hora_inicio, 0, 5) : '', // Devuelve "08:00" directamente
                    'hora_fin' => $registro->hora_fin ? substr($registro->hora_fin, 0, 5) : '',       // Devuelve "10:00" directamente
                    'total_horas' => $registro->total_horas,
                    'tipo_labor' => $registro->tipo_labor,
                    'descripcion' => $registro->descripcion,
                    'sh' => (bool) $registro->sh,
                    'horas_ponderadas' => $registro->horas_ponderadas,
                ];
            })
            ->toArray();
        if ($dispatchEvent) {
            $this->dispatch('actualizarGrilla-' . $this->idTable, $this->registros);
        }
    }

    public function updatedNoAcumularHoras($valor)
    {
        $this->resumenRiego->update([
            'no_acumular_horas' => $valor
        ]);
        $horaInicioAlmuerzo = $this->resumenRiego->hora_inicio_almuerzo;
        $horaFinAlmuerzo = $this->resumenRiego->hora_fin_almuerzo;
        app(ConsolidadorServicio::class)->consolidar($this->resumenRiego, $horaInicioAlmuerzo, $horaFinAlmuerzo);
    }

    public function storeTableDataRegistroDiarioRiego($data)
    {
        try {

            $parametros = [
                'resumen_riego' => $this->resumenRiego,
                'fecha' => $this->fecha,
                'data' => $data,
                'hora_inicio_almuerzo' => $this->hora_inicio_almuerzo,
                'hora_fin_almuerzo' => $this->hora_fin_almuerzo,
            ];
            /*
            array:5 [▼ // app\Livewire\GestionRiego\ReporteDiarioRiegoDetalleComponent.php:190
  "resumen_riego" => 
App\Models
\
ConsolidadoRiego
 {#769 ▶}
  "fecha" => "2026-08-01"
  "data" => array:6 [▼
    0 => array:8 [▼
      0 => "A3"
      1 => "6:30"
      2 => "8:30"
      3 => 2
      4 => "Riego"
      5 => null
      6 => null
      7 => null
    ]
    1 => array:8 [▼
      0 => "A1"
      1 => "8:30"
      2 => "10:00"
      3 => 1.5
      4 => "Riego"
      5 => null
      6 => null
      7 => null
    ]
    2 => array:8 [▼
      0 => "Naranjos"
      1 => "10:00"
      2 => "12:00"
      3 => 2
      4 => "Riego"
      5 => null
      6 => null
      7 => null
    ]
    3 => array:8 [▼
      0 => "10"
      1 => "12:00"
      2 => "14:00"
      3 => 2
      4 => "Riego"
      5 => null
      6 => null
      7 => null
    ]
    4 => array:8 [▼
      0 => "9"
      1 => "14:00"
      2 => "16:00"
      3 => 2
      4 => "Riego"
      5 => null
      6 => null
      7 => null
    ]
    5 => array:8 [▼
      0 => "10"
      1 => "16:00"
      2 => "19:00"
      3 => 3
      4 => "Riego"
      5 => "Se dejo regando"
      6 => true //sin haberes, es cuando el trabajador deja regando por fuera de su hora, se necesa saber cuantas horas de riego hay pero no se considera en su jornal
      7 => null
    ]
  ]
  "hora_inicio_almuerzo" => "11:00"
  "hora_fin_almuerzo" => "11:30"
]
   */

            $conflictos = app(ConsolidarJornadaRiegoProceso::class)->ejecutarGuardadoRegistros($parametros);

            $this->sincronizarAcumulado();
            $this->dispatch('registroRegadoresActualizado', $this->resumenRiego->id);
            $this->obtenerRegistrosDiarios();

            if (!empty($conflictos)) {
                $this->errorAlert("Registro guardado, pero con avisos:\n" . implode("\n", $conflictos));
            } else {
                $this->alert('success', 'Registro Guardado');
            }
        } catch (\Throwable $th) {
            return $this->errorAlert($th);
        }
    }

    public function eliminarRegador($riegoId)
    {
        try {
            RiegoServicio::eliminarRegistroRegador($riegoId);
            $this->dispatch('registroRiegoEliminado', $riegoId);
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function render()
    {
        return view('livewire.gestion-riego.reporte-diario-riego-detalle-component');
    }
}
