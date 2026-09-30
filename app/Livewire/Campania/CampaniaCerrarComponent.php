<?php

namespace App\Livewire\Campania;

use App\Models\CampoCampania;
use App\Services\Campania\Cosecha\CampaniaCosechaConsulta;
use App\Services\Campania\Registro\CampaniaRegistroImpactoConsulta;
use App\Services\Campania\Registro\CampaniaRegistroProceso;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

/**
 * Modal "Cerrar campaña". Se abre con el evento `cerrarCampania` (campaniaId, fecha opcional).
 * Lo usan el resumen, la ficha de campaña (paso "campo" del registro) y las tareas pendientes
 * (enlace a /campania/resumen?cerrar={id}). Al cerrar emite `campaniaCerrada` y `campaniaInsertada`.
 */
class CampaniaCerrarComponent extends Component
{
    use LivewireAlert;

    public bool $mostrar = false;
    public ?int $campaniaId = null;
    public ?string $fechaCierre = null;

    /** Datos de la campaña y de su cosecha para mostrar. */
    public array $resumen = [];

    /** Avisos del cambio (costos que se mueven, registros reasignados…). */
    public array $avisos = [];

    protected $listeners = ['cerrarCampania'];

    public function cerrarCampania(int $campaniaId, ?string $fecha = null): void
    {
        $this->resetErrorBag();
        $campania = CampoCampania::find($campaniaId);
        if (!$campania) {
            $this->alert('error', 'La campaña ya no existe.');
            return;
        }
        if ($campania->fecha_fin) {
            $this->alert('info', "{$campania->nombre_campania} ya está cerrada desde el " . formatear_fecha($campania->fecha_fin) . '.');
            return;
        }

        $estado = app(CampaniaCosechaConsulta::class)->estado($campania);
        $this->campaniaId = $campania->id;
        $this->fechaCierre = $fecha ?? $estado['fecha_cierre_sugerida']?->toDateString();
        $this->resumen = [
            'nombre' => $campania->nombre_campania,
            'campo' => $campania->campo,
            'inicio' => formatear_fecha($campania->fecha_inicio),
            'cosecha' => $estado['ultima_cosecha']
                ? formatear_fecha($estado['primera_cosecha']) . ($estado['dias_cosecha'] > 1 ? ' – ' . formatear_fecha($estado['ultima_cosecha']) : '')
                    . " ({$estado['dias_cosecha']} día(s)" . ($estado['es_mama'] ? ', para mamá' : '') . ')'
                : null,
            'ultima_cosecha' => $estado['ultima_cosecha']?->toDateString(),
            'sugerida' => $estado['fecha_cierre_sugerida']?->toDateString(),
            'posterior' => $estado['actividad_posterior']
                ? $estado['actividad_posterior']['labor'] . ' el ' . formatear_fecha($estado['actividad_posterior']['fecha'])
                : null,
        ];
        $this->calcularAvisos();
        $this->mostrar = true;
    }

    public function updatedFechaCierre(): void
    {
        $this->resetErrorBag();
        $this->calcularAvisos();
    }

    public function confirmar(): void
    {
        $this->validate(['fechaCierre' => 'required|date'], ['fechaCierre.required' => 'Indica la fecha de cierre.']);

        try {
            $campania = app(CampaniaRegistroProceso::class)->cerrar($this->campaniaId, $this->fechaCierre);
            $this->alert('success', "Campaña {$campania->nombre_campania} cerrada el " . formatear_fecha($this->fechaCierre) . '.');
            $this->mostrar = false;
            $this->dispatch('campaniaCerrada', campaniaId: $campania->id);
            $this->dispatch('campaniaInsertada', $campania->toArray());
        } catch (ValidationException $e) {
            // Los errores del proceso vienen con claves del formulario de campaña
            foreach ($e->errors() as $mensajes) {
                $this->addError('fechaCierre', implode(' ', $mensajes));
            }
        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    private function calcularAvisos(): void
    {
        $this->avisos = [];
        if (!$this->campaniaId || !$this->fechaCierre) {
            return;
        }
        $campania = CampoCampania::find($this->campaniaId);
        if ($this->fechaCierre < $campania->fecha_inicio->toDateString()) {
            $this->addError('fechaCierre', 'La fecha de cierre no puede ser anterior al inicio (' . formatear_fecha($campania->fecha_inicio) . ').');
            return;
        }
        if (!empty($this->resumen['ultima_cosecha']) && $this->fechaCierre < $this->resumen['ultima_cosecha']) {
            $this->avisos[] = 'La fecha es anterior al último día de cosecha (' . formatear_fecha($this->resumen['ultima_cosecha'])
                . '): esos días de cosecha quedarán fuera de la campaña.';
        }
        $this->avisos = array_merge($this->avisos,
            app(CampaniaRegistroImpactoConsulta::class)->analizar($campania, $campania->fecha_inicio->toDateString(), $this->fechaCierre)['avisos']);
    }

    public function render()
    {
        return view('livewire.campania.campania-cerrar-component');
    }
}
