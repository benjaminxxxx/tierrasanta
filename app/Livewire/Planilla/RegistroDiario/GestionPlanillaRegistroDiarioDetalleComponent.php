<?php

namespace App\Livewire\Planilla\RegistroDiario;

use App\Constants\Permisos;
use App\Models\PlanResumenDiario;
use App\Services\Planilla\ListaMensual\PlanillaListaMensualProceso;
use App\Services\Planilla\ListaMensual\PlanillaListaMensualReglas;
use App\Services\Planilla\RegistroDiario\PlanillaRegistroDiarioReglas;
use App\Services\Planilla\Suspension\PlanillaSuspensionConsulta;
use App\Services\Planilla\Suspension\PlanillaSuspensionDiarioProceso;
use Illuminate\Support\Carbon;
use App\Services\Planilla\Modulos\GestionPlanillaReporteDiario;
use App\Services\Planilla\Empleado\ActividadServicio;
use App\Services\Planilla\RegistroDiario\PlanillaRegistroDiarioServicio;
use App\Traits\ListasComunes\ConArrayCampos;
use App\Traits\ListasComunes\ConArrayPlanTipoAsistencia;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

/**
 * Tabla del registro diario de planilla. Las filas viven en el navegador (Handsontable): no son propiedad pública
 * para no mandarlas de ida y vuelta en cada petición. "Guardar" envía solo las filas modificadas y las columnas.
 */
class GestionPlanillaRegistroDiarioDetalleComponent extends Component
{
    use ConArrayPlanTipoAsistencia, ConArrayCampos, LivewireAlert, \App\Traits\HandlesAlerts;
    public $fecha;
    public $resumenDiarioPlanilla;
    /** Columnas de tramos guardadas (nunca menos que los tramos con datos). */
    public $totalActividades = 1;
    /** Vacaciones, descansos y permisos que cubren la fecha (aviso arriba de la tabla) */
    public $ausencias = [];
    /** El mes no tiene lista y no se generó (sin contratados agrarios o mes lejano) */
    public $sinLista = false;
    /** Trabajadores con código de suspensión nuevo al guardar: ¿hasta cuándo? */
    public array $preguntasHasta = [];
    public bool $mostrarPreguntaHasta = false;
    /** Trabajadores a los que se les puso otro código sobre una suspensión que sigue: ¿regresó, solo hoy o error? */
    public array $preguntasRegreso = [];
    public bool $mostrarPreguntaRegreso = false;
    /** Filas de la carga inicial (solo para el primer render; no se guardan en el estado del componente). */
    protected array $empleadosIniciales = [];
    protected $listeners = ['actualizarListaPlanillaRegistroDiario' => 'recargarTabla'];

    public function mount($fecha)
    {
        $this->fecha = $fecha;
        $this->prepararListaMensual();
        $this->obtenerResumenDiarioPlanilla();
        $this->empleadosIniciales = $this->filas();
        $this->totalActividades = PlanillaRegistroDiarioReglas::totalActividades($this->totalActividades, $this->empleadosIniciales);
        $this->cargarAusencias();
    }

    /** El mes se genera solo (orden del mes anterior, contratados nuevos al final): ya no hace falta "Actualizar contratados". */
    private function prepararListaMensual(): void
    {
        $f = Carbon::parse($this->fecha);
        try {
            if (!PlanillaListaMensualProceso::existe($f->month, $f->year) && PlanillaListaMensualReglas::sePuedeGenerar($f->month, $f->year)) {
                app(PlanillaListaMensualProceso::class)->generar($f->month, $f->year);
            }
            $this->sinLista = !PlanillaListaMensualProceso::existe($f->month, $f->year);
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    public function cargarAusencias(): void
    {
        $this->ausencias = $this->fecha ? app(PlanillaSuspensionConsulta::class)->vigentesEn($this->fecha) : [];
    }

    /** Regresó antes: su suspensión termina ayer y se quitan sus V (DM…) desde hoy hasta el fin que tenía. */
    public function regresoAnticipado(int $suspensionId): void
    {
        try {
            $this->authorize(Permisos::PLANILLA_SUSPENSION_GESTIONAR);
            $r = app(PlanillaSuspensionDiarioProceso::class)->regresoAnticipado($suspensionId, $this->fecha);
            $this->obtenerResumenDiarioPlanilla();
            $this->recargarTabla();
            $this->cargarAusencias();
            $this->alert('success', "Listo: se quitaron {$r['revertidos']} día(s) del registro diario. Registra lo que hizo hoy.");
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    public function obtenerResumenDiarioPlanilla()
    {
        if (!$this->fecha) {
            return;
        }

        $this->resumenDiarioPlanilla = PlanResumenDiario::firstOrCreate(['fecha' => $this->fecha]);
        $this->totalActividades = max(1, (int) $this->resumenDiarioPlanilla->total_actividades);
    }

    private function filas(): array
    {
        try {
            return app(GestionPlanillaReporteDiario::class)->obtenerHandsontableObtenerRegistroDiarioPlanilla($this->fecha);
        } catch (\Throwable $th) {
            $this->errorAlert($th);
            return [];
        }
    }

    /** Vuelve a leer la tabla desde la base (lista reordenada, regreso anticipado…) y la manda al navegador. */
    public function recargarTabla(): void
    {
        $filas = $this->filas();
        $this->totalActividades = PlanillaRegistroDiarioReglas::totalActividades(
            (int) ($this->resumenDiarioPlanilla?->total_actividades ?? 1), $filas);
        $this->dispatch('setEmpleados', filas: $filas, totalActividades: $this->totalActividades);
    }

    /**
     * Guarda las filas modificadas y el número de columnas. Se puede guardar solo el cambio de columnas.
     * Devuelve true si guardó (el navegador limpia sus marcas de cambios solo entonces).
     */
    public function guardarInformacionRegistroPlanilla($datos, $totalActividades = null): bool
    {
        if (!$this->fecha || !$this->resumenDiarioPlanilla || !is_array($datos)) {
            return false;
        }

        try {
            $this->authorize(Permisos::PLANILLA_ACTIVIDAD_GESTIONAR);
            $columnas = PlanillaRegistroDiarioReglas::totalActividades((int) ($totalActividades ?? $this->totalActividades), $datos);
            $cambianColumnas = $columnas !== (int) $this->resumenDiarioPlanilla->total_actividades;

            if (count($datos) === 0 && !$cambianColumnas) {
                $this->alert('info', 'No hay cambios por guardar.');
                return true;
            }

            if (count($datos) > 0) {
                $antes = \App\Models\PlanRegistroDiario::where('fecha', $this->fecha)
                    ->whereIn('plan_det_men_id', array_filter(array_column($datos, 'plan_men_detalle_id')))
                    ->pluck('asistencia', 'plan_det_men_id');
                // Antes de guardar: quienes tenían una suspensión que sigue después de hoy y se les puso otro código
                $interrumpidas = $this->suspensionesInterrumpidas($datos, $antes->all());
                app(PlanillaRegistroDiarioServicio::class)->guardarRegistrosDiarios($this->fecha, $datos, $columnas);
                $this->prepararPreguntaHasta($datos, $antes->all());
                $this->prepararPreguntaRegreso($interrumpidas);
            }
            $this->resumenDiarioPlanilla->update(['total_actividades' => $columnas]);
            $this->obtenerResumenDiarioPlanilla();
            if (count($datos) > 0) {
                ActividadServicio::detectarYCrearActividades($this->fecha);
                $this->cargarAusencias();
            }
            // Si ese día hubo riego, se vuelve a verificar solo (tarea "Registros de riego desincronizados")
            $aviso = count($datos) > 0
                ? app(\App\Services\Riego\VerificacionSincronizacionRiegoServicio::class)->verificarDespuesDeGuardar($this->fecha, \App\Models\PlanEmpleado::class)
                : null;
            $aviso ? $this->alert('warning', "Registros guardados. {$aviso}", ['timer' => 8000])
                : $this->alert('success', count($datos) > 0 ? 'Registros guardados correctamente.' : 'Columnas guardadas.');

            return true;
        } catch (\Throwable $th) {
            $this->errorAlert($th);
            return false;
        }
    }

    // ------------------------------------------------------------------ ¿hasta cuándo? (vacaciones, DM, licencias)

    /**
     * Quienes recibieron hoy un código que es suspensión (V, DM, LSG…): al guardar se creó o alargó su suspensión
     * solo con este día. Se pregunta hasta cuándo para marcar solos los días siguientes.
     */
    private function prepararPreguntaHasta(array $datos, array $antes): void
    {
        $tipoPorCodigo = \App\Models\PlanTipoAsistencia::whereNotNull('plan_tipo_suspension_id')
            ->whereNotIn('codigo', PlanillaRegistroDiarioReglas::SIN_PREGUNTA_HASTA)
            ->pluck('plan_tipo_suspension_id', 'codigo');
        $preguntas = [];
        foreach ($datos as $f) {
            $codigo = trim((string) ($f['asistencia'] ?? ''));
            $detalle = $f['plan_men_detalle_id'] ?? null;
            if (!$detalle || !isset($tipoPorCodigo[$codigo]) || trim((string) ($antes[$detalle] ?? '')) === $codigo) {
                continue;
            }
            $empleadoId = (int) ($f['plan_empleado_id'] ?? \App\Models\PlanMensualDetalle::whereKey($detalle)->value('plan_empleado_id'));
            $s = \App\Models\PlanSuspension::with('tipoSuspension')
                ->where('plan_empleado_id', $empleadoId)
                ->where('tipo_suspension_id', $tipoPorCodigo[$codigo])
                ->whereDate('fecha_inicio', '<=', $this->fecha)->whereDate('fecha_fin', '>=', $this->fecha)
                ->first();
            // Ya tiene un fin después de hoy (se puso antes, o viene de Permisos y suspensiones): no se vuelve a preguntar
            if (!$s || !$s->fecha_fin || $s->fecha_fin->toDateString() > $this->fecha) {
                continue;
            }
            $preguntas[] = [
                'suspension_id' => $s->id,
                'plan_empleado_id' => $empleadoId,
                'nombre' => $f['nombres'] ?? '',
                'codigo' => $codigo,
                'tipo' => $s->tipoSuspension?->descripcion ?? $codigo,
                'desde' => $s->fecha_inicio->toDateString(),
                'hasta' => $s->fecha_fin->toDateString(),
                'hasta_actual' => $s->fecha_fin->toDateString(),
            ];
        }
        $this->preguntasHasta = $preguntas;
        $this->mostrarPreguntaHasta = (bool) $preguntas;
    }

    // ------------------------------------------------------------------ ¿regresó? (otro código sobre una suspensión)

    /**
     * Filas que tenían hoy el código de una suspensión que sigue después de hoy (V, DM, LSG…) y se guardan con otro
     * código. Al guardar, el día sale de la suspensión y esta queda partida en dos; luego se pregunta qué pasó.
     */
    private function suspensionesInterrumpidas(array $datos, array $antes): array
    {
        $tipoPorCodigo = \App\Models\PlanTipoAsistencia::whereNotNull('plan_tipo_suspension_id')
            ->whereNotIn('codigo', PlanillaRegistroDiarioReglas::SIN_PREGUNTA_HASTA)
            ->pluck('plan_tipo_suspension_id', 'codigo');
        $casos = [];
        foreach ($datos as $f) {
            $detalle = $f['plan_men_detalle_id'] ?? null;
            $anterior = trim((string) ($antes[$detalle] ?? ''));
            $nuevo = trim((string) ($f['asistencia'] ?? ''));
            if (!$detalle || !isset($tipoPorCodigo[$anterior]) || $nuevo === $anterior) {
                continue;
            }
            $empleadoId = (int) ($f['plan_empleado_id'] ?? \App\Models\PlanMensualDetalle::whereKey($detalle)->value('plan_empleado_id'));
            $s = \App\Models\PlanSuspension::with('tipoSuspension')
                ->where('plan_empleado_id', $empleadoId)->where('tipo_suspension_id', $tipoPorCodigo[$anterior])
                ->whereDate('fecha_inicio', '<=', $this->fecha)
                ->where(fn($q) => $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>', $this->fecha))
                ->first();
            if ($s) {
                $casos[] = [
                    'plan_empleado_id' => $empleadoId,
                    'plan_det_men_id' => (int) $detalle,
                    'tipo_suspension_id' => $s->tipo_suspension_id,
                    'nombre' => $f['nombres'] ?? '',
                    'codigo' => $anterior,
                    'nuevo' => $nuevo,
                    'tipo' => $s->tipoSuspension?->descripcion ?? $anterior,
                    'desde' => $s->fecha_inicio->toDateString(),
                    'hasta' => $s->fecha_fin?->toDateString(),
                ];
            }
        }
        return $casos;
    }

    /** Después de guardar: busca la parte de la suspensión que quedó después de hoy y arma la pregunta. */
    private function prepararPreguntaRegreso(array $casos): void
    {
        $preguntas = [];
        foreach ($casos as $c) {
            $resto = \App\Models\PlanSuspension::where('plan_empleado_id', $c['plan_empleado_id'])
                ->where('tipo_suspension_id', $c['tipo_suspension_id'])
                ->whereDate('fecha_inicio', '>', $this->fecha)
                ->whereDate('fecha_inicio', '<=', Carbon::parse($this->fecha)->addDays(2))
                ->when($c['hasta'], fn($q) => $q->whereDate('fecha_fin', $c['hasta']), fn($q) => $q->whereNull('fecha_fin'))
                ->first();
            $preguntas[] = $c + ['resto_id' => $resto?->id, 'decision' => 'regreso'];
        }
        $this->preguntasRegreso = $preguntas;
        $this->mostrarPreguntaRegreso = (bool) $preguntas;
        if ($preguntas) {
            $this->mostrarPreguntaHasta = false; // se muestra después de responder esta
        }
    }

    /**
     * Aplica lo elegido por trabajador:
     *  - regreso: la suspensión termina ayer y se quitan sus días siguientes del registro diario;
     *  - solo_hoy: queda partida (hoy con el código nuevo, sigue después);
     *  - error: hoy vuelve al código de la suspensión (se borran los tramos de hoy) y la suspensión se une otra vez.
     */
    public function confirmarRegreso(): void
    {
        try {
            $this->authorize(Permisos::PLANILLA_SUSPENSION_GESTIONAR);
            $diario = app(PlanillaSuspensionDiarioProceso::class);
            $quitados = $corregidos = 0;
            \Illuminate\Support\Facades\DB::transaction(function () use ($diario, &$quitados, &$corregidos) {
                foreach ($this->preguntasRegreso as $p) {
                    if ($p['decision'] === 'regreso' && $p['resto_id']) {
                        $resto = \App\Models\PlanSuspension::find($p['resto_id']);
                        if ($resto) {
                            $quitados += $diario->regresoAnticipado($resto->id, $resto->fecha_inicio->toDateString())['revertidos'];
                        }
                    } elseif ($p['decision'] === 'error') {
                        $this->volverAlCodigoDeSuspension($p);
                        $corregidos++;
                    }
                }
            });
            $servicio = app(PlanillaRegistroDiarioServicio::class);
            $servicio->actualizarResumenAsistencia($this->fecha);
            \App\Services\Costos\Consolidacion\BddManoObraServicio::registrarCambio($this->fecha);
            ActividadServicio::detectarYCrearActividades($this->fecha);

            $this->cerrarPreguntaRegreso();
            $this->obtenerResumenDiarioPlanilla();
            $this->recargarTabla();
            $this->cargarAusencias();
            $this->alert('success', 'Listo.' . ($quitados ? " Se quitaron {$quitados} día(s) siguientes del registro diario." : '')
                . ($corregidos ? " {$corregidos} trabajador(es) volvieron a su código de suspensión hoy." : ''));
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    /** "Dejar así" (o cerrar): queda como se guardó; si había "¿hasta cuándo?" pendiente, se muestra ahora. */
    public function cerrarPreguntaRegreso(): void
    {
        $this->mostrarPreguntaRegreso = false;
        $this->preguntasRegreso = [];
        $this->mostrarPreguntaHasta = (bool) $this->preguntasHasta;
    }

    private function volverAlCodigoDeSuspension(array $p): void
    {
        $registro = \App\Models\PlanRegistroDiario::where('plan_det_men_id', $p['plan_det_men_id'])->whereDate('fecha', $this->fecha)->first();
        $horas = \App\Models\PlanTipoAsistencia::where('codigo', $p['codigo'])->value('horas_jornal');
        if ($registro) {
            $registro->detalles()->delete();
            $registro->update(['asistencia' => $p['codigo'], 'total_horas' => is_numeric($horas) ? (float) $horas : 0]);
        } else {
            \App\Models\PlanRegistroDiario::create(['plan_det_men_id' => $p['plan_det_men_id'], 'fecha' => $this->fecha,
                'asistencia' => $p['codigo'], 'total_horas' => is_numeric($horas) ? (float) $horas : 0]);
        }
        // El código nuevo pudo ser otra suspensión (p. ej. DM de un día): se retira ese día de ella
        if ($p['nuevo'] !== '') {
            app(\App\Services\Planilla\SincronizarSuspensionDesdeAsistenciaServicio::class)
                ->sincronizar($p['plan_empleado_id'], $this->fecha, $p['nuevo'], null);
        }
        // La suspensión vuelve a sus fechas de antes: se une la parte de antes de hoy con la de después
        $resto = $p['resto_id'] ? \App\Models\PlanSuspension::find($p['resto_id']) : null;
        $primera = \App\Models\PlanSuspension::where('plan_empleado_id', $p['plan_empleado_id'])
            ->where('tipo_suspension_id', $p['tipo_suspension_id'])
            ->whereDate('fecha_inicio', $p['desde'])->whereDate('fecha_fin', '<', $this->fecha)->first();
        if ($primera) {
            $primera->update(['fecha_fin' => $p['hasta']]);
            $resto?->delete();
        } elseif ($resto) {
            $resto->update(['fecha_inicio' => $p['desde']]);
        } else {
            \App\Models\PlanSuspension::create(['plan_empleado_id' => $p['plan_empleado_id'], 'tipo_suspension_id' => $p['tipo_suspension_id'],
                'fecha_inicio' => $p['desde'], 'fecha_fin' => $p['hasta']]);
        }
        \App\Services\Reporte\AuditoriaServicio::registrar(\App\Models\PlanSuspension::class, $primera?->id ?? $resto?->id ?? 0, 'editar', null,
            ['fecha_inicio' => $p['desde'], 'fecha_fin' => $p['hasta']], "Registro diario {$this->fecha}: el {$p['nuevo']} fue un error, vuelve a {$p['codigo']}");
    }

    /** Guarda las fechas de fin elegidas: la suspensión se alarga (o acorta) y el registro diario se marca solo. */
    public function confirmarHasta(): void
    {
        try {
            $this->authorize(Permisos::PLANILLA_SUSPENSION_GESTIONAR);
            $crud = app(\App\Services\Planilla\Suspension\PlanillaSuspensionCrud::class);
            $marcados = 0;
            $conflictos = [];
            foreach ($this->preguntasHasta as $i => $p) {
                $hasta = $p['hasta'] ?: $p['hasta_actual'];
                if ($hasta < $this->fecha) {
                    throw \Illuminate\Validation\ValidationException::withMessages(["preguntasHasta.{$i}.hasta" => "{$p['nombre']}: el fin no puede ser antes del " . Carbon::parse($this->fecha)->format('d/m/Y') . '.']);
                }
                if ($hasta === $p['hasta_actual']) {
                    continue;
                }
                $s = \App\Models\PlanSuspension::findOrFail($p['suspension_id']);
                $r = $crud->guardarRangos($p['plan_empleado_id'], [[
                    'id' => $s->id, 'tipo_suspension_id' => $s->tipo_suspension_id,
                    'fecha_inicio' => $s->fecha_inicio->toDateString(), 'fecha_fin' => $hasta,
                    'observaciones' => $s->observaciones,
                ]], [$s->id]);
                $marcados += $r['registrados'];
                $conflictos = array_merge($conflictos, $r['conflictos']);
            }
            $this->mostrarPreguntaHasta = false;
            $this->preguntasHasta = [];
            $this->cargarAusencias();
            $texto = $marcados ? "Listo: {$marcados} día(s) siguientes quedaron marcados en el registro diario." : 'Listo.';
            $conflictos
                ? $this->alert('warning', $texto . ' Días que ya tenían otra asistencia (no se cambiaron): ' . implode('; ', array_slice($conflictos, 0, 5)), ['timer' => 12000])
                : $this->alert('success', $texto);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    /** Se guardó el modal de suspensiones (menú contextual): la tabla y el aviso se vuelven a leer. */
    #[\Livewire\Attributes\On('suspensionesGuardadas')]
    public function suspensionesGuardadas(): void
    {
        $this->obtenerResumenDiarioPlanilla();
        $this->recargarTabla();
        $this->cargarAusencias();
    }

    public function render()
    {
        return view('livewire.planilla.registro-diario.gestion-planilla-registro-diario-detalle', [
            'empleados' => $this->empleadosIniciales,
        ]);
    }
}
