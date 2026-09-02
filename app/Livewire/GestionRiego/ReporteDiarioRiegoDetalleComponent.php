<?php

namespace App\Livewire\GestionRiego;

use App\Models\AcumulacionUso;
use App\Models\ConsolidadoRiego;
use App\Models\ParametroTemporal;
use App\Services\Campo\Riego\RiegoServicio;
use App\Services\Riego\ConsolidadorServicio;
use App\Services\Riego\ConsolidarJornadaRiegoProceso;
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
    use LivewireAlert;
    //public $regador;
    public $tipoLabores;
    public $campos;
    public $fecha;
    public $registros;
    public $riego;
    public $noDescontarHoraAlmuerzo;
    public $idTable;
    public $resumenRiego;
    public $horasAcumuladas;
    public $registroDiarioAcumulado = null;
    public $noAcumularHoras = false;
    public bool $mostrarDetalleAcumulado = false;
    public array $detalleAcumulado = [];
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

        $this->obtenerRegistrosDiarios();
        $this->horasAcumuladas = $this->resumenRiego->disponible_formateado;
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
                app(ConsolidadorServicio::class)->consolidar($this->resumenRiego);
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
        $this->dispatch('actualizarGrilla-' . $this->idTable, $this->registros);
    }

    public function obtenerRegistrosDiarios()
    {
        if (!$this->fecha || !$this->resumenRiego) {
            return;
        }

        $this->noDescontarHoraAlmuerzo = $this->resumenRiego->descuento_horas_almuerzo;
        $this->noAcumularHoras = $this->resumenRiego->no_acumular_horas;

        $this->registros = $this->resumenRiego->registrosDiarios()
            ->whereDate('fecha', $this->fecha)
            ->where('por_acumulacion', false)
            ->orderByRaw("CASE WHEN LOWER(tipo_labor) = 'riego' THEN 0 ELSE 1 END, tipo_labor ASC")
            ->orderBy('hora_inicio')
            ->get() // Obtienes los resultados como una colección
            ->map(function ($registro) {

                return [
                    'campo' => $registro->campo,
                    'hora_inicio' => str_replace(':', '.', substr($registro->hora_inicio, 0, 5)), // Cambia ":" por "."
                    'hora_fin' => str_replace(':', '.', substr($registro->hora_fin, 0, 5)),       // Cambia ":" por "."
                    'total_horas' => $registro->total_horas,
                    'tipo_labor' => $registro->tipo_labor,
                    'descripcion' => $registro->descripcion,
                    'sh' => $registro->sh ? true : false, // Convertir 0 o 1 a true o false
                ];
            })
            ->toArray();
    }
    public function updatedNoDescontarHoraAlmuerzo($valor)
    {
        $this->resumenRiego->update([
            'descuento_horas_almuerzo' => $valor
        ]);
        app(ConsolidadorServicio::class)->consolidar($this->resumenRiego);

    }
    public function updatedNoAcumularHoras($valor)
    {
        $this->resumenRiego->update([
            'no_acumular_horas' => $valor
        ]);
        app(ConsolidadorServicio::class)->consolidar($this->resumenRiego);
    }

    public function storeTableDataRegistroDiarioRiego($data)
    {
        try {
          
            app(ConsolidarJornadaRiegoProceso::class)
                ->ejecutarGuardadoRegistros($this->resumenRiego, $this->fecha, $data);
            $this->sincronizarAcumulado();
            $this->dispatch('registroRegadoresActualizado', $this->resumenRiego->id);
            
            $this->alert("success", "Registro Guardado");
        } catch (\Throwable $th) {
            return $this->alert("error", $th->getMessage());
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
