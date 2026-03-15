<?php

namespace App\Livewire\GestionCochinilla;

use App\Models\CochinillaInfestacion;
use App\Models\EstadisticaMensual;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CochinillaInfestacionStatsComponent extends Component
{
    public int $mes;
    public int $anio;
    public array $estadisticas = [];
    public function mount()
    {
        $this->cargar();
    }
    public function cargar(): void
    {
        $claves = [
            'cochinilla_total_infestaciones',
            'cochinilla_campos_infestacion',
            'cochinilla_campos_reinfestacion',
            'cochinilla_area_infestacion',
            'cochinilla_area_reinfestacion',
            'cochinilla_kg_madres_infestacion',
            'cochinilla_kg_madres_reinfestacion',
            'cochinilla_kg_madres_ha_promedio',
            'cochinilla_infestadores_ha_promedio',
            'cochinilla_malla_count',
            'cochinilla_tubo_count',
            'cochinilla_carton_count',
        ];

        $this->estadisticas = EstadisticaMensual::where('mes', $this->mes)
            ->where('anio', $this->anio)
            ->whereIn('clave', $claves)
            ->get()
            ->keyBy('clave')
            ->toArray();
    }

    // Helpers para la vista
    public function e(string $clave): mixed
    {
        return $this->estadisticas[$clave]['valor'] ?? 0;
    }

    public function t(string $clave): float
    {
        $valor = $this->estadisticas[$clave]['valor'] ?? 0;
        $anterior = $this->estadisticas[$clave]['valor_anterior'] ?? 0;

        if ($anterior == 0)
            return 0;
        return round((($valor - $anterior) / $anterior) * 100, 1);
    }

    public function render()
    {
        return view('livewire.gestion-cochinilla.cochinilla-infestacion-stats-component');
    }
}
