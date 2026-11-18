<?php

namespace App\Livewire\Campania;

use App\Constants\Permisos;
use App\Models\CampoCampania;
use App\Services\Campania\Registro\CampaniaRegistroProceso;
use App\Services\Campania\Tarjeta\CampaniaTarjetaConsulta;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Tarjeta de campaña (DESIGN.md): vista rápida y gráfica de una campaña desde cualquier lista (clic en su nombre,
 * <x-campania.nombre>): datos, avance, cosecha, siembras con su etapa y línea de tiempo, y accesos a los modales de
 * siempre (historia, mapa de etapas, editar, corregir fechas, cerrar, eliminar). Los modales que abre deben estar en la
 * página (las mismas que usan x-campania.opciones).
 */
class CampaniaTarjetaComponent extends Component
{
    use LivewireAlert, \App\Traits\HandlesAlerts;

    public bool $mostrar = false;
    public ?int $campaniaId = null;

    /** Formulario "+ Siembra (relleno)" dentro de la tarjeta */
    public bool $agregandoSiembra = false;
    public ?string $fechaNuevaSiembra = null;
    public ?string $motivoNuevaSiembra = null;
    /** Ventana "Detalle de siembras" (de dónde salió cada una) */
    public bool $verSiembras = false;

    #[On('verTarjetaCampania')]
    public function verTarjetaCampania(int $campaniaId): void
    {
        $this->campaniaId = $campaniaId;
        $this->agregandoSiembra = false;
        $this->fechaNuevaSiembra = null;
        $this->motivoNuevaSiembra = null;
        $this->verSiembras = false;
        $this->mostrar = true;
    }

    /** Parte del campo se sembró en otra fecha (relleno): se agrega la siembra desde aquí, sin entrar al mapa. */
    public function agregarSiembra(): void
    {
        try {
            abort_unless($this->puedeGestionar(), 403);
            app(\App\Services\Campania\Sector\CampaniaSectorProceso::class)
                ->agregarSiembra(CampoCampania::findOrFail($this->campaniaId), (string) $this->fechaNuevaSiembra, $this->motivoNuevaSiembra);
            $this->agregandoSiembra = false;
            $this->fechaNuevaSiembra = null;
            $this->motivoNuevaSiembra = null;
            $this->alert('success', 'Siembra agregada. Sus hileras se repartieron parejo: corrígelas en "Editar siembras y etapas" si hace falta.');
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    /** Posible relleno confirmado: era otra siembra (se agrega con su fecha y las labores como motivo). */
    public function confirmarRelleno(string $desde): void
    {
        try {
            abort_unless($this->puedeGestionar(), 403);
            $c = CampoCampania::findOrFail($this->campaniaId);
            $tanda = collect(app(\App\Services\Campania\Sector\CampaniaSectorConsulta::class)->posiblesRellenos($c))->firstWhere('desde', $desde);
            $labores = $tanda ? collect($tanda['labores'])->map(fn($j, $l) => "{$l} ({$j})")->take(3)->implode(', ') : '';
            app(\App\Services\Campania\Sector\CampaniaSectorProceso::class)->agregarSiembra($c, $desde,
                'confirmado en la tarjeta' . ($tanda ? " por labores de siembra: {$labores}, {$tanda['jornales']} jornales" : ''));
            $this->alert('success', 'Relleno agregado como otra siembra. Sus hileras se repartieron parejo: corrígelas en "Editar siembras y etapas" si hace falta.');
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    /** Posible relleno descartado: fue replante de la misma siembra; no se vuelve a proponer. */
    public function descartarRelleno(string $desde): void
    {
        try {
            abort_unless($this->puedeGestionar(), 403);
            $c = CampoCampania::findOrFail($this->campaniaId);
            $antes = (array) ($c->rellenos_descartados ?? []);
            $c->forceFill(['rellenos_descartados' => array_values(array_unique(array_merge($antes, [$desde])))])->save();
            \App\Services\Reporte\AuditoriaServicio::registrar(CampoCampania::class, $c->id, 'editar', ['rellenos_descartados' => $antes],
                ['rellenos_descartados' => $c->rellenos_descartados], "Labores de siembra del {$desde} en {$c->nombre_campania}: replante, no es otra siembra");
            $this->alert('success', 'Marcado como replante: no se volverá a proponer.');
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    public function eliminarCampania(): void
    {
        try {
            abort_unless($this->puedeGestionar(), 403);
            // Auditado: queda en `auditorias` quién eliminó y la campaña completa
            app(CampaniaRegistroProceso::class)->eliminar((int) $this->campaniaId);
            $this->mostrar = false;
            // La lista de la página ya no la tiene: se recarga con el aviso
            $this->flash('success', 'Campaña eliminada.', [], url()->previous());
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    private function puedeGestionar(): bool
    {
        return (bool) auth()->user()?->canAny([Permisos::CAMPAÑA_GESTIONAR, Permisos::CAMPAÑA_POR_CAMPO_GESTIONAR]);
    }

    public function render()
    {
        $campania = $this->mostrar && $this->campaniaId ? CampoCampania::find($this->campaniaId) : null;
        return view('livewire.campania.campania-tarjeta-component', [
            'campania' => $campania,
            't' => $campania ? app(CampaniaTarjetaConsulta::class)->tarjeta($campania) : null,
            'puedeGestionar' => $this->puedeGestionar(),
        ]);
    }
}
