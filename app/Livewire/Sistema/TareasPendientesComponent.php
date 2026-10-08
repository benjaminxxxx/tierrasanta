<?php

namespace App\Livewire\Sistema;

use App\Models\TareaPendiente;
use App\Services\Cuadrilla\VerificacionHorasCuadrillaServicio;
use App\Services\Almacen\Kardex\VerificacionKardexServicio;
use App\Services\Riego\VerificacionSincronizacionRiegoServicio;
use App\Traits\HandlesAlerts;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class TareasPendientesComponent extends Component
{
    use HandlesAlerts,LivewireAlert;
    public bool $mostrarFormularioTareasPendientes = false;

    // Hoy se elige un mes; internamente todo trabaja con un rango de fechas (ver rango()),
    // así más adelante se puede filtrar por día cambiando solo cómo se arma el rango.
    public string $mes = '';

    /** Detectores de tareas pendientes. Cada uno recibe el rango (fechaInicio, fechaFin). */
    private const DETECTORES = [
        VerificacionSincronizacionRiegoServicio::class,
        VerificacionKardexServicio::class,
        VerificacionHorasCuadrillaServicio::class,
        \App\Services\Planilla\Empleado\VerificacionContratosServicio::class,
        \App\Services\Almacen\VerificacionCombustibleServicio::class,
        \App\Services\Campania\Cosecha\CampaniaCosechaDetector::class,
        \App\Services\Campania\Cobertura\CampaniaCoberturaDetector::class,
        \App\Services\Almacen\Kardex\AlmacenKardexCostoCeroDetector::class,
        \App\Services\Campo\Labor\CampoLaborManoObraDetector::class,
        \App\Services\Caja\Cierre\CajaCierreDetector::class,
        \App\Services\Campania\Etapa\CampaniaEtapaDetector::class,
        \App\Services\Campania\Etapa\CampaniaEvaluacionInfestacionDetector::class,
        //aqui ir agregando mas tareas pendientes
    ];

    /** Clave de sesión con el mes que se está revisando (se conserva al cerrar y reabrir el panel). */
    private const SESION_MES = 'tareas_pendientes.mes';

    public function mount(): void
    {
        $mes = session(self::SESION_MES);
        $this->mes = is_string($mes) && preg_match('/^\d{4}-\d{2}$/', $mes) ? $mes : now()->format('Y-m');
    }

    public function updatedMes(): void
    {
        if (preg_match('/^\d{4}-\d{2}$/', $this->mes)) {
            session([self::SESION_MES => $this->mes]);
        }
    }

    /** @return array{0: string, 1: string} [fechaInicio, fechaFin] */
    private function rango(): array
    {
        // Con el día 01 explícito: createFromFormat('Y-m', ...) completa con el día de hoy y,
        // un 29/30/31, febrero (u otro mes corto) se desbordaba al mes siguiente.
        $inicio = $this->mes
            ? Carbon::createFromFormat('Y-m-d', $this->mes . '-01')->startOfDay()
            : now()->startOfMonth();
        return [$inicio->toDateString(), $inicio->copy()->endOfMonth()->toDateString()];
    }

    public function detectarTareasPendientes(){
        try {
            [$inicio, $fin] = $this->rango();
            foreach (self::DETECTORES as $detector) {
                app($detector)->detectarTareasPendientes($inicio, $fin);
            }
            $this->alert('success','Tareas ejecutadas');
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }
    public function ejecutar(int $tareaId, int $indiceAccion = 0)
    {
        $tarea = TareaPendiente::findOrFail($tareaId);
        $accion = $tarea->acciones[$indiceAccion] ?? null;

        // Las acciones de tipo "link" se abren en el navegador; aquí solo se ejecutan métodos
        if (!$accion || empty($accion['metodo'])) {
            return;
        }

        try {
            app($tarea->servicio)->{$accion['metodo']}(...($accion['parametros'] ?? []));

            $tarea->update([
                'ejecutado_por' => auth()->id(),
                'ejecutado_en' => now(),
            ]);
            $this->alert('success', 'Acción ejecutada.');
        } catch (\Throwable $th) {
            // Una acción puede fallar a medias (p. ej. regenerar varios kardex): se informa y se refresca igual
            $this->errorAlert($th);
        }

        if ($tarea->metodo_detectar) {
            // refresca estado inmediatamente, con el periodo elegido en el panel
            app($tarea->servicio)->{$tarea->metodo_detectar}(...$this->rango());
        }
    }

    public function render()
    {
        [$inicio, $fin] = $this->rango();

        $tareas = TareaPendiente::where('estado', 'pendiente')
            ->whereNull('parent_id')
            ->enPeriodo($inicio, $fin)
            ->with(['subtareas' => fn($q) => $q->where('estado', 'pendiente')->enPeriodo($inicio, $fin)->orderBy('fecha_inicio')])
            ->withCount(['subtareas as subtareas_pendientes_count' => fn($q) => $q->where('estado', 'pendiente')])
            ->get()
            // Una tarea general (sin periodo) que se divide en subtareas por periodo solo se muestra
            // si tiene alguna en el periodo elegido (ej. riego: solo en los meses con desincronizados).
            ->filter(fn($t) => $t->subtareas->isNotEmpty() || $t->subtareas_pendientes_count === 0)
            ->each(function ($t) {
                // Conteo del periodo elegido (el total general se muestra aparte)
                $t->cantidad_periodo = $t->subtareas->isNotEmpty() && !$t->fecha_inicio
                    ? $t->subtareas->sum('cantidad_afectados')
                    : $t->cantidad_afectados;
            })
            ->sortByDesc('cantidad_periodo')
            ->values();

        return view('livewire.sistema.tareas-pendientes-component', compact('tareas'));
    }
}
