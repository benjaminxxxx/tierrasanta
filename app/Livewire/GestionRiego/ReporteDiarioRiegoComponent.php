<?php

namespace App\Livewire\GestionRiego;

use App\Livewire\Traits\ConFechaReporteDia;
use App\Models\ConsolidadoRiego;
use App\Models\Cuadrillero;
use App\Models\PlanEmpleado;
use App\Models\PlanMensualDetalle;
use App\Models\ReporteDiarioRiego;
use App\Services\Campo\Riego\RiegoServicio;
use App\Services\Modulos\Planilla\GestionPlanillaReporteDiario;
use App\Services\RecursosHumanos\Personal\ActividadServicio;
use App\Services\RecursosHumanos\Planilla\PlanillaRegistroDiarioServicio;
use App\Services\Riego\VerificacionSincronizacionRiegoServicio;
use App\Traits\TieneParametrosTemporales;
use DateTime;
use Exception;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Session;

class ReporteDiarioRiegoComponent extends Component
{
    use LivewireAlert, ConFechaReporteDia, TieneParametrosTemporales;
    public $consolidados;
    public $archivoBackupHoy;
    public $tipoLabores;
    public $listaPorEnviarRegadores = [];
    public $mostrarEnvioAReporteDiario = false;
    protected array $parametros = [
        'limiteHorasDiarias' => ['tipo' => 'limite_horas_riego', 'default' => 8]
    ];
    public int $limiteHorasDiarias = 8;
    protected $listeners = ["generalActualizado", 'obtenerRiegos', 'registroRiegoEliminado', 'nuevosRegadoresHanSidoAgregados'];

    public function mount()
    {
        $this->inicializarFecha();
        $this->cargarParametros();

        // 🔧 PARCHE TEMPORAL — eliminar cuando todos los registros estén migrados
        $this->parcheMigrarTrabajadores();
        $this->parchearConsolidadoIdFaltantes();

        $this->obtenerRiegos();
        //$this->obtenerTrabajadores();
    }
    public function updated(string $propiedad): void
    {
        if (array_key_exists($propiedad, $this->parametros)) {
            $this->guardarParametro($propiedad);
        }
    }
    /*
    no consideraba las horas acumuladas, solo las horas de la jornada del día
    public function enviarRegistroDiarioRegadores()
    {

        $this->listaPorEnviarRegadores = $this->consolidados->map(function ($item) {

            // Nombre ya resuelto por tu accessor
            $nombre = $item->trabajador_nombre;

            // Determinar tipo
            $tipo = match ($item->trabajador_type) {
                \App\Models\Cuadrillero::class => 'cuadrilla',
                \App\Models\PlanEmpleado::class => 'planilla',
                default => 'desconocido'
            };

            // Sumar minutos_jornal a hora_inicio para obtener hora_fin
            $inicio = new DateTime($item->hora_inicio);
            $minutos = $item->minutos_jornal;

            $fin = (clone $inicio)->modify("+{$minutos} minutes");
            $horaFinCalculada = $fin->format('H:i:s');
            $horasDecimales = round($item->minutos_jornal / 60, 2);

            return [
                'trabajador_id' => $item->trabajador_id,
                'trabajador_name' => $nombre,
                'tipo' => $tipo,
                'hora_inicio' => $item->hora_inicio,
                'hora_fin' => $horaFinCalculada,
                'total_horas' => $horasDecimales,
                'campo' => 'FDM',
                'labor' => 81,
            ];
        });

        $this->mostrarEnvioAReporteDiario = true;
    }*/
    /*public function enviarRegistroDiarioRegadores()
    {
        $this->listaPorEnviarRegadores = collect();

        foreach ($this->consolidados as $item) {

            $nombre = $item->trabajador_nombre;

            $tipo = match ($item->trabajador_type) {
                Cuadrillero::class => 'cuadrilla',
                PlanEmpleado::class => 'planilla',
                default => 'desconocido'
            };

            // 1. Obtener registro acumulado si existe
            $registroAcumulado = ReporteDiarioRiego::where('consolidado_id', $item->id)
                ->where('por_acumulacion', true)
                ->first();

            // 2. Determinar la HORA INICIO que prevalece (la menor entre el consolidado/detalle y el acumulado)
            $horaInicioNormal = $item->hora_inicio ? Carbon::parse($item->hora_inicio) : null;
            $horaInicioAcumulado = $registroAcumulado ? Carbon::parse($registroAcumulado->hora_inicio) : null;

            if ($horaInicioNormal && $horaInicioAcumulado) {
                // Si existen ambas, tomamos la menor
                $horaInicioReal = $horaInicioNormal->lt($horaInicioAcumulado) ? $horaInicioNormal : $horaInicioAcumulado;
            } else {
                // Si solo existe una de las dos, tomamos la disponible
                $horaInicioReal = $horaInicioNormal ?? $horaInicioAcumulado;
            }

            // Si no hay hora de inicio por ningún lado, saltamos el registro
            if (!$horaInicioReal) {
                continue;
            }

            // 3. Minutos totales a reportar en la jornada (Minutos de Riego/Mantenimiento + Minutos Acumulados)
            $minutosTotales = $item->minutos_jornal;

            // 4. Calcular la HORA FIN sumando los minutos totales a la hora inicio prevalente
            $horaFinReal = (clone $horaInicioReal)->addMinutes($minutosTotales);

            // 5. PUSH de UN SOLO REGISTRO consolidado
            $this->listaPorEnviarRegadores->push([
                'trabajador_id' => $item->trabajador_id,
                'trabajador_name' => $nombre,
                'tipo' => $tipo,
                'hora_inicio' => $horaInicioReal->format('H:i:s'),
                'hora_fin' => $horaFinReal->format('H:i:s'),
                'total_horas' => round($minutosTotales / 60, 2),
                'campo' => 'FDM',
                'labor' => 81,
            ]);
        }

        $this->mostrarEnvioAReporteDiario = true;
    }*/
    public function enviarRegistroDiarioRegadores(RiegoServicio $riegoServicio)
    {
        $this->listaPorEnviarRegadores = (array) $riegoServicio->generarRegistroDiarioParaRegadores($this->fecha);
        $this->mostrarEnvioAReporteDiario = true;
    }
    public function confirmarEnvio(RiegoServicio $riegoServicio)
    {
        try {
            $riegoServicio->registrarDiarioRegadores($this->fecha, $this->listaPorEnviarRegadores);

            $this->alert('success', 'Registros Diarios Enviados Correctamente.');
            $this->mostrarEnvioAReporteDiario = false;

        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    protected function despuesFechaModificada($fecha)
    {
        $this->cargarParametros();
        $this->obtenerRiegos();
    }
    public function nuevosRegadoresHanSidoAgregados()
    {
        $this->obtenerRiegos();
    }
    public function generalActualizado()
    {
        $this->dispatch('delay-riegos');
    }
    public function registroRiegoEliminado()
    {
        $this->alert('success', 'Registro eliminado correctamente');
        $this->obtenerRiegos();
    }



    public function obtenerRiegos()
    {

        if (!$this->fecha) {
            return;
        }

        $this->consolidados = ConsolidadoRiego::whereDate('fecha', $this->fecha)->get();

    }

    public function descargarBackup()
    {
        $this->dispatch('RDRIE_descargarPorFecha', $this->fecha);
    }
    public function descargarBackupCompleto()
    {
        $this->dispatch('RDRIE_descargarBackupCompleto');
    }
    /**
     * 🔧 PARCHE TEMPORAL
     * Migra registros antiguos de reg_resumen que no tienen trabajador_id ni trabajador_type.
     * Este parche debe eliminarse cuando ya no existan registros sin el nuevo algoritmo.
     */
    private function parcheMigrarTrabajadores()
    {
        $registros = ConsolidadoRiego::where(function ($q) {
            $q->whereNull('trabajador_id')
                ->orWhere('trabajador_id', 0);
        })
            ->where(function ($q) {
                $q->whereNull('trabajador_type')
                    ->orWhere('trabajador_type', '');
            })
            ->get();

        if ($registros->isEmpty()) {
            return; // Nada que migrar
        }

        foreach ($registros as $registro) {

            $documento = $registro->regador_documento;

            if (!$documento) {
                throw new Exception("Registro ID {$registro->id} no tiene documento para migración.");
            }

            // 1️⃣ Buscar en empleados
            $empleado = PlanEmpleado::where('documento', $documento)->first();

            if ($empleado) {
                $registro->update([
                    'trabajador_id' => $empleado->id,
                    'trabajador_type' => \App\Models\PlanEmpleado::class,
                ]);
                continue;
            }

            // 2️⃣ Buscar en cuadrilleros (campo dni)
            $cuadrillero = Cuadrillero::where('dni', $documento)->first();

            if ($cuadrillero) {
                $registro->update([
                    'trabajador_id' => $cuadrillero->id,
                    'trabajador_type' => \App\Models\Cuadrillero::class,
                ]);
                continue;
            }

            // 3️⃣ No existe en ninguno → error explícito
            throw new Exception(
                "No se encontró trabajador para documento {$documento} (registro ID {$registro->id}). " .
                "Debe existir en empleados o cuadrilleros."
            );
        }

    }
    private function parchearConsolidadoIdFaltantes()
    {
        // Solo procesar registros sin asignación
        $registros = ReporteDiarioRiego::whereNull('consolidado_id')->get();

        foreach ($registros as $r) {

            // Buscar el consolidado correspondiente por documento y fecha
            $consolidado = ConsolidadoRiego::where('regador_documento', $r->documento)
                ->where('fecha', $r->fecha)
                ->first();

            if (!$consolidado) {
                throw new Exception(
                    "No existe consolidado para documento {$r->documento} en fecha {$r->fecha}"
                );
            }

            // Actualizar vínculo
            $r->update([
                'consolidado_id' => $consolidado->id
            ]);
        }
    }
    public function verificarSincronizacion()
    {
        $resultado = app(VerificacionSincronizacionRiegoServicio::class)->verificarPorFecha($this->fecha);

        $this->dispatch('registroConsolidado'); // refresca cada Detalle para que lea el nuevo valor de sincronizado

        $this->alert('success', "Verificación completa: {$resultado['ok']} sincronizados, {$resultado['desincronizados']} con diferencias, {$resultado['omitidos']} omitidos (cuadrilla).");
    }
    public function render()
    {
        return view('livewire.gestion-riego.reporte-diario-riego-component');
    }

}
