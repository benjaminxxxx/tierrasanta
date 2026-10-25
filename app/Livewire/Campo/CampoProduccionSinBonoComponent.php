<?php

namespace App\Livewire\Campo;

use App\Constants\Permisos;
use App\Exceptions\ErrorControlado;
use App\Models\Actividad;
use App\Models\Cuadrillero;
use App\Models\PlanEmpleado;
use App\Services\Campo\Produccion\CampoProduccionConsulta;
use App\Services\Campo\Produccion\CampoProduccionProceso;
use App\Services\Campo\Produccion\CampoProduccionReglas;
use Illuminate\Support\Facades\Gate;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use Livewire\Component;

/**
 * Cantidades SIN bono de una actividad (fecha + campo + labor): filas con trabajador opcional (cuadrillero o empleado),
 * cantidad por recojo y observación. Se usa al final de bonificaciones y en Producción de campo.
 * Guarda con CampoProduccionProceso (reemplaza las filas sin bono de la actividad).
 */
class CampoProduccionSinBonoComponent extends Component
{
    use LivewireAlert, \App\Traits\HandlesAlerts;

    #[Locked]
    public int $actividadId;

    /** @var array<int, array{trabajador: ?string, nombre: ?string, recojos: array<int, mixed>, observacion: ?string}> */
    public array $filas = [];
    public int $recojos = 1;

    public function mount(int $actividadId): void
    {
        $this->actividadId = $actividadId;
        $this->cargar();
    }

    private function cargar(): void
    {
        $actividad = Actividad::findOrFail($this->actividadId);
        $filas = CampoProduccionConsulta::sinBono($this->actividadId);
        // Tantos recojos como la actividad o como el mayor recojo ya registrado
        $mayor = 1;
        foreach ($filas as $f) {
            $mayor = max($mayor, ...array_keys($f['recojos']));
        }
        $this->recojos = max((int) $actividad->recojos, $mayor);
        $this->filas = array_map(fn($f) => [
            'trabajador' => $f['tipo'] ? ($f['tipo'] === 'cuadrilla' ? 'c:' : 'p:') . $f['trabajador_id'] : null,
            'nombre' => $f['nombre'], 'recojos' => $f['recojos'], 'observacion' => $f['observacion'],
        ], $filas);
    }

    public function agregarFila(): void
    {
        $this->filas[] = ['trabajador' => null, 'nombre' => null, 'recojos' => [], 'observacion' => 'Sin bono'];
    }

    public function quitarFila(int $i): void
    {
        unset($this->filas[$i]);
        $this->filas = array_values($this->filas);
    }

    public function agregarRecojo(): void
    {
        $this->recojos++;
    }

    /** Buscador de trabajadores (cuadrilleros y empleados de planilla). */
    #[Renderless]
    public function buscarTrabajadores($texto): array
    {
        $texto = trim((string) $texto);
        $cuadrilla = Cuadrillero::query()
            ->when($texto !== '', fn($q) => $q->where(fn($q) => $q->where('nombres', 'like', "%{$texto}%")->orWhere('dni', 'like', "%{$texto}%")))
            ->orderBy('nombres')->limit(10)->get(['id', 'nombres'])
            ->map(fn($c) => ['id' => "c:{$c->id}", 'name' => "{$c->nombres} · cuadrilla"]);
        $planilla = PlanEmpleado::buscar($texto)->alfabetico()->limit(10)->get()
            ->map(fn($e) => ['id' => "p:{$e->id}", 'name' => "{$e->nombre_completo} · planilla"]);
        return $cuadrilla->concat($planilla)->values()->all();
    }

    public function guardar(CampoProduccionProceso $proceso): void
    {
        try {
            if (!Gate::any([Permisos::CAMPO_PRODUCCION_REGISTRAR, Permisos::CUADRILLA_BONIFICACION_ACTUALIZAR])) {
                throw new ErrorControlado('No tienes permiso para registrar producción (permiso: ' . Permisos::CAMPO_PRODUCCION_REGISTRAR . ').');
            }
            $filas = array_map(function ($f) {
                [$tipo, $id] = str_contains((string) $f['trabajador'], ':') ? explode(':', $f['trabajador']) : [null, null];
                return ['tipo' => ['c' => 'cuadrilla', 'p' => 'planilla'][$tipo] ?? null, 'trabajador_id' => $id,
                    'recojos' => $f['recojos'] ?? [], 'observacion' => $f['observacion'] ?? null];
            }, $this->filas);
            $n = $proceso->guardarSinBono(Actividad::findOrFail($this->actividadId), $filas);
            $this->cargar();
            $this->alert('success', $n ? "Producción sin bono guardada ({$n} registro(s))." : 'Se quitaron las cantidades sin bono.');
            $this->dispatch('produccionActualizada');
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function render()
    {
        $actividad = Actividad::find($this->actividadId);
        return view('livewire.campo.campo-produccion-sin-bono-component', [
            'unidad' => $actividad ? CampoProduccionReglas::unidadDeLabor($actividad->codigo_labor, $actividad->fecha) : null,
            'puede' => Gate::any([Permisos::CAMPO_PRODUCCION_REGISTRAR, Permisos::CUADRILLA_BONIFICACION_ACTUALIZAR]),
        ]);
    }
}
