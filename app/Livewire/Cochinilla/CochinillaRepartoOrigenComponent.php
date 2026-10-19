<?php

namespace App\Livewire\Cochinilla;

use App\Constants\Permisos;
use App\Models\CochinillaIngreso;
use App\Services\Cochinilla\Recuperacion\CochinillaRecuperacionProceso;
use App\Traits\HandlesAlerts;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Modal "¿De qué campos es esta cochinilla?": reparte los kg de un retorno de infestadores entre los campos que
 * pusieron la mamá. Sugiere por % de kg de mamá; quien registra lo corrige (se perdió peso, se sabe qué camas eran…).
 * Se abre con el evento repartirIngreso (desde el ingreso de cochinilla o desde "Cochinilla por recuperar").
 */
class CochinillaRepartoOrigenComponent extends Component
{
    use LivewireAlert, HandlesAlerts;

    public bool $mostrar = false;
    public ?int $ingresoId = null;
    /** @var array<string, mixed> campo de origen => kg */
    public array $kg = [];
    public string $motivo = '';

    #[On('repartirIngreso')]
    public function abrir(int $ingresoId): void
    {
        $ingreso = CochinillaIngreso::findOrFail($ingresoId);
        $opciones = app(CochinillaRecuperacionProceso::class)->opciones($ingreso);
        $this->ingresoId = $ingresoId;
        $this->motivo = '';
        // Si ya tiene reparto se muestra tal cual (0 en los demás campos); si no, el sugerido por % de mamá
        $tieneReparto = collect($opciones['origenes'])->contains(fn($o) => $o['kg'] !== null);
        $this->kg = array_map(fn($o) => $tieneReparto ? ($o['kg'] ?? 0) : $o['kg_sugerido'], $opciones['origenes']);
        $this->resetErrorBag();
        $this->mostrar = true;
    }

    /** Vuelve a poner el reparto sugerido por % de mamá. */
    public function usarSugerido(): void
    {
        $opciones = app(CochinillaRecuperacionProceso::class)->opciones(CochinillaIngreso::findOrFail($this->ingresoId));
        $this->kg = array_map(fn($o) => $o['kg_sugerido'], $opciones['origenes']);
    }

    public function guardar(): void
    {
        try {
            abort_unless(auth()->user()->can(Permisos::COCHINILLA_INGRESO) || auth()->user()->can(Permisos::COCHINILLA_RECUPERACION_GESTIONAR), 403);
            app(CochinillaRecuperacionProceso::class)->guardarReparto(CochinillaIngreso::findOrFail($this->ingresoId), $this->kg, true, $this->motivo);
            $this->mostrar = false;
            $this->alert('success', 'Reparto guardado: cada campaña de origen recibe sus kilos.');
            $this->dispatch('repartoGuardado');
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function render()
    {
        $ingreso = $this->mostrar && $this->ingresoId ? CochinillaIngreso::with('origenes')->find($this->ingresoId) : null;
        return view('livewire.cochinilla.cochinilla-reparto-origen-component', [
            'ingreso' => $ingreso,
            'opciones' => $ingreso ? app(CochinillaRecuperacionProceso::class)->opciones($ingreso) : null,
        ]);
    }
}
