<?php

namespace App\Livewire\Planilla;

use App\Exceptions\ParametroAfectaPlanillas;
use App\Models\PlanPrimaComisionHistorico;
use App\Services\Planilla\Parametro\PlanillaParametroProceso;
use Carbon\Carbon;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class ConfiguracionPrimasComisionesComponent extends Component
{
    use LivewireAlert, \App\Traits\HandlesAlerts;

    public array $fechas = [];
    public string $fecha_inicio;
    public string $informacion = '';
    public bool $modoRegistro = false;
    /** Motivo del cambio (publicación SBS, resolución…) */
    public ?string $motivo = null;
    /** Cambio pendiente de confirmar porque afecta planillas generadas, y los meses afectados */
    public ?array $pendiente = null;
    public array $afectados = [];

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
            $this->errorAlert('Pega los datos de la SBS antes de generar los montos.');
            return;
        }

        try {
            $valores = $this->parsearInformacion($this->informacion);

            if (empty($valores)) {
                $this->errorAlert('No se reconoció ninguna AFP en el texto pegado. Verifica que copiaste la tabla completa desde la SBS.');
                return;
            }

            $this->ejecutar(['accion' => 'guardar', 'valores' => $valores, 'motivo' => $this->motivo]);
        } catch (\Throwable $th) {
            $this->errorAlert($th, 'Ocurrió un error inesperado');
        }
    }

    public function eliminarRegistros(): void
    {
        $this->ejecutar(['accion' => 'eliminar']);
    }

    /** "Sí, guardar y regenerar" */
    public function confirmar(): void
    {
        if ($this->pendiente) {
            $this->ejecutar($this->pendiente, true);
        }
    }

    public function cancelarConfirmacion(): void
    {
        $this->reset(['pendiente', 'afectados']);
    }

    /** Guarda o elimina por el proceso de parámetros: si toca meses con planilla generada pide confirmar y regenera. */
    private function ejecutar(array $cambio, bool $confirmado = false): void
    {
        try {
            $this->authorize(\App\Constants\Permisos::PLANILLA_CONFIG_AFP_GESTIONAR);
            $proceso = app(PlanillaParametroProceso::class);
            if ($cambio['accion'] === 'guardar') {
                $proceso->guardarAfp($this->fecha_inicio, $cambio['valores'], $cambio['motivo'], $confirmado);
                $texto = 'Valores guardados para ' . $this->fecha_inicio . '.';
            } else {
                $proceso->eliminarAfp($this->fecha_inicio, $confirmado);
                $texto = 'Los valores de ' . $this->fecha_inicio . ' fueron eliminados.';
            }
            $this->reset(['pendiente', 'afectados', 'motivo']);
            $this->modoRegistro = false;
            $this->informacion = '';
            $regenerados = collect($proceso->regenerados)->map(fn($r) => ($r['ok'] ? '✔ ' : '✖ ') . $r['periodo'] . ' ' . $r['planilla'] . ': ' . $r['detalle'])->implode(' · ');
            $this->alert('success', $texto . ($regenerados ? ' Planillas regeneradas: ' . $regenerados : ''), $regenerados ? ['timer' => 12000] : []);
        } catch (ParametroAfectaPlanillas $e) {
            $this->pendiente = $cambio;
            $this->afectados = $e->meses;
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
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

        $fecha = Carbon::createFromFormat('Y-m-d', $this->fecha_inicio . '-01')->startOfMonth()->format('Y-m-d');

        $registros = PlanPrimaComisionHistorico::whereDate('fecha_inicio', $fecha)
            ->orderByRaw("FIELD(referencia, 'HABITAT', 'INTEGRA', 'PRIMA', 'PROFUTURO')")
            ->get();

        return view('livewire.planilla.configuracion-primas-comisiones-component', [
            'fechasRegistradas' => $fechasRegistradas,
            'registros' => $registros,
        ]);
    }
}