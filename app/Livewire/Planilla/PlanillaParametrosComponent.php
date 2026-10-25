<?php

namespace App\Livewire\Planilla;

use App\Services\Planilla\Parametro\PlanillaParametroConsulta;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Parámetros de planilla: una tarjeta por cada valor que necesita el sistema (RMV, EsSalud, gratificaciones…) con
 * su valor vigente este mes. Al hacer clic se abre su historial (PlanillaParametroModalComponent).
 */
class PlanillaParametrosComponent extends Component
{
    public string $buscar = '';

    #[On('parametroGuardado')]
    public function refrescar(): void
    {
        PlanillaParametroConsulta::olvidar();
    }

    public function render()
    {
        $tarjetas = collect(PlanillaParametroConsulta::tarjetas());
        $termino = mb_strtolower(trim($this->buscar));
        if ($termino !== '') {
            $tarjetas = $tarjetas->filter(fn($t) => str_contains(mb_strtolower($t['nombre'] . ' ' . $t['codigo']), $termino));
        }
        return view('livewire.planilla.planilla-parametros-component', [
            'grupos' => [
                'Planilla agraria y oficina' => $tarjetas->filter(fn($t) => $t['en_catalogo'] && count($t['planillas']) > 1),
                'Solo planilla agraria' => $tarjetas->filter(fn($t) => $t['en_catalogo'] && $t['planillas'] === ['agraria']),
                'Solo planilla oficina' => $tarjetas->filter(fn($t) => $t['en_catalogo'] && $t['planillas'] === ['oficina']),
            ],
            'otros' => $tarjetas->reject(fn($t) => $t['en_catalogo']),
            'afp' => PlanillaParametroConsulta::tarjetaAfp(),
            'mesActual' => ucfirst(now()->translatedFormat('F Y')),
        ]);
    }
}
