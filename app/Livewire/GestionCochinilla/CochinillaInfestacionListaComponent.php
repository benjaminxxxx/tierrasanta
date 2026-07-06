<?php

namespace App\Livewire\GestionCochinilla;

use App\Models\Auditoria;
use App\Models\Campo;
use App\Models\CampoCampania;
use App\Models\CochinillaInfestacion;
use App\Support\ExcelHelper;
use Exception;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class CochinillaInfestacionListaComponent extends Component
{
    use LivewireAlert, WithPagination;

    public $breadcrumb = [];

    // Filtros
    public string $filtroCampania = '';
    public string $filtroCampo = '';
    public string $filtroTipo = '';
    public string $filtroMetodo = '';
    public string $filtroCampoOrigen = '';
    public string $filtroFechaDesde = '';
    public string $filtroFechaHasta = '';

    // Ordenamiento
    public string $ordenCampo = 'fecha';
    public string $ordenDireccion = 'desc';

    // Listas para selects
    public array $listaCampos = [];
    public array $listaCampanias = [];
    public array $listaCamposOrigen = [];

    
    public ?int $auditoriaModeloId = null;
    public array $auditoriaHistorial = [];
    public bool $modalAuditoria = false;

    protected $queryString = [
        'filtroCampania' => ['except' => ''],
        'filtroCampo' => ['except' => ''],
        'filtroTipo' => ['except' => ''],
        'filtroMetodo' => ['except' => ''],
        'filtroCampoOrigen' => ['except' => ''],
        'filtroFechaDesde' => ['except' => ''],
        'filtroFechaHasta' => ['except' => ''],
        'ordenCampo' => ['except' => 'fecha'],
        'ordenDireccion' => ['except' => 'desc'],
    ];

    public bool $modalVinculacion = false;
    public array $listoParaVincular = [];   // [{ infestacion, campania }]
    public array $conConflicto = [];        // [{ infestacion, motivo }]
    public bool $vinculandoEnProgreso = false;

    public function mount(): void
    {
        $this->breadcrumb = [
            ['label' => 'Infestaciones'],
        ];

        $this->listaCampos = Campo::orderBy('orden')->pluck('nombre')->toArray();
        $this->listaCamposOrigen = $this->listaCampos;
        $this->cargarCampanias();
    }
    /**
     * Abre el modal y analiza TODAS las infestaciones sin campo_campania_id.
     * No modifica nada todavía.
     */
    public function abrirVinculacion(): void
    {
        try {
            $this->listoParaVincular = [];
            $this->conConflicto = [];

            $huerfanas = CochinillaInfestacion::whereNull('campo_campania_id')
                ->orderBy('campo_nombre')
                ->orderBy('fecha')
                ->get(['id', 'campo_nombre', 'fecha', 'tipo_infestacion']);

            if ($huerfanas->isEmpty()) {
                $this->alert('info', 'Todas las infestaciones ya tienen campaña asignada.');
                return;
            }

            // Cargar todas las campañas de los campos involucrados de una vez
            $camposUnicos = $huerfanas->pluck('campo_nombre')->unique()->values();

            $campaniasPorCampo = \DB::table('campos_campanias')
                ->whereIn('campo', $camposUnicos)
                ->orderBy('campo')
                ->orderByDesc('fecha_inicio')
                ->get(['id', 'campo', 'nombre_campania', 'fecha_inicio', 'fecha_fin'])
                ->groupBy('campo');

            foreach ($huerfanas as $infestacion) {
                $fecha = \Carbon\Carbon::parse($infestacion->fecha);
                $campo = $infestacion->campo_nombre;
                $campanias = $campaniasPorCampo->get($campo, collect());

                // ── Sin campañas registradas para ese campo ──────────────────────
                if ($campanias->isEmpty()) {
                    $this->conConflicto[] = [
                        'id' => $infestacion->id,
                        'campo_nombre' => $campo,
                        'fecha' => $infestacion->fecha,
                        'tipo_infestacion' => $infestacion->tipo_infestacion,
                        'motivo' => 'El campo no tiene campañas registradas.',
                        'motivo_tipo' => 'sin_campanias',
                    ];
                    continue;
                }

                // ── Detectar campañas abiertas (sin fecha_fin) ───────────────────
                $abiertas = $campanias->whereNull('fecha_fin');

                if ($abiertas->count() > 1) {
                    // Conflicto: más de una campaña abierta para el mismo campo
                    $nombresAbiertas = $abiertas->pluck('nombre_campania')->implode(', ');
                    $this->conConflicto[] = [
                        'id' => $infestacion->id,
                        'campo_nombre' => $campo,
                        'fecha' => $infestacion->fecha,
                        'tipo_infestacion' => $infestacion->tipo_infestacion,
                        'motivo' => "Campañas abiertas simultáneas sin cierre: {$nombresAbiertas}",
                        'motivo_tipo' => 'multiples_abiertas',
                    ];
                    continue;
                }

                // ── Buscar campaña cuyo rango cubre la fecha ─────────────────────
                $campaniaEncontrada = null;

                foreach ($campanias as $c) {
                    $inicio = \Carbon\Carbon::parse($c->fecha_inicio);
                    $fin = $c->fecha_fin ? \Carbon\Carbon::parse($c->fecha_fin) : null;

                    if ($fecha->gte($inicio) && ($fin === null || $fecha->lte($fin))) {
                        $campaniaEncontrada = $c;
                        break;
                    }
                }

                if ($campaniaEncontrada) {
                    $this->listoParaVincular[] = [
                        'id' => $infestacion->id,
                        'campo_nombre' => $campo,
                        'fecha' => $infestacion->fecha,
                        'tipo_infestacion' => $infestacion->tipo_infestacion,
                        'campania_id' => $campaniaEncontrada->id,
                        'campania_nombre' => $campaniaEncontrada->nombre_campania,
                    ];
                } else {
                    // La fecha no cae en ningún rango — campaña cerrada antes de la fecha
                    // o la fecha es anterior al inicio de la primera campaña
                    $ultimaCampania = $campanias->first(); // ya viene ordenada desc por fecha_inicio
                    $this->conConflicto[] = [
                        'id' => $infestacion->id,
                        'campo_nombre' => $campo,
                        'fecha' => $infestacion->fecha,
                        'tipo_infestacion' => $infestacion->tipo_infestacion,
                        'motivo' => "Ninguna campaña cubre esta fecha. Última campaña: {$ultimaCampania->nombre_campania} (inicio: {$ultimaCampania->fecha_inicio}, cierre: " . ($ultimaCampania->fecha_fin ?? 'sin cierre') . ').',
                        'motivo_tipo' => 'fuera_de_rango',
                    ];
                }
            }

            $this->modalVinculacion = true;

        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    /**
     * Aplica la vinculación solo sobre los registros listos (sin conflicto).
     */
    public function vincularConfirmados(): void
    {
        try {
            if (empty($this->listoParaVincular)) {
                $this->alert('warning', 'No hay infestaciones listas para vincular.');
                return;
            }

            \DB::transaction(function () {
                foreach ($this->listoParaVincular as $item) {
                    CochinillaInfestacion::where('id', $item['id'])
                        ->update(['campo_campania_id' => $item['campania_id']]);
                }
            });

            $cantidad = count($this->listoParaVincular);
            $pendientes = count($this->conConflicto);

            $this->listoParaVincular = [];
            $this->modalVinculacion = false;

            $msg = "{$cantidad} infestación(es) vinculadas correctamente.";
            if ($pendientes > 0) {
                $msg .= " Quedan {$pendientes} con conflicto por resolver.";
            }

            $this->alert('success', $msg);

        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    // Cuando cambia el filtro de campaña, restringir campos disponibles
    public function updatedFiltroCampania(): void
    {
        $this->resetPage();
        $this->filtroCampo = '';
        $this->cargarCamposPorCampania();
    }

    public function updatedFiltroCampo(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroTipo(): void
    {
        $this->resetPage();
    }
    public function updatedFiltroMetodo(): void
    {
        $this->resetPage();
    }
    public function updatedFiltroCampoOrigen(): void
    {
        $this->resetPage();
    }
    public function updatedFiltroFechaDesde(): void
    {
        $this->resetPage();
    }
    public function updatedFiltroFechaHasta(): void
    {
        $this->resetPage();
    }

    private function cargarCampanias(): void
    {
        $this->listaCampanias = CampoCampania::select('nombre_campania')
            ->distinct()
            ->orderBy('nombre_campania')
            ->pluck('nombre_campania')
            ->toArray();
    }

    private function cargarCamposPorCampania(): void
    {
        if (empty($this->filtroCampania)) {
            $this->listaCampos = Campo::orderBy('orden')->pluck('nombre')->toArray();
            return;
        }

        // Solo mostrar campos que tengan campaña con esa descripción
        $this->listaCampos = CampoCampania::where('nombre_campania', $this->filtroCampania)
            ->orderBy('campo')
            ->pluck('campo')
            ->toArray();
    }

    public function ordenarPor(string $campo): void
    {
        if ($this->ordenCampo === $campo) {
            $this->ordenDireccion = $this->ordenDireccion === 'asc' ? 'desc' : 'asc';
        } else {
            $this->ordenCampo = $campo;
            $this->ordenDireccion = 'asc';
        }
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->filtroCampania = '';
        $this->filtroCampo = '';
        $this->filtroTipo = '';
        $this->filtroMetodo = '';
        $this->filtroCampoOrigen = '';
        $this->filtroFechaDesde = '';
        $this->filtroFechaHasta = '';
        $this->ordenCampo = 'fecha';
        $this->ordenDireccion = 'desc';
        $this->listaCampos = Campo::orderBy('orden')->pluck('nombre')->toArray();
        $this->resetPage();
    }
    public function exportarReporte()
    {
        try {
            $spreadsheet = ExcelHelper::cargarPlantilla('rpt_tmpl_reporte_infestaciones.xlsx');
            $hoja = $spreadsheet->getSheetByName('INFESTACIONES');

            if (!$hoja) {
                throw new Exception("La plantilla no contiene la hoja 'INFESTACIONES'.");
            }

            $table = $hoja->getTableByName('tblInfestaciones');
            if (!$table) {
                throw new Exception("La plantilla no tiene una tabla llamada 'tblInfestaciones'.");
            }

            // ── 1. Escribir filtros en las celdas de encabezado (filas 2–5) ──────
            $ordenLabel = match ($this->ordenCampo) {
                'tipo_infestacion' => 'Tipo',
                'numero_envases' => 'N° Envases',
                'capacidad_envase' => 'Und × Envase',
                default => 'Fecha',
            };
            $ordenLabel .= ' (' . strtoupper($this->ordenDireccion) . ')';

            $hoja->setCellValue('B2', $this->filtroCampania ?: 'Todas');
            $hoja->setCellValue('B3', $this->filtroTipo ? ucfirst($this->filtroTipo) : 'Todas');
            $hoja->setCellValue('B4', $this->filtroFechaDesde ?: '-');
            $hoja->setCellValue('B5', $this->filtroFechaHasta ?: '-');

            $hoja->setCellValue('E2', $this->filtroCampo ?: 'Todos');
            $hoja->setCellValue('E3', $this->filtroCampoOrigen ?: 'Todos');
            $hoja->setCellValue('E4', $this->filtroMetodo ? ucfirst($this->filtroMetodo) : 'Todos');
            $hoja->setCellValue('E5', $ordenLabel);

            // ── 2. Obtener datos con los filtros activos ──────────────────────────
            $datos = $this->getQuery()->get()->map(function ($i) {
                return [
                    'tipo_infestacion' => $i->tipo_infestacion ? strtoupper($i->tipo_infestacion) : '',
                    'fecha' => $i->fecha,
                    'campo_nombre' => $i->campo_nombre ?? '',
                    'area' => $i->area ?? '',
                    'campania' => $i->campoCampania?->nombre_campania ?? '',
                    'kg_madres' => $i->kg_madres ?? '',
                    'campo_origen_nombre' => $i->campo_origen_nombre ?? '',
                    'metodo' => $i->metodo ? strtoupper($i->metodo) : '',
                    'capacidad_envase' => $i->capacidad_envase ?? '',
                    'numero_envases' => $i->numero_envases ?? '',
                ];
            })->toArray();

            // ── 3. Determinar fila de inicio de datos ─────────────────────────────
            $rangoOriginal = $table->getRange();          // e.g. "A7:N8"
            [$celdaInicio] = explode(':', $rangoOriginal); // "A7"
            $colInicio = preg_replace('/[0-9]/', '', $celdaInicio);  // "A"
            $filaEncabezado = (int) filter_var($celdaInicio, FILTER_SANITIZE_NUMBER_INT); // 7
            $filaInicio = $filaEncabezado + 1; // 8

            // ── 4. Escribir filas de datos ────────────────────────────────────────
            $filaActual = $filaInicio;

            foreach ($datos as $fila) {
                // A: TIPO

                $hoja->setCellValue("A{$filaActual}", $fila['tipo_infestacion']);

                // B: FECHA INFESTACION (serial Excel)
                if (!empty($fila['fecha'])) {
                    $hoja->setCellValue("B{$filaActual}", ExcelDate::PHPToExcel($fila['fecha']));
                    $hoja->getStyle("B{$filaActual}")
                        ->getNumberFormat()
                        ->setFormatCode('dd/mm/yyyy');
                }

                // C: CAMPO
                $hoja->setCellValue("C{$filaActual}", $fila['campo_nombre']);

                // D: AREA
                $hoja->setCellValue("D{$filaActual}", $fila['area']);

                // E: CAMPAÑA
                $hoja->setCellValue("E{$filaActual}", $fila['campania']);

                // F: KG MADRES
                $hoja->setCellValue("F{$filaActual}", $fila['kg_madres']);

                // G: KG MADRES/HA
                $hoja->setCellValue("G{$filaActual}", "=IFERROR(F{$filaActual}/D{$filaActual},\"\")");

                // H: ORIGEN CAMPO
                $hoja->setCellValue("H{$filaActual}", $fila['campo_origen_nombre']);

                // I: METODO
                $hoja->setCellValue("I{$filaActual}", $fila['metodo']);

                // J: UND X ENVASE
                $hoja->setCellValue("J{$filaActual}", $fila['capacidad_envase']);

                // K: ENVASES
                $hoja->setCellValue("K{$filaActual}", $fila['numero_envases']);

                // L: INFESTADORES
                $hoja->setCellValue("L{$filaActual}", "=IFERROR(J{$filaActual}*K{$filaActual},\"\")");

                // M: MADRES/INFES
                $hoja->setCellValue("M{$filaActual}", "=IFERROR(F{$filaActual}/L{$filaActual},\"\")");

                // N: INFESTADORES/HA
                $hoja->setCellValue("N{$filaActual}", "=IFERROR(L{$filaActual}/D{$filaActual},\"\")");

                $filaActual++;
            }

            // ── 5. Actualizar rango de la tabla ───────────────────────────────────
            $filaFin = max($filaActual - 1, $filaEncabezado + 1); // mínimo 1 fila de datos
            //dd($colInicio, $filaEncabezado, $filaFin);
            $table->setRange("{$colInicio}{$filaEncabezado}:N{$filaFin}");

            // ── 7. Descargar ──────────────────────────────────────────────────────
            return ExcelHelper::descargar($spreadsheet, 'REPORTE_INFESTACIONES.xlsx');

        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }


    public function registrarInfestacion(): void
    {
        // El padre vinculará este dispatch al módulo de registro masivo
        $this->dispatch('abrirRegistroInfestacion');
    }

    private function getQuery()
    {
        $query = CochinillaInfestacion::query()
            ->with(['campoCampania'])
            ->when($this->filtroTipo, fn($q) => $q->where('tipo_infestacion', $this->filtroTipo))
            ->when($this->filtroCampo, fn($q) => $q->where('campo_nombre', $this->filtroCampo))
            ->when($this->filtroCampoOrigen, fn($q) => $q->where('campo_origen_nombre', $this->filtroCampoOrigen))
            ->when($this->filtroMetodo, fn($q) => $q->where('metodo', $this->filtroMetodo))
            ->when($this->filtroFechaDesde, fn($q) => $q->whereDate('fecha', '>=', $this->filtroFechaDesde))
            ->when($this->filtroFechaHasta, fn($q) => $q->whereDate('fecha', '<=', $this->filtroFechaHasta))
            ->when($this->filtroCampania, function ($q) {
                // Filtrar por descripción de campaña a través de la relación
    
                $q->whereHas('campoCampania', fn($r) => $r->where('nombre_campania', $this->filtroCampania));
            });

        $columnasOrden = [
            'fecha' => 'fecha',
            'tipo_infestacion' => 'tipo_infestacion',
            'capacidad_envase' => 'capacidad_envase',
            'numero_envases' => 'numero_envases',
        ];

        $columna = $columnasOrden[$this->ordenCampo] ?? 'fecha';
        $query->orderBy($columna, $this->ordenDireccion);

        return $query;
    }
    public function verAuditoria(int $id): void
    {
        $this->auditoriaModeloId = $id;
        $this->auditoriaHistorial = Auditoria::where('modelo', CochinillaInfestacion::class)
            ->where('modelo_id', $id)
            ->orderByDesc('fecha_accion')
            ->get()
            ->toArray();

        $this->modalAuditoria = true;
    }
    public function render()
    {
        try {
            $infestaciones = $this->getQuery()->paginate(20);
            $total = $this->getQuery()->count();
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
            $infestaciones = collect();
            $total = 0;
        }

        return view('livewire.gestion-cochinilla.cochinilla-infestacion-lista-component', [
            'infestaciones' => $infestaciones,
            'total' => $total,
        ]);
    }
}