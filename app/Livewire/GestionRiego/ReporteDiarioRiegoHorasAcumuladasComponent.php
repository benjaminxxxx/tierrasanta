<?php

namespace App\Livewire\GestionRiego;

use App\Models\AcumulacionUso;
use App\Models\ConsolidadoRiego;
use App\Models\ParametroTemporal;
use Exception;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Facades\DB;

class ReporteDiarioRiegoHorasAcumuladasComponent extends Component
{
    use LivewireAlert;

    public $mostrarHorasAcumuladasForm = false;
    public $acumulado = [];
    public $resumenRiego;               // 👈 faltaba declararlo
    public $origenesAcumulados = [];    // 👈 nuevo: de dónde vienen las horas

    protected $listeners = ["abrirModalHorasAcumuladas"];

    public function mount()
    {
        $this->reiniciarHorasAcumuladas();
    }

    // El nombre del parámetro debe coincidir con la clave del payload:
    // $wire.dispatch('abrirModalHorasAcumuladas', {resumenRiegoId: ...})
    public function abrirModalHorasAcumuladas($resumenRiegoId)
    {
        $this->resumenRiego = ConsolidadoRiego::find($resumenRiegoId);

        if (!$this->resumenRiego) {
            $this->alert('error', 'El resumen de riego ya no existe o fue eliminado.');
            return;
        }

        $this->reiniciarHorasAcumuladas();
        $this->cargarOrigenesAcumulados();
        $this->mostrarHorasAcumuladasForm = true;
    }

    public function reiniciarHorasAcumuladas()
    {
        $this->resetErrorBag();
        $this->acumulado = [
            'horaInicio' => '08:00',
            'horaFin' => '16:00',
            'totalHoras' => 8
        ];
    }

    // Muestra de dónde salen los minutos disponibles ANTES de consumirlos,
    // recorriendo los mismos consolidados con saldo que usa el FIFO real al registrar el uso
    public function cargarOrigenesAcumulados(): void
    {
        $this->origenesAcumulados = ConsolidadoRiego::where('trabajador_type', $this->resumenRiego->trabajador_type)
            ->where('trabajador_id', $this->resumenRiego->trabajador_id)
            ->whereRaw('minutos_acumulados > minutos_utilizados')
            ->orderBy('fecha')
            ->get()
            ->map(function ($origen) {
                $disponible = $origen->minutos_acumulados - $origen->minutos_utilizados;
                $horas = intdiv($disponible, 60);
                $mins = $disponible % 60;
                return [
                    'fecha' => $origen->fecha,
                    'minutos' => $disponible,
                    'formateado' => $horas > 0 ? "{$horas}h {$mins}min" : "{$mins}min",
                ];
            })
            ->toArray();
    }

    public function registrarUsoHorasAcumuladas()
    {
        try {
            if (!$this->resumenRiego) {
                throw new Exception("No se ha seleccionado un resumen de riego. Recargue la página.");
            }

            // 1. Calcular minutos a usar desde el formulario
            $inicio = Carbon::parse($this->acumulado['horaInicio']);
            $fin = Carbon::parse($this->acumulado['horaFin']);

            if ($fin->lte($inicio)) {
                $this->addError('acumulado.horaFin', 'La hora final debe ser mayor a la hora de inicio.');
                return;
            }

            $minutosAUsar = $inicio->diffInMinutes($fin);

            // 2. Verificar que no supere los disponibles
            $disponibles = $this->resumenRiego->minutos_disponibles;

            if ($minutosAUsar > $disponibles) {
                $this->addError('acumulado.horaFin', "Solo tienes {$this->resumenRiego->disponible_formateado} disponibles.");
                return;
            }
            $minutosJornal = $this->resumenRiego->minutos_jornal;

            $limite = ParametroTemporal::limiteMinutosDiarios($this->resumenRiego->fecha);
            $total = $minutosJornal + $minutosAUsar;

            if ($total > $limite) {
                $horasActuales = intdiv($minutosJornal, 60);
                $minsActuales = $minutosJornal % 60;
                $horasAgregar = intdiv($minutosAUsar, 60);
                $minsAgregar = $minutosAUsar % 60;
                $exceso = $total - $limite;
                $horasExceso = intdiv($exceso, 60);
                $minsExceso = $exceso % 60;

                $mensaje =
                    "Actualmente tiene {$horasActuales}h {$minsActuales}m de jornal. " .
                    "Al intentar añadir {$horasAgregar}h {$minsAgregar}m, " .
                    "se excede el límite de 8h por {$horasExceso}h {$minsExceso}m.";

                throw new Exception($mensaje);
            }

            // 3. Verificar que no exista ya un registro de acumulación para este consolidado
            $yaExiste = \App\Models\ReporteDiarioRiego::where('consolidado_id', $this->resumenRiego->id)
                ->where('por_acumulacion', true)
                ->exists();

            if ($yaExiste) {
                $this->addError('acumulado.horaFin', 'Ya existe un registro de uso de horas acumuladas para este día.');
                return;
            }

            DB::transaction(function () use ($minutosAUsar, $inicio, $fin) {
                $registro = \App\Models\ReporteDiarioRiego::create([
                    'consolidado_id' => $this->resumenRiego->id,
                    'campo' => 'FDM',
                    'hora_inicio' => $inicio->format('H:i'),
                    'hora_fin' => $fin->format('H:i'),
                    'fecha' => $this->resumenRiego->fecha,
                    'documento' => '',
                    'regador' => '',
                    'tipo_labor' => 'Por Acumulación',
                    'descripcion' => 'Uso de horas acumuladas',
                    'por_acumulacion' => true,
                    'campo_campania_id' => null,
                ]);

                $pendiente = $minutosAUsar;

                ConsolidadoRiego::where('trabajador_type', $this->resumenRiego->trabajador_type)
                    ->where('trabajador_id', $this->resumenRiego->trabajador_id)
                    ->whereRaw('minutos_acumulados > minutos_utilizados')
                    ->orderBy('fecha')
                    ->each(function ($origen) use (&$pendiente, $registro) {
                        if ($pendiente <= 0) return false;

                        $disponibleOrigen = $origen->minutos_acumulados - $origen->minutos_utilizados;
                        $consumir = min($disponibleOrigen, $pendiente);

                        $origen->increment('minutos_utilizados', $consumir);

                        AcumulacionUso::updateOrCreate([
                            'consolidado_destino_id' => $this->resumenRiego->id,
                            'consolidado_origen_id' => $origen->id,
                        ], [
                            'minutos_consumidos' => $consumir,
                        ]);

                        $pendiente -= $consumir;
                    });

                app(\App\Services\Riego\ConsolidadorServicio::class)->consolidar($this->resumenRiego);
            });

            $this->resumenRiego->refresh();
            $this->cargarOrigenesAcumulados(); // refrescar el desglose por si quedó saldo
            $this->mostrarHorasAcumuladasForm = false;

            // 👇 se pasa el id para que solo se refresque el Detalle correspondiente, no todos
            $this->dispatch('registroConsolidado', resumenRiegoId: $this->resumenRiego->id);

            $this->alert('success', 'Uso de horas acumuladas registrado correctamente.');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.gestion-riego.reporte-diario-riego-horas-acumuladas-component');
    }
}