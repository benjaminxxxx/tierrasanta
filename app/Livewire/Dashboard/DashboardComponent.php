<?php

namespace App\Livewire\Dashboard;
use App\Models\CochinillaInfestacion;
use App\Models\EstadisticaMensual;
use App\Services\RecursosHumanos\Planilla\PlanillaEmpleadoServicio;
use App\Traits\Selectores\ConSelectorMes;
use Livewire\Component;
use Str;

class DashboardComponent extends Component
{
    use ConSelectorMes;
    public array $estadisticas = [];
    public string $reload = '';
    public function mount()
    {
        $this->inicializarMesAnio();
        $this->generarReload();

    }
    public function generarReload(){
        $this->reload = Str::random(10);
    }
    protected function despuesMesAnioModificado($mes, $anio)
    {
        $this->obtenerEstadistica();
    }
    public function obtenerEstadistica()
    {
        $this->estadisticas = EstadisticaMensual::where('mes', $this->mes)
            ->where('anio', $this->anio)
            ->get()
            ->keyBy('clave')  // índice por clave
            ->map(fn($e) => [
                'valor' => (float) $e->valor,
                'valor_anterior' => $e->valor_anterior !== null ? (float) $e->valor_anterior : null,
            ])
            ->toArray();
    }
    private function calcularTrend(string $clave): ?float
    {
        $actual = $this->estadisticas[$clave]['valor'] ?? null;
        $anterior = $this->estadisticas[$clave]['valor_anterior'] ?? null;

        if ($actual === null || $anterior === null || $anterior == 0) {
            return null;
        }

        return round((($actual - $anterior) / $anterior) * 100, 1);
    }
    public function actualizar()
    {
        $mes = $this->mes;
        $anio = $this->anio;
        $claves = [
            'total_empleados_agraria' => PlanillaEmpleadoServicio::totalActivos($mes, $anio, 'agraria'),
            'total_empleados_oficina' => PlanillaEmpleadoServicio::totalActivos($mes, $anio, 'oficina'),
            //'contratados_mes' => ContratoServicio::contratadosEnMes($mes, $anio),
            //'tasa_rotacion' => ContratoServicio::tasaRotacion($anio),
        ];

        $infestacionesMes = CochinillaInfestacion::whereMonth('fecha', $mes)
            ->whereYear('fecha', $anio)
            ->get();

        $porTipo = $infestacionesMes->groupBy('tipo_infestacion');
        $infestaciones = $porTipo->get('infestacion', collect());
        $reinfestaciones = $porTipo->get('reinfestacion', collect());

        $claves = array_merge($claves, [
            // Conteos
            'cochinilla_total_infestaciones' => $infestacionesMes->count(),
            'cochinilla_campos_infestacion' => $infestaciones->unique('campo_nombre')->count(),
            'cochinilla_campos_reinfestacion' => $reinfestaciones->unique('campo_nombre')->count(),

            // Área
            'cochinilla_area_infestacion' => round($infestaciones->sum('area'), 3),
            'cochinilla_area_reinfestacion' => round($reinfestaciones->sum('area'), 3),

            // KG Madres
            'cochinilla_kg_madres_infestacion' => round($infestaciones->sum('kg_madres'), 2),
            'cochinilla_kg_madres_reinfestacion' => round($reinfestaciones->sum('kg_madres'), 2),

            // Eficiencia (promedio ponderado)
            'cochinilla_kg_madres_ha_promedio' => $infestacionesMes->sum('area') > 0
                ? round($infestacionesMes->sum('kg_madres') / $infestacionesMes->sum('area'), 2)
                : 0,
            'cochinilla_infestadores_ha_promedio' => $infestacionesMes->sum('area') > 0
                ? round($infestacionesMes->sum(fn($i) => $i->infestadores ?? 0) / $infestacionesMes->sum('area'), 0)
                : 0,

            // Por método
            'cochinilla_malla_count' => $infestacionesMes->filter(fn($i) => strtoupper($i->metodo) === 'MALLA')->count(),
            'cochinilla_tubo_count' => $infestacionesMes->filter(fn($i) => strtoupper($i->metodo) === 'TUBO')->count(),
            'cochinilla_carton_count' => $infestacionesMes->filter(fn($i) => strtoupper($i->metodo) === 'CARTON')->count(),
        ]);

        $mesPrevio = $mes === 1 ? 12 : $mes - 1;
        $anioPrevio = $mes === 1 ? $anio - 1 : $anio;

        foreach ($claves as $clave => $valor) {
            $anterior = EstadisticaMensual::where([
                'mes' => $mesPrevio,
                'anio' => $anioPrevio,
                'clave' => $clave,
            ])->value('valor');

            EstadisticaMensual::updateOrCreate(
                ['mes' => $mes, 'anio' => $anio, 'clave' => $clave],
                ['valor' => $valor, 'valor_anterior' => $anterior]
            );
        }
        $this->obtenerEstadistica();
        $this->generarReload();
    }
    public function render()
    {
        return view('livewire.dashboard.dashboard-component');
    }
}