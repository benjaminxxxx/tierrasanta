<?php

namespace App\Livewire\Reporte;

use App\Services\Reporte\HistorialLaboral\ReporteHistorialLaboralConsulta;
use App\Services\Reporte\HistorialLaboral\ReporteHistorialLaboralExcel;
use App\Traits\HandlesAlerts;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\Title;
use Livewire\Component;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Reporte y Auditoría → Historial laboral: se busca al trabajador por nombre parcial o DNI (planilla y, si se marca,
 * cuadrilla), se eligen una o varias identidades (el mismo trabajador puede estar en cuadrilla con otro nombre) y se
 * ve qué hizo día a día en el periodo, con su producción contra el estándar. Descarga Excel.
 */
#[Title('Historial laboral')]
class ReporteHistorialLaboralComponent extends Component
{
    use LivewireAlert, HandlesAlerts;

    public string $texto = '';
    public bool $incluirCuadrilla = false;
    /** @var string[] claves "p:{id}" / "c:{id}" */
    public array $seleccionados = [];

    public string $modo = 'mes';
    public ?string $dia = null;
    public int $mes;
    public int $anio;
    public ?string $desde = null;
    public ?string $hasta = null;

    public function mount(): void
    {
        $hoy = Carbon::today();
        $this->dia = $hoy->toDateString();
        $this->mes = $hoy->month;
        $this->anio = $hoy->year;
        $this->desde = $hoy->copy()->startOfMonth()->toDateString();
        $this->hasta = $hoy->toDateString();
    }

    public function agregar(string $clave): void
    {
        if (!in_array($clave, $this->seleccionados, true)) {
            $this->seleccionados[] = $clave;
        }
    }

    public function quitar(string $clave): void
    {
        $this->seleccionados = array_values(array_diff($this->seleccionados, [$clave]));
    }

    /** @return array{0:string, 1:string} */
    private function periodo(): array
    {
        return match ($this->modo) {
            'dia' => [$this->dia ?: Carbon::today()->toDateString(), $this->dia ?: Carbon::today()->toDateString()],
            'anio' => [Carbon::create($this->anio, 1, 1)->toDateString(), Carbon::create($this->anio, 12, 31)->toDateString()],
            'rango' => [min($this->desde, $this->hasta), max($this->desde, $this->hasta)],
            default => [Carbon::create($this->anio, $this->mes, 1)->toDateString(), Carbon::create($this->anio, $this->mes, 1)->endOfMonth()->toDateString()],
        };
    }

    public function descargar()
    {
        try {
            if (!$this->seleccionados) {
                throw new \App\Exceptions\ErrorControlado('Elige al menos un trabajador.');
            }
            [$desde, $hasta] = $this->periodo();
            $consulta = app(ReporteHistorialLaboralConsulta::class);
            $identidades = $consulta->identidades($this->seleccionados);
            $libro = app(ReporteHistorialLaboralExcel::class)->generar($consulta->historial($this->seleccionados, $desde, $hasta), $identidades, $desde, $hasta);
            $nombre = 'HISTORIAL_' . preg_replace('/[^A-Z0-9]+/', '_', mb_strtoupper(\Illuminate\Support\Str::ascii($identidades[0]['nombre'] ?? 'TRABAJADOR')))
                . "_{$desde}_{$hasta}.xlsx";
            return response()->streamDownload(fn() => (new Xlsx($libro))->save('php://output'), $nombre);
        } catch (\Throwable $e) {
            $this->errorAlert($e);
            return null;
        }
    }

    public function render()
    {
        $consulta = app(ReporteHistorialLaboralConsulta::class);
        $elegidos = $consulta->identidades($this->seleccionados);

        $resultados = mb_strlen(trim($this->texto)) >= 2 ? $consulta->buscar($this->texto, $this->incluirCuadrilla) : [];
        // Planilleros elegidos: cuadrilleros que podrían ser la misma persona (mismo DNI o nombre parecido)
        $sugerencias = collect($elegidos)->where('tipo', 'planilla')
            ->flatMap(fn($e) => $consulta->posiblesEnCuadrilla((int) substr($e['clave'], 2)))
            ->reject(fn($c) => in_array($c['clave'], $this->seleccionados, true))->unique('clave')->values()->all();

        [$desde, $hasta] = $this->periodo();
        return view('livewire.reporte.reporte-historial-laboral-component', [
            'resultados' => $resultados,
            'elegidos' => $elegidos,
            'sugerencias' => $sugerencias,
            'desde' => $desde,
            'hasta' => $hasta,
            'historial' => $this->seleccionados ? $consulta->historial($this->seleccionados, $desde, $hasta) : null,
        ]);
    }
}
