<?php

namespace App\Livewire\Costos;

use App\Models\CostoMensual;
use App\Services\Costos\Consolidacion\BddManoObraServicio;
use App\Services\Costos\Consolidacion\ConsolidarCostoInsumosServicio;
use App\Services\Costos\Consolidacion\ConsolidarCostoServiciosCampoServicio;
use App\Services\Costos\Consolidacion\ConsolidarReporteMensualCostos;
use App\Traits\HandlesAlerts;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

/**
 * Consolidación mensual de costos (modal de /costos/campo).
 *
 * "Consolidar mes" solo lee la BDD (resumen_costo_diarios), calcula los totales y genera el Excel: es rápido porque
 * la mano de obra se mantiene al día sola al guardar cada registro. Si algo no cuadra, cada grupo tiene su
 * "Reconstruir", que vuelve a leer las fuentes solo de ese grupo:
 * - Mano de obra (planilla, bonos, cuadrilla, riego y pagos sin horas): registros diarios.
 * - Insumos (pesticidas, fertilizantes): kardex.
 * - Servicios en campo.
 */
class ConsolidadorCostosMensualesComponent extends Component
{
    use HandlesAlerts, LivewireAlert;

    public bool $mostrarModal = false;
    public ?int $anio = null;
    public ?int $mes = null;

    protected $listeners = [
        'abrirConsolidadorCostosMensuales' => 'abrirModal',
    ];

    /** Grupos de costo: qué conceptos muestra y qué fuente reconstruye */
    public const GRUPOS = [
        'mano_obra' => [
            'nombre' => 'Mano de obra',
            'fuente' => 'registros diarios de planilla y cuadrilla, riego y bonos',
            'conceptos' => [
                'costo_planilla' => 'Planilla (campos + FDM + pagos sin horas)',
                'costo_bono_productividad' => 'Bono de productividad (planilla)',
                'costo_cuadrilla' => 'Cuadrilla (jornal + bonos con jornal)',
                'costo_cuadrilla_bono' => 'Cuadrilla bonos (se pagan aparte)',
            ],
        ],
        'insumos' => [
            'nombre' => 'Insumos',
            'fuente' => 'salidas del kardex',
            'conceptos' => ['costo_pesticida' => 'Pesticidas', 'costo_fertilizante' => 'Fertilizantes'],
        ],
        'servicios' => [
            'nombre' => 'Servicios en campo',
            'fuente' => 'servicios registrados en campo',
            'conceptos' => ['costo_servicio_campo' => 'Servicios en campo'],
        ],
        'otros' => [
            'nombre' => 'Otros (se registran aparte)',
            'fuente' => null,
            'conceptos' => ['costo_maquinaria' => 'Maquinaria', 'costo_gastos_generales' => 'Gastos generales'],
        ],
    ];

    public function mount(): void
    {
        $this->anio = (int) date('Y');
        $this->mes = (int) date('n');
    }

    public function abrirModal(): void
    {
        $this->mostrarModal = true;
    }

    public function cerrarModal(): void
    {
        $this->mostrarModal = false;
    }

    public function elegirMes(int $mes): void
    {
        $this->mes = $mes;
    }

    /** Solo lee la BDD: totales del mes y Excel. */
    public function consolidar(): void
    {
        try {
            $this->validarMes();
            $t = microtime(true);
            app(ConsolidarReporteMensualCostos::class)->ejecutar($this->anio, $this->mes);
            $this->alert('success', sprintf('Mes %d/%d consolidado (%.1f s).', $this->mes, $this->anio, microtime(true) - $t));
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    /** Vuelve a leer las fuentes de un grupo y luego consolida el mes. */
    public function reconstruir(string $grupo): void
    {
        try {
            $this->validarMes();
            $inicio = Carbon::create($this->anio, $this->mes, 1)->toDateString();
            $fin = Carbon::create($this->anio, $this->mes, 1)->endOfMonth()->toDateString();
            $t = microtime(true);
            $avisos = [];
            switch ($grupo) {
                case 'mano_obra':
                    $r = app(BddManoObraServicio::class)->asegurarMes($this->anio, $this->mes, true);
                    $avisos = $r['avisos'];
                    break;
                case 'insumos':
                    app(ConsolidarCostoInsumosServicio::class)->consolidarEnRango($inicio, $fin);
                    break;
                case 'servicios':
                    app(ConsolidarCostoServiciosCampoServicio::class)->consolidarEnRango($inicio, $fin);
                    break;
                default:
                    throw new \Exception('Ese grupo no se reconstruye desde el sistema.');
            }
            app(ConsolidarReporteMensualCostos::class)->ejecutar($this->anio, $this->mes);
            $mensaje = sprintf('%s de %d/%d reconstruido y mes consolidado (%.1f s).', self::GRUPOS[$grupo]['nombre'], $this->mes, $this->anio, microtime(true) - $t);
            if ($avisos) {
                $this->alert('warning', $mensaje . ' Revisar: ' . implode(' ', $avisos), ['toast' => false, 'position' => 'center', 'timer' => null, 'showConfirmButton' => true]);
            } else {
                $this->alert('success', $mensaje);
            }
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    private function validarMes(): void
    {
        if (!$this->mes || !$this->anio || $this->mes < 1 || $this->mes > 12) {
            throw new \Exception('Elige el mes y el año.');
        }
    }

    /** Estado de cada mes del año para la tira de meses: ok, diferencia o sin consolidar. */
    private function estadoMeses($costosAnio): array
    {
        $conceptos = array_merge(...array_map(fn($g) => array_keys($g['conceptos']), array_values(self::GRUPOS)));
        $estados = [];
        foreach (range(1, 12) as $m) {
            $c = $costosAnio->get($m);
            if (!$c) {
                $estados[$m] = 'sin';
                continue;
            }
            $descuadre = collect($conceptos)->contains(function ($k) use ($c) {
                $p = $c->{$k};
                $calc = $c->{$k . '_calculado'};
                return $p !== null && $calc !== null && abs((float) $p - (float) $calc) >= 0.01;
            });
            $estados[$m] = $descuadre ? 'diferencia' : 'ok';
        }
        return $estados;
    }

    public function render()
    {
        $costosAnio = $this->mostrarModal ? CostoMensual::where('anio', $this->anio)->get()->keyBy('mes') : collect();
        $costo = $costosAnio->get($this->mes);
        $desactualizados = $this->mostrarModal && $this->mes
            ? count(app(BddManoObraServicio::class)->diasDesactualizados((int) $this->anio, (int) $this->mes))
            : 0;

        return view('livewire.costos.consolidador-costos-mensuales-component', [
            'costo' => $costo,
            'estados' => $this->mostrarModal ? $this->estadoMeses($costosAnio) : [],
            'grupos' => self::GRUPOS,
            'desactualizados' => $desactualizados,
            'urlExcel' => $costo && $costo->reporte_file && Storage::disk('public')->exists($costo->reporte_file)
                ? Storage::disk('public')->url($costo->reporte_file) : null,
        ]);
    }
}
