<?php

namespace App\Livewire\GestionCampania;

use App\Models\CampoCampania;
use App\Models\CostoMensual;
use App\Models\CostoMensualDistribucion;
use Carbon\Carbon;
use Livewire\Component;
use Session;

class GestionCampaniaCostos extends Component
{
    // ── Filtros ──────────────────────────────────────────────────────────────
    public $campoSeleccionado;
    public $campaniaSeleccionada;

    // ── Datos ────────────────────────────────────────────────────────────────
    public $campanias = [];
    public $campaniaActual = null;
    public $filasMeses = [];
    public $duracionMeses = 0;
    public $totales = [
        'total_distribuido' => 0,
        'meses_con_distribucion' => 0,
        'meses_sin_costo' => 0,
    ];

    // ── Modal Cierre de Campaña ──────────────────────────────────────────────
    public bool $mostrandoCierreCampania = false;
    public string $fechaCierreCampania = '';
    public string $motivoCierre = '';

    private const MESES = [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ];

    // Campos de costo con etiquetas legibles
    private const CAMPOS_COSTO = [
        'fijo_administrativo' => 'Administrativo',
        'fijo_financiero' => 'Financiero',
        'fijo_gastos_oficina' => 'Gastos Oficina',
        'fijo_depreciaciones' => 'Depreciaciones',
        'fijo_costo_terreno' => 'Costo Terreno',
        'operativo_servicios_fundo' => 'Servicios Fundo',
        'operativo_mano_obra_indirecta' => 'Mano Obra Ind.',
    ];

    protected $listeners = ['distribucionAprobada' => 'cargarDatosCampania'];
    // ════════════════════════════════════════════════════════════════════════
    //  CICLO DE VIDA
    // ════════════════════════════════════════════════════════════════════════

    public function mount($campaniaId = null): void
    {
        $this->campoSeleccionado = Session::get('campo_seleccionado');
        $this->fechaCierreCampania = now()->format('Y-m-d');
        if ($campaniaId) {
            $campania = CampoCampania::find($campaniaId);
            if ($campania) {
                $this->campoSeleccionado = $campania->campo;
                $this->campaniaSeleccionada = $campaniaId;
                $this->fechaCierreCampania = $campania->fecha_fin;
                Session::put('campo_seleccionado', $this->campoSeleccionado);
            }

        }

        
        $this->obtenerCampanias();
        $this->cargarDatosCampania();
    }

    // ════════════════════════════════════════════════════════════════════════
    //  WATCHERS
    // ════════════════════════════════════════════════════════════════════════

    public function updatedCampoSeleccionado(): void
    {
        $this->campaniaSeleccionada = null;
        $this->campaniaActual = null;
        $this->filasMeses = [];
        Session::put('campo_seleccionado', $this->campoSeleccionado);
        $this->obtenerCampanias();
    }

    public function updatedCampaniaSeleccionada(): void
    {
        $this->cargarDatosCampania();
    }

    // ════════════════════════════════════════════════════════════════════════
    //  CARGA DE DATOS
    // ════════════════════════════════════════════════════════════════════════

    public function obtenerCampanias(): void
    {
        $this->campanias = CampoCampania::where('campo', $this->campoSeleccionado)
            ->orderBy('fecha_inicio')
            ->get()
            ->toArray();
    }

    /**
     * Construye las filas mes a mes usando los datos ya guardados
     * en costos_mensuales y costo_mensual_distribuciones.
     * No recalcula nada: solo lee y presenta.
     */
    public function cargarDatosCampania(): void
    {
        $this->filasMeses = [];
        $this->campaniaActual = null;
        $this->totales = [
            'total_distribuido' => 0,
            'meses_con_distribucion' => 0,
            'meses_sin_costo' => 0,
        ];

        if (!$this->campaniaSeleccionada) {
            return;
        }

        $campania = CampoCampania::find($this->campaniaSeleccionada);
        if (!$campania) {
            return;
        }

        $this->campaniaActual = $campania->toArray();

        $inicioCampania = Carbon::parse($campania->fecha_inicio);
        $finCampania = $campania->fecha_fin ? Carbon::parse($campania->fecha_fin) : null;

        $inicio = $inicioCampania->copy()->startOfMonth();
        $fin = ($finCampania ?? now())->copy()->startOfMonth();

        $this->duracionMeses = $inicio->diffInMonths($fin) + 1;

        // Cargar costos mensuales del rango de años involucrados
        $costosMensuales = CostoMensual::whereBetween('anio', [$inicio->year, $fin->year])
            ->get()
            ->keyBy(fn($c) => "{$c->anio}-{$c->mes}");

        // Cargar distribuciones ya guardadas para esta campaña
        $distribuciones = CostoMensualDistribucion::where('campo_campania_id', $campania->id)
            ->get()
            ->keyBy(fn($d) => "{$d->anio}-{$d->mes}");

        $filas = [];
        $totalDistribuido = 0;
        $mesesConDist = 0;
        $mesesSinCosto = 0;
        $cursor = $inicio->copy();

        while ($cursor->lte($fin)) {
            $key = "{$cursor->year}-{$cursor->month}";
            $costo = $costosMensuales[$key] ?? null;
            $distribucion = $distribuciones[$key] ?? null;

            $tieneCosto = (bool) $costo;
            $tieneDistribucion = (bool) $distribucion;

            // ── Totales del mes (blanco+negro de cada tipo) ────────────────
            $totalMesBlanco = 0;
            $totalMesNegro = 0;
            $detalleCostos = [];   // para la subtabla colapsable

            if ($tieneCosto) {
                foreach (self::CAMPOS_COSTO as $campo => $etiqueta) {
                    $campoB = "{$campo}_blanco";
                    $campoN = "{$campo}_negro";

                    $totalMesBlanco += (float) ($costo->$campoB ?? 0);
                    $totalMesNegro += (float) ($costo->$campoN ?? 0);
                }
            }

            // ── Montos ya distribuidos para esta campaña ───────────────────
            $distribBlanco = 0;
            $distribNegro = 0;
            $porcentaje = null;
            $diasActivos = null;
            $diasMes = $cursor->daysInMonth;
            $costoMensualId = $costo?->id;

            if ($tieneDistribucion) {
                $porcentaje = (float) $distribucion->porcentaje;
                $diasActivos = (int) $distribucion->dias_activos;

                foreach (self::CAMPOS_COSTO as $campo => $etiqueta) {
                    $montoDist = (float) ($distribucion->$campo ?? 0);

                    // Blanco y negro: proporcional al peso de cada color sobre el total
                    // La distribución guarda el monto total (B+N) prorrateado
                    // Calculamos blanco/negro proporcionalmente desde costos_mensuales
                    $totalCampo = 0;
                    $blancoBase = 0;
                    $negroBase = 0;

                    if ($tieneCosto) {
                        $campoB = "{$campo}_blanco";
                        $campoN = "{$campo}_negro";
                        $blancoBase = (float) ($costo->$campoB ?? 0);
                        $negroBase = (float) ($costo->$campoN ?? 0);
                        $totalCampo = $blancoBase + $negroBase;
                    }

                    $montoBlanco = $totalCampo > 0 ? round($montoDist * ($blancoBase / $totalCampo), 2) : 0;
                    $montoNegro = $totalCampo > 0 ? round($montoDist * ($negroBase / $totalCampo), 2) : 0;

                    $distribBlanco += $montoBlanco;
                    $distribNegro += $montoNegro;

                    $detalleCostos[] = [
                        'etiqueta' => $etiqueta,
                        'campo' => $campo,
                        'total_blanco' => $blancoBase,
                        'total_negro' => $negroBase,
                        'total_mes' => $blancoBase + $negroBase,
                        'dist_blanco' => $montoBlanco,
                        'dist_negro' => $montoNegro,
                        'dist_total' => $montoDist,
                    ];
                }

                $totalDistribuido += $distribBlanco + $distribNegro;
                $mesesConDist++;
            }

            if (!$tieneCosto) {
                $mesesSinCosto++;
            }

            $filas[] = [
                'key' => $key,
                'anio' => $cursor->year,
                'mes' => $cursor->month,
                'nombre_mes' => self::MESES[$cursor->month],
                'dias_mes' => $diasMes,
                'dias_activos' => $diasActivos,
                'porcentaje' => $porcentaje,
                'tiene_costo' => $tieneCosto,
                'tiene_distribucion' => $tieneDistribucion,
                'total_mes_blanco' => $totalMesBlanco,
                'total_mes_negro' => $totalMesNegro,
                'total_mes' => $totalMesBlanco + $totalMesNegro,
                'dist_blanco' => round($distribBlanco, 2),
                'dist_negro' => round($distribNegro, 2),
                'dist_total' => round($distribBlanco + $distribNegro, 2),
                'es_primer_mes' => $cursor->isSameMonth($inicioCampania),
                'es_ultimo_mes' => $finCampania && $cursor->isSameMonth($finCampania),
                'costo_mensual_id' => $costoMensualId,
                'detalle_costos' => $detalleCostos,
            ];

            $cursor->addMonth();
        }

        $this->filasMeses = $filas;
        $this->totales = [
            'total_distribuido' => round($totalDistribuido, 2),
            'meses_con_distribucion' => $mesesConDist,
            'meses_sin_costo' => $mesesSinCosto,
            'total_meses' => count($filas),
        ];
    }

    // ════════════════════════════════════════════════════════════════════════
    //  RECÁLCULO — delega al servicio existente
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Dispara el panel de distribución del mes indicado,
     * usando el componente CostosMensualesDistribucionFormComponent ya existente.
     */
    public function recalcularMes(int $costoMensualId): void
    {
        $this->dispatch('distribuirCostosMensuales', $costoMensualId);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  CIERRE DE CAMPAÑA
    // ════════════════════════════════════════════════════════════════════════

    public function mostrarCerrarCampania(): void
    {
        $this->fechaCierreCampania = now()->format('Y-m-d');
        $this->motivoCierre = '';
        $this->mostrandoCierreCampania = true;
    }

    public function confirmarCierreCampania(): void
    {
        $this->validate([
            'fechaCierreCampania' => 'required|date',
        ], [
            'fechaCierreCampania.required' => 'La fecha de cierre es requerida.',
        ]);

        $campania = CampoCampania::find($this->campaniaSeleccionada);
        if (!$campania) {
            return;
        }

        $campania->update(['fecha_fin' => $this->fechaCierreCampania]);

        $this->mostrandoCierreCampania = false;
        $this->campaniaActual = $campania->fresh()->toArray();
        $this->cargarDatosCampania();

        session()->flash('success', 'Campaña cerrada exitosamente.');
    }

    // ════════════════════════════════════════════════════════════════════════
    //  RENDER
    // ════════════════════════════════════════════════════════════════════════

    public function render()
    {
        return view('livewire.gestion-campania.gestion-campania-costos');
    }
}