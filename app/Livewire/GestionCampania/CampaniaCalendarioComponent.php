<?php

namespace App\Livewire\GestionCampania;

use App\Models\Campo;
use App\Models\CampoCampania;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Session;

class CampaniaCalendarioComponent extends Component
{
    use LivewireAlert;
    public $timeline = [];
    public function mount()
    {
        $campos = Campo::query()
            ->select('nombre')
            ->with([
                'campanias' => fn($q) => $q
                    ->select('nombre_campania', 'fecha_inicio', 'fecha_fin', 'campo','id')
                    ->orderByDesc('fecha_inicio')
            ])
            ->orderBy('nombre')
            ->get()
            ->map(fn($campo) => [
                'nombre' => $campo->nombre,
                'campanias' => $campo->campanias->map(function ($c) {
                    $inicio = $c->fecha_inicio;
                    $fin = $c->fecha_fin ?? now();

                    return [
                        'id'=>$c->id,
                        'nombre' => $c->nombre_campania,
                        'inicio' => $inicio->toDateString(),
                        'fin' => $c->fecha_fin?->toDateString(),
                        'duracion_humana' => $inicio
                            ->diffAsCarbonInterval($fin)
                            ->locale('es')
                            ->forHumans(['short' => true, 'parts' => 2]),
                        'activa' => is_null($c->fecha_fin),
                    ];
                })->values()->toArray(),
            ])
            ->values()
            ->toArray();

        $this->timeline = [
            'min_year' => 2015,
            'max_year' => now()->year,
            'px_per_day' => 1,
            'campos' => $campos
        ];
    }
    public function render()
    {
        return view('livewire.gestion-campania.campania-calendario-component');
    }
}
