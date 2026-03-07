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
    public $mostrarHorasAcumuladasForm = false;
    public $acumulado = [];
    public $registroDiarioAcumulado = null;
    public $noAcumularHoras = false;
    protected $listeners = ["registroConsolidado"];
    public function mount($resumenId)
    {
        $this->reiniciarHorasAcumuladas();
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
    protected function sincronizarAcumulado(): void
    {
        $this->registroDiarioAcumulado = $this->resumenRiego
            ->registrosDiarios()
            ->where('por_acumulacion', true)
            ->first();
    }
    public function abrirModalHorasAcumuladas()
    {
        $this->reiniciarHorasAcumuladas();
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
    public function registrarUsoHorasAcumuladas()
    {
        try {
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

            //$limite = 480; // 8 horas
            $limite = ParametroTemporal::limiteMinutosDiarios(
                $this->resumenRiego->fecha
            );
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
            $yaExiste = ReporteDiarioRiego::where('consolidado_id', $this->resumenRiego->id)
                ->where('por_acumulacion', true)
                ->exists();

            if ($yaExiste) {
                $this->addError('acumulado.horaFin', 'Ya existe un registro de uso de horas acumuladas para este día.');
                return;
            }

            DB::transaction(function () use ($minutosAUsar, $inicio, $fin) {
                // 4. Crear el registro diario
                $registro = ReporteDiarioRiego::create([
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

                // 5. Consumir FIFO de los consolidados con saldo disponible
                $pendiente = $minutosAUsar;

                ConsolidadoRiego::where('trabajador_type', $this->resumenRiego->trabajador_type)
                    ->where('trabajador_id', $this->resumenRiego->trabajador_id)
                    ->whereRaw('minutos_acumulados > minutos_utilizados')
                    ->orderBy('fecha')
                    ->each(function ($origen) use (&$pendiente, $registro) {
                        if ($pendiente <= 0)
                            return false;

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

                app(ConsolidadorServicio::class)->consolidar($this->resumenRiego);
            });

            $this->resumenRiego->refresh();
            $this->sincronizarAcumulado();
            $this->mostrarHorasAcumuladasForm = false;
            $this->dispatch('registroConsolidado');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
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
            });

            $this->resumenRiego->refresh();
            $this->sincronizarAcumulado();

            $this->dispatch('registroConsolidado');
            $this->alert('success', 'Horas acumuladas liberadas correctamente.');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function registroConsolidado()
    {
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
            /*
            $riegoService = app(RiegoServicio::class);
            $riegoService->procesarRegistroDiario(
                $this->resumenRiego,
                $this->fecha,
                $data
            );*/
            app(ConsolidarJornadaRiegoProceso::class)
                ->ejecutarGuardadoRegistros($this->resumenRiego, $this->fecha, $data,$this->noAcumularHoras);
            $this->sincronizarAcumulado();
            //$this->dispatch('consolidarRegador', $this->resumenRiego->id);
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
