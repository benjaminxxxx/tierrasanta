<?php

namespace App\Livewire\GestionCochinilla;

use App\Models\CochinillaInfestacion;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CochinillaInfestacionResumenComponent extends Component
{
    use LivewireAlert;
    public $resumen = [];
    public $mes;
    public $anio;
    public function mount($mes, $anio)
    {
        $this->mes = $mes;
        $this->anio = $anio;

        $infestaciones = CochinillaInfestacion::whereMonth('fecha', $this->mes)
            ->whereYear('fecha', $this->anio)
            ->get();

        $this->resumen = $this->calcularResumen($infestaciones);
    }
    public function calcularResumen($infestaciones): array
    {
        $total = $infestaciones->count();
        if ($total === 0)
            return [];

        $totalKgMadres = $infestaciones->sum('kg_madres');
        $totalInfestadores = $infestaciones->sum(fn($i) => $i->infestadores ?? 0);
        $totalArea = $infestaciones->sum('area');

        // Campos únicos con conteo
        $porCampo = $infestaciones->groupBy('campo_nombre')
            ->map(fn($grupo, $campo) => [
                'campo' => $campo,
                'cantidad' => $grupo->count(),
                'kg_madres' => $grupo->sum('kg_madres'),
                'porcentaje' => round($grupo->count() / $total * 100, 1),
            ])
            ->sortByDesc('cantidad')
            ->values();

        // Métodos
        $porMetodo = $infestaciones->groupBy(fn($i) => strtoupper($i->metodo ?? 'SIN MÉTODO'))
            ->map(fn($grupo, $metodo) => [
                'metodo' => $metodo,
                'cantidad' => $grupo->count(),
                'porcentaje' => round($grupo->count() / $total * 100, 1),
            ])
            ->sortByDesc('cantidad')
            ->values();

        // Tipos
        $porTipo = $infestaciones->groupBy('tipo_infestacion')
            ->map(fn($grupo, $tipo) => [
                'tipo' => strtoupper($tipo ?? 'SIN TIPO'),
                'cantidad' => $grupo->count(),
                'porcentaje' => round($grupo->count() / $total * 100, 1),
            ])
            ->values();

        return [
            'total' => $total,
            'total_kg_madres' => round($totalKgMadres, 2),
            'total_infestadores' => (int) $totalInfestadores,
            'total_area' => round($totalArea, 3),
            'kg_madres_por_ha' => $totalArea > 0 ? round($totalKgMadres / $totalArea, 2) : 0,
            'por_campo' => $porCampo,
            'por_metodo' => $porMetodo,
            'por_tipo' => $porTipo,
        ];
    }
    public function render()
    {
        return view('livewire.gestion-cochinilla.cochinilla-infestacion-resumen-component');
    }
}
