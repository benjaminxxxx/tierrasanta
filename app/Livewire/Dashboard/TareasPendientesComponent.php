<?php

namespace App\Livewire\Dashboard;

use App\Models\TareaPendiente;
use App\Services\Riego\VerificacionSincronizacionRiegoServicio;
use App\Traits\HandlesAlerts;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class TareasPendientesComponent extends Component
{
    use HandlesAlerts,LivewireAlert;
    public bool $mostrarFormularioTareasPendientes = false;
    public function detectarTareasPendientes(){
        try {
            app(VerificacionSincronizacionRiegoServicio::class)->detectarTareasPendientes();
            //aqui ir agregando mas tareas pendientes
            $this->alert('success','Tareas ejecutadas');
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }
    public function ejecutar(int $tareaId, int $indiceAccion = 0)
    {
        $tarea = TareaPendiente::findOrFail($tareaId);
        $accion = $tarea->acciones[$indiceAccion] ?? null;

        if (!$accion) {
            return;
        }

        app($tarea->servicio)->{$accion['metodo']}(...($accion['parametros'] ?? []));

        $tarea->update([
            'ejecutado_por' => auth()->id(),
            'ejecutado_en' => now(),
        ]);

        if ($tarea->metodo_detectar) {
            app($tarea->servicio)->{$tarea->metodo_detectar}(); // refresca estado inmediatamente
        }

        $this->alert('success', 'Acción ejecutada.');
    }

    public function render()
    {
        $tareas = TareaPendiente::where('estado', 'pendiente')
            ->whereNull('parent_id')
            ->with(['subtareas' => fn($q) => $q->where('estado', 'pendiente')])
            ->orderByDesc('cantidad_afectados')
            ->get();

        return view('livewire.dashboard.tareas-pendientes-component', compact('tareas'));
    }
}