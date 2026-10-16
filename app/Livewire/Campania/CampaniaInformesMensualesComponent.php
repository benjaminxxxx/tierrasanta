<?php

namespace App\Livewire\Campania;

use App\Models\CampaniaInformeMensual;
use App\Models\CampoCampania;
use App\Services\Campania\InformeMensual\CampaniaInformeMensualEstadoConsulta;
use App\Services\Campania\InformeMensual\CampaniaInformeMensualExcel;
use App\Services\Campania\InformeMensual\CampaniaInformeMensualFormatoReglas;
use App\Services\Campania\InformeMensual\CampaniaInformeMensualProceso;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Campaña → Informes mensuales. Al cierre de cada mes se genera el informe acumulado de cada campaña activa (desde su
 * inicio hasta el fin de mes, o hasta su fecha de fin si cierra ese mes). Cada informe queda guardado: botón para el
 * Excel y para verlo en la web. Si se corrige un mes, "Recalcular desde aquí" rehace ese mes y los siguientes.
 */
#[Title('Informes mensuales de campaña')]
class CampaniaInformesMensualesComponent extends Component
{
    use LivewireAlert, \App\Traits\HandlesAlerts;

    #[Url]
    public string $vista = 'mes';
    #[Url]
    public int $anio;
    #[Url]
    public int $mes;
    #[Url]
    public ?string $campo = null;
    #[Url]
    public ?int $campaniaId = null;

    public bool $mostrarInforme = false;
    public ?int $informeId = null;

    public function mount(): void
    {
        // Por defecto, el último mes cerrado
        $anterior = now()->subMonthNoOverflow();
        $this->anio ??= $anterior->year;
        $this->mes ??= $anterior->month;
    }

    public function updatedCampo(): void
    {
        $this->campaniaId = CampoCampania::where('campo', $this->campo)->orderByDesc('fecha_inicio')->value('id');
    }

    public function verCampania(int $campaniaId): void
    {
        $campania = CampoCampania::findOrFail($campaniaId);
        $this->campo = $campania->campo;
        $this->campaniaId = $campania->id;
        $this->vista = 'campania';
    }

    public function generar(int $campaniaId, int $anio, int $mes): void
    {
        $this->ejecutar(function () use ($campaniaId, $anio, $mes) {
            $informe = app(CampaniaInformeMensualProceso::class)->generar(CampoCampania::findOrFail($campaniaId), $anio, $mes);
            $this->alert('success', "Informe generado (versión {$informe->version}).");
        });
    }

    /** Rehace el mes y los meses siguientes ya generados de la campaña. */
    public function recalcularDesde(int $campaniaId, int $anio, int $mes): void
    {
        $this->ejecutar(function () use ($campaniaId, $anio, $mes) {
            $hechos = app(CampaniaInformeMensualProceso::class)->regenerarDesde(CampoCampania::findOrFail($campaniaId), $anio, $mes);
            $this->alert('success', count($hechos) === 1 ? 'Informe recalculado.' : 'Se recalcularon ' . count($hechos) . ' meses: ' . collect($hechos)->map(fn($i) => sprintf('%02d/%d', $i->mes, $i->anio))->implode(', ') . '.');
        });
    }

    /** Genera los informes del mes seleccionado que todavía no existen. */
    public function generarPendientesDelMes(): void
    {
        $this->ejecutar(function () {
            $r = app(CampaniaInformeMensualProceso::class)->generarMes($this->anio, $this->mes, true);
            $mensaje = count($r['generados']) . ' informe(s) generado(s).';
            if ($r['errores']) {
                $this->alert('warning', $mensaje . ' Con error: ' . implode(' · ', $r['errores']), ['timer' => 12000]);
                return;
            }
            $this->alert('success', $mensaje);
        });
    }

    public function ver(int $informeId): void
    {
        $this->informeId = $informeId;
        $this->mostrarInforme = true;
    }

    public function descargar(int $informeId)
    {
        $informe = CampaniaInformeMensual::with('generadoPor:id,name')->findOrFail($informeId);
        $excel = app(CampaniaInformeMensualExcel::class);
        $libro = $excel->generar($informe);
        return response()->streamDownload(fn() => (new Xlsx($libro))->save('php://output'), $excel->nombreArchivo($informe));
    }

    private function ejecutar(callable $accion): void
    {
        abort_unless(auth()->user()->can(\App\Constants\Permisos::CAMPAÑA_INFORME_MENSUAL_GESTIONAR), 403);
        try {
            $accion();
        } catch (ValidationException $e) {
            $this->errorAlert(collect($e->errors())->flatten()->implode(' '));
        } catch (\Throwable $e) {
            report($e);
            $this->errorAlert($e);
        }
    }

    public function render()
    {
        $estado = app(CampaniaInformeMensualEstadoConsulta::class);
        $campania = $this->vista === 'campania' && $this->campaniaId ? CampoCampania::find($this->campaniaId) : null;
        $informe = $this->mostrarInforme && $this->informeId ? CampaniaInformeMensual::with(['generadoPor:id,name', 'campania'])->find($this->informeId) : null;

        return view('livewire.campania.campania-informes-mensuales-component', [
            'estadoMes' => $this->vista === 'mes' ? $estado->estadoMes($this->anio, $this->mes) : null,
            'campania' => $campania,
            'mesesCampania' => $campania ? $estado->estadoCampania($campania) : [],
            'campaniasDelCampo' => $this->campo ? CampoCampania::where('campo', $this->campo)->orderByDesc('fecha_inicio')->get(['id', 'nombre_campania', 'fecha_inicio', 'fecha_fin']) : collect(),
            'informe' => $informe,
            'tablas' => $informe ? CampaniaInformeMensualFormatoReglas::tablas($informe->datos + ['advertencias' => $informe->advertenciasVigentes()]) : [],
        ]);
    }
}
