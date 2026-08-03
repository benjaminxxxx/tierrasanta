<?php

namespace App\Livewire;

use App\Models\PlanPrimaComisionHistorico;
use Carbon\Carbon;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class ConfiguracionPrimasComisionesComponent extends Component
{
    use LivewireAlert;

    public array $fechas = [];
    public string $fecha_inicio;
    public string $informacion = '';
    public bool $modoRegistro = false;

    protected array $referenciasValidas = ['HABITAT', 'INTEGRA', 'PRIMA', 'PROFUTURO'];

    public function mount(): void
    {
        $this->generarFechas();
    }

    public function generarFechas(): void
    {
        $inicio = Carbon::createFromDate(1993, 6, 1);
        $actual = Carbon::now();

        while ($actual > $inicio) {
            $this->fechas[] = $actual->format('Y-m');
            $actual->subMonth();
        }

        $this->fecha_inicio = Carbon::now()->format('Y-m');
    }

    public function cambiarFechaA(string $fecha): void
    {
        $this->fecha_inicio = $fecha;
        $this->modoRegistro = false;
        $this->informacion = '';
    }

    public function abrirModoRegistro(): void
    {
        $this->modoRegistro = true;
        $this->informacion = '';
    }

    public function cancelarRegistro(): void
    {
        $this->modoRegistro = false;
        $this->informacion = '';
    }

    public function guardarPrimasComisiones(): void
    {
        if (!$this->informacion || trim($this->informacion) === '') {
            $this->alert('error', 'Pega los datos de la SBS antes de generar los montos.');
            return;
        }

        try {
            $valores = $this->parsearInformacion($this->informacion);

            if (empty($valores)) {
                $this->alert('error', 'No se reconoció ninguna AFP en el texto pegado. Verifica que copiaste la tabla completa desde la SBS.');
                return;
            }

            $fecha = Carbon::createFromFormat('Y-m', $this->fecha_inicio)->startOfMonth()->format('Y-m-d');

            foreach ($valores as $referencia => $datos) {
                PlanPrimaComisionHistorico::updateOrCreate(
                    ['referencia' => $referencia, 'fecha_inicio' => $fecha],
                    $datos
                );
            }

            $this->modoRegistro = false;
            $this->informacion = '';

            $this->alert('success', 'Valores guardados correctamente para ' . $this->fecha_inicio . '.');
        } catch (\Throwable $th) {
            $this->alert('error', 'Ocurrió un error inesperado: ' . $th->getMessage());
        }
    }

    public function eliminarRegistros(): void
    {
        $fecha = Carbon::createFromFormat('Y-m', $this->fecha_inicio)->startOfMonth()->format('Y-m-d');
        PlanPrimaComisionHistorico::whereDate('fecha_inicio', $fecha)->delete();

        $this->alert('success', 'Los valores de ' . $this->fecha_inicio . ' fueron eliminados.');
    }

    protected function parsearInformacion(string $informacion): array
    {
        $filas = explode("\n", $informacion);
        $valores = [];

        foreach ($filas as $fila) {
            $columnas = preg_split('/\t+/', trim($fila));

            if (count($columnas) < 5) {
                continue;
            }

            $referencia = strtoupper(trim($columnas[0]));

            if (!in_array($referencia, $this->referenciasValidas)) {
                continue; // ignora encabezados u otras filas que no sean AFP
            }

            $valores[$referencia] = [
                'comision_flujo' => $this->formatearPorcentaje($columnas[1]),
                'comision_saldo' => $this->formatearPorcentaje($columnas[2]),
                'prima_seguros' => $this->formatearPorcentaje($columnas[3]),
                'aporte_obligatorio' => $this->formatearPorcentaje($columnas[4]),
                'remuneracion_maxima_asegurable' => $this->formatearMonto($columnas[5] ?? null),
            ];
        }

        return $valores;
    }

    protected function formatearPorcentaje(?string $valor): float
    {
        return (float) str_replace([' ', '%', ','], ['', '', '.'], trim((string) $valor));
    }

    protected function formatearMonto(?string $valor): ?float
    {
        if (is_null($valor) || trim($valor) === '') {
            return null;
        }

        return (float) str_replace([' ', ','], ['', '.'], trim($valor));
    }

    public function render()
    {
        $fechasRegistradas = PlanPrimaComisionHistorico::select('fecha_inicio')
            ->distinct()
            ->orderByDesc('fecha_inicio')
            ->pluck('fecha_inicio')
            ->map(fn ($f) => Carbon::parse($f)->format('Y-m'))
            ->toArray();

        $fecha = Carbon::createFromFormat('Y-m', $this->fecha_inicio)->startOfMonth()->format('Y-m-d');

        $registros = PlanPrimaComisionHistorico::whereDate('fecha_inicio', $fecha)
            ->orderByRaw("FIELD(referencia, 'HABITAT', 'INTEGRA', 'PRIMA', 'PROFUTURO')")
            ->get();

        return view('livewire.configuracion-primas-comisiones-component', [
            'fechasRegistradas' => $fechasRegistradas,
            'registros' => $registros,
        ]);
    }
}