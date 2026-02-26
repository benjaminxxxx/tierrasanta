<?php

namespace App\Livewire\Dashboard;
use App\Models\EstadisticaMensual;
use App\Services\RecursosHumanos\Planilla\PlanillaEmpleadoServicio;
use App\Traits\Selectores\ConSelectorMes;
use Livewire\Component;

class DashboardComponent extends Component
{
    use ConSelectorMes;
    public array $estadisticas = [];
    public function mount()
    {
        $this->inicializarMesAnio();


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
            //dd($this->estadisticas);
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
    }
    public function render()
    {
        return view('livewire.dashboard.dashboard-component');
    }
}