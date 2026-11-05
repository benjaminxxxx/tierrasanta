<?php

namespace App\Livewire\Campania;

use App\Models\CampoCampania;
use App\Services\Campania\Registro\CampaniaRegistroProceso;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

/**
 * Modal "Corregir fechas de la campaña" (evento `corregirFechasCampania`: campaniaId). Corrige el inicio y/o el
 * cierre o reabre la campaña, con motivo. Si la campaña vecina estaba pegada, se mueve con ella (opcional).
 * Antes de guardar muestra qué campañas cambian y a dónde pasan los registros. Emite `campaniaInsertada`.
 */
class CampaniaCorregirFechasComponent extends Component
{
    use LivewireAlert, \App\Traits\HandlesAlerts;

    public bool $mostrar = false;
    public ?int $campaniaId = null;
    public ?string $inicio = null;
    public ?string $fin = null;
    public bool $reabrir = false;
    public bool $moverVecinas = true;
    public string $motivo = '';
    public array $actual = [];

    /** Vista previa: [{nombre, antes, despues}], avisos y vecinas. */
    public array $cambios = [];
    public array $avisos = [];
    public array $vecinas = [];
    public bool $sinCambio = true;

    protected $listeners = ['corregirFechasCampania'];

    public function corregirFechasCampania(int $campaniaId): void
    {
        $campania = CampoCampania::find($campaniaId);
        if (!$campania) {
            $this->errorAlert('La campaña ya no existe.');
            return;
        }
        $this->resetErrorBag();
        $this->reset(['motivo', 'reabrir', 'cambios', 'avisos', 'vecinas']);
        $this->moverVecinas = true;
        $this->campaniaId = $campania->id;
        $this->inicio = $campania->fecha_inicio->toDateString();
        $this->fin = $campania->fecha_fin?->toDateString();
        $this->actual = ['nombre' => $campania->nombre_campania, 'campo' => $campania->campo,
            'inicio' => $this->inicio, 'fin' => $this->fin];
        $this->previsualizar();
        $this->mostrar = true;
    }

    public function updated($propiedad): void
    {
        if (in_array($propiedad, ['inicio', 'fin', 'reabrir', 'moverVecinas'], true)) {
            $this->previsualizar();
        }
    }

    private function finElegido(): ?string
    {
        return $this->reabrir ? null : ($this->fin ?: null);
    }

    private function previsualizar(): void
    {
        $this->resetErrorBag();
        $this->cambios = $this->avisos = $this->vecinas = [];
        $this->sinCambio = true;
        if (!$this->campaniaId || !$this->inicio) {
            return;
        }
        try {
            $plan = app(CampaniaRegistroProceso::class)->planCorreccionFechas($this->campaniaId, $this->inicio, $this->finElegido(), $this->moverVecinas);
            $this->sinCambio = $plan['sin_cambio'];
            $this->avisos = $plan['avisos'];
            $this->vecinas = $plan['vecinas'];
            foreach ($plan['cambios'] as $c) {
                $antes = [$c['campania']->fecha_inicio->toDateString(), $c['campania']->fecha_fin?->toDateString()];
                if ($antes !== [$c['inicio'], $c['fin']]) {
                    $this->cambios[] = ['nombre' => $c['campania']->nombre_campania, 'antes' => $antes, 'despues' => [$c['inicio'], $c['fin']],
                        'principal' => $c['campania']->id === $this->campaniaId];
                }
            }
        } catch (ValidationException $e) {
            foreach ($e->errors() as $mensajes) {
                $this->addError('fechas', implode(' ', $mensajes));
            }
        } catch (\Throwable $e) {
            $this->addError('fechas', $e->getMessage());
        }
    }

    public function guardar(): void
    {
        abort_unless(auth()->user()?->canAny([\App\Constants\Permisos::CAMPAÑA_GESTIONAR, \App\Constants\Permisos::CAMPAÑA_POR_CAMPO_GESTIONAR]), 403,
            'No tienes permiso para corregir fechas de campañas.');
        try {
            app(CampaniaRegistroProceso::class)->corregirFechas($this->campaniaId, $this->inicio, $this->finElegido(), $this->motivo, $this->moverVecinas);
            $campania = CampoCampania::find($this->campaniaId);
            $this->alert('success', "Fechas de {$campania->nombre_campania} corregidas. Los costos de los días que cambiaron se regeneran.");
            $this->mostrar = false;
            $this->dispatch('campaniaInsertada', $campania->toArray());
            $this->dispatch('campaniaCerrada', campaniaId: $campania->id);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $campo => $mensajes) {
                $this->addError($campo === 'motivo' ? 'motivo' : 'fechas', implode(' ', $mensajes));
            }
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function render()
    {
        return view('livewire.campania.campania-corregir-fechas-component');
    }
}
