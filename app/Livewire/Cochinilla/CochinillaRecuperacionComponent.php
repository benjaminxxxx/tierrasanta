<?php

namespace App\Livewire\Cochinilla;

use App\Constants\Permisos;
use App\Services\Cochinilla\Recuperacion\CochinillaRecuperacionConsulta;
use App\Services\Cochinilla\Recuperacion\CochinillaRecuperacionProceso;
use App\Services\Cochinilla\Recuperacion\CochinillaRecuperacionReglas;
use App\Traits\HandlesAlerts;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Cochinilla → Por recuperar: cada infestación de un campo (con la mamá de 1 a 3 campos), cuánto volvió ya como
 * infestadores, cuántos días van y si se pasó del plazo de aviso. Desde aquí se reparte cada retorno entre los campos
 * de origen y se da por recuperada la infestación.
 */
#[Title('Cochinilla por recuperar')]
class CochinillaRecuperacionComponent extends Component
{
    use LivewireAlert, HandlesAlerts;

    #[Url(except: 'por_recuperar')]
    public string $estado = 'por_recuperar';
    #[Url(except: '')]
    public string $campo = '';

    public bool $mostrarCerrar = false;
    public ?string $cerrarClave = null;
    public string $nota = '';

    protected $listeners = ['repartoGuardado' => '$refresh'];

    public function pedirCerrar(string $clave): void
    {
        $this->cerrarClave = $clave;
        $this->nota = '';
        $this->mostrarCerrar = true;
    }

    public function cerrar(): void
    {
        try {
            abort_unless(auth()->user()->can(Permisos::COCHINILLA_RECUPERACION_GESTIONAR), 403);
            [$campo, $inicio] = explode('|', (string) $this->cerrarClave);
            app(CochinillaRecuperacionProceso::class)->cerrar($campo, $inicio, trim($this->nota) ?: null);
            $this->mostrarCerrar = false;
            $this->alert('success', "Infestación de {$campo} dada por recuperada.");
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function reabrir(string $clave): void
    {
        try {
            abort_unless(auth()->user()->can(Permisos::COCHINILLA_RECUPERACION_GESTIONAR), 403);
            [$campo, $inicio] = explode('|', $clave);
            app(CochinillaRecuperacionProceso::class)->reabrir($campo, $inicio);
            $this->alert('success', 'Se volvió a abrir: vuelve a figurar como por recuperar.');
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function render()
    {
        $consulta = app(CochinillaRecuperacionConsulta::class);
        $tandas = $consulta->tandas($this->estado, trim($this->campo) ?: null);
        $todas = $this->estado === 'todas' && !$this->campo ? $tandas : $consulta->tandas('todas');

        return view('livewire.cochinilla.cochinilla-recuperacion-component', [
            'tandas' => $tandas,
            'contadores' => [
                'por_recuperar' => $todas->where('cerrada', null)->count(),
                'alerta' => $todas->where('alerta', true)->count(),
                'recuperadas' => $todas->filter(fn($t) => $t['cerrada'])->count(),
            ],
            'diasAlerta' => CochinillaRecuperacionReglas::diasAlerta(),
            'puedeGestionar' => auth()->user()->can(Permisos::COCHINILLA_RECUPERACION_GESTIONAR),
        ]);
    }
}
