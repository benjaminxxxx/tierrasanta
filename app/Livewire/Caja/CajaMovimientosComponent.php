<?php

namespace App\Livewire\Caja;

use App\Constants\Permisos;
use App\Models\CajaArqueo;
use App\Models\CajaFuente;
use App\Services\Caja\Arqueo\CajaArqueoCrud;
use App\Services\Caja\Cierre\CajaCierreConsulta;
use App\Services\Caja\Cierre\CajaCierreProceso;
use App\Services\Caja\Importacion\CajaImportacionExcel;
use App\Services\Caja\Movimiento\CajaMovimientoConsulta;
use App\Services\Caja\Movimiento\CajaMovimientoCrud;
use App\Services\Caja\Reporte\CajaReporteExcel;
use App\Traits\HandlesAlerts;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Movimientos de caja: tabla de solo lectura (Handsontable) con filtros, totales, arqueos y cierre del mes.
 * Los cambios se hacen en modales y solo con el permiso de gestionar.
 */
#[Title('Caja')]
class CajaMovimientosComponent extends Component
{
    use LivewireAlert, HandlesAlerts, WithFileUploads;

    #[Url] public $anio;
    #[Url] public $mes;
    #[Url] public $semana = '';
    #[Url] public $tipo = '';
    #[Url] public $condicion = '';
    #[Url] public $contable = '';
    #[Url] public $clasificador1 = '';
    #[Url] public $clasificador2 = '';
    #[Url] public $subgrupo = '';
    #[Url] public $buscar = '';

    // Color personalizado
    public bool $modalColor = false;
    public array $colorIds = [];
    public ?string $colorFondo = null;
    public ?string $colorTexto = null;
    public bool $colorNegrita = false;

    // Eliminar
    public bool $modalEliminar = false;
    public ?int $eliminarId = null;
    public string $eliminarResumen = '';
    public string $motivoEliminacion = '';

    // Historial de una fila
    public bool $modalHistorial = false;
    public array $historial = [];

    // Cierre
    public bool $modalCerrar = false;
    public bool $modalReabrir = false;
    public string $motivoReapertura = '';
    public string $observacionCierre = '';

    // Arqueo
    public bool $modalArqueo = false;
    public ?int $arqueoId = null;
    public string $arqueoFecha = '';
    public array $arqueoMontos = [];
    public string $arqueoObservacion = '';
    public string $nuevaFuente = '';

    // Importación
    public bool $modalImportar = false;
    public $archivoImportar;
    public string $modoImportacion = CajaImportacionExcel::MODO_DIFERENCIA;

    private ?array $datosCache = null;

    public function mount(): void
    {
        $this->anio = (int) ($this->anio ?: now()->year);
        $this->mes = $this->mes === '0' || $this->mes === 0 ? '' : ($this->mes ?: now()->month);
    }

    public function getPuedeGestionarProperty(): bool
    {
        return auth()->user()?->can(Permisos::CAJA_MOVIMIENTO_GESTIONAR) ?? false;
    }

    private function filtros(): array
    {
        return [
            'anio' => (int) $this->anio,
            'mes' => $this->mes ? (int) $this->mes : null,
            'semana' => $this->semana,
            'tipo' => $this->tipo,
            'condicion' => $this->condicion,
            'contable' => $this->contable,
            'clasificador_1' => $this->clasificador1,
            'clasificador_2' => $this->clasificador2,
            'subgrupo' => $this->subgrupo,
            'buscar' => $this->buscar,
        ];
    }

    private function datos(): array
    {
        return $this->datosCache ??= app(CajaMovimientoConsulta::class)->listar($this->filtros());
    }

    /** Cualquier filtro cambia la tabla. */
    public function updated($propiedad): void
    {
        if (in_array($propiedad, ['anio', 'mes', 'semana', 'tipo', 'condicion', 'contable', 'clasificador1', 'clasificador2', 'subgrupo', 'buscar'], true)) {
            if ($propiedad === 'clasificador1') {
                $this->clasificador2 = '';
            }
            $this->refrescarTabla();
        }
    }

    /** Aviso de meses sin cerrar: cambia de mes en la misma pantalla (sin navegar). */
    public function irAMes(int $anio, int $mes): void
    {
        $this->anio = $anio;
        $this->mes = $mes;
        $this->refrescarTabla();
    }

    public function limpiarFiltros(): void
    {
        $this->reset(['semana', 'tipo', 'condicion', 'contable', 'clasificador1', 'clasificador2', 'subgrupo', 'buscar']);
        $this->refrescarTabla();
    }

    #[On('cajaMovimientoGuardado')]
    public function refrescarTabla(): void
    {
        $this->datosCache = null;
        $this->dispatch('cajaFilas', filas: $this->datos()['filas'], editable: $this->puedeGestionar && $this->mesEditable());
    }

    /** Las acciones que modifican caja solo con el permiso de gestionar (además de ocultar los botones). */
    private function soloGestion(): void
    {
        abort_unless($this->puedeGestionar, 403, 'No tienes permiso para modificar la caja.');
    }

    private function mesEditable(): bool
    {
        return $this->mes && !app(CajaCierreConsulta::class)->estaCerrado((int) $this->anio, (int) $this->mes);
    }

    // ------------------------------------------------------------------ colores

    public function abrirColor(array $ids): void
    {
        $this->soloGestion();
        $this->colorIds = array_map('intval', $ids);
        $this->reset(['colorFondo', 'colorTexto', 'colorNegrita']);
        $this->modalColor = true;
    }

    public function guardarColor(): void
    {
        $this->soloGestion();
        try {
            $n = app(CajaMovimientoCrud::class)->asignarColor($this->colorIds, $this->colorFondo, $this->colorTexto, $this->colorNegrita ?: null);
            $this->modalColor = false;
            $this->alert('success', "Color aplicado a {$n} fila(s).");
            $this->refrescarTabla();
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function quitarColor(array $ids): void
    {
        $this->soloGestion();
        try {
            $n = app(CajaMovimientoCrud::class)->asignarColor(array_map('intval', $ids), null, null, null);
            $this->alert('success', "{$n} fila(s) vuelven al color de su clasificador.");
            $this->refrescarTabla();
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    // ------------------------------------------------------------------ eliminar

    public function confirmarEliminar(int $id): void
    {
        $this->soloGestion();
        $m = \App\Models\CajaMovimiento::findOrFail($id);
        $this->eliminarId = $id;
        $this->eliminarResumen = $m->fecha->format('d/m/Y') . ' · ' . ($m->beneficiario ?: '—') . ' · ' . $m->descripcion
            . ' · S/ ' . number_format((float) $m->importe, 2);
        $this->motivoEliminacion = '';
        $this->resetErrorBag();
        $this->modalEliminar = true;
    }

    public function eliminar(): void
    {
        $this->soloGestion();
        try {
            app(CajaMovimientoCrud::class)->eliminar($this->eliminarId, $this->motivoEliminacion);
            $this->modalEliminar = false;
            $this->alert('success', 'Movimiento eliminado.');
            $this->refrescarTabla();
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    // ------------------------------------------------------------------ historial

    /** Quién registró, editó o eliminó la fila y qué cambió (también para quien solo puede ver). */
    public function verHistorial(int $id): void
    {
        $this->historial = app(\App\Services\Caja\Historial\CajaHistorialConsulta::class)->deMovimiento($id);
        $this->modalHistorial = true;
    }

    // ------------------------------------------------------------------ cierre

    public function cerrarMes(): void
    {
        $this->soloGestion();
        try {
            $cierre = app(CajaCierreProceso::class)->cerrar((int) $this->anio, (int) $this->mes, $this->observacionCierre);
            $this->reset(['modalCerrar', 'observacionCierre']);
            $this->successAlert('Caja cerrada. Saldo final: S/ ' . number_format((float) $cierre->saldo_final, 2) . '.');
            $this->refrescarTabla();
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function reabrirMes(): void
    {
        $this->soloGestion();
        try {
            app(CajaCierreProceso::class)->reabrir((int) $this->anio, (int) $this->mes, $this->motivoReapertura);
            $this->reset(['modalReabrir', 'motivoReapertura']);
            $this->alert('success', 'Mes reabierto. Recuerda cerrarlo cuando termines la corrección.');
            $this->refrescarTabla();
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    // ------------------------------------------------------------------ arqueos

    public function abrirArqueo(?int $id = null): void
    {
        $this->soloGestion();
        $this->reset(['arqueoId', 'arqueoMontos', 'arqueoObservacion', 'nuevaFuente']);
        if ($id) {
            $arqueo = CajaArqueo::with('detalles')->findOrFail($id);
            $this->arqueoId = $id;
            $this->arqueoFecha = $arqueo->fecha->toDateString();
            $this->arqueoObservacion = (string) $arqueo->observacion;
            $this->arqueoMontos = $arqueo->detalles->mapWithKeys(fn($d) => [$d->caja_fuente_id => (string) $d->monto])->all();
        } else {
            [, $hasta] = app(CajaMovimientoConsulta::class)->rango($this->filtros());
            $this->arqueoFecha = min(now()->toDateString(), $hasta);
        }
        $this->modalArqueo = true;
    }

    public function agregarFuente(): void
    {
        $this->soloGestion();
        $nombre = trim($this->nuevaFuente);
        if ($nombre === '') {
            return;
        }
        CajaFuente::firstOrCreate(['nombre' => $nombre], ['orden' => (int) CajaFuente::max('orden') + 1]);
        $this->nuevaFuente = '';
    }

    public function guardarArqueo(): void
    {
        $this->soloGestion();
        try {
            app(CajaArqueoCrud::class)->guardar($this->arqueoId, $this->arqueoFecha, $this->arqueoMontos, $this->arqueoObservacion);
            $this->modalArqueo = false;
            $this->alert('success', 'Arqueo guardado.');
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function eliminarArqueo(int $id): void
    {
        $this->soloGestion();
        try {
            app(CajaArqueoCrud::class)->eliminar($id);
            $this->alert('success', 'Arqueo eliminado.');
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    // ------------------------------------------------------------------ Excel

    public function exportar(string $alcance)
    {
        try {
            $archivo = app(CajaReporteExcel::class)->generar((int) $this->anio, $alcance === 'mes' && $this->mes ? (int) $this->mes : null);
            return response()->download($archivo['ruta'], $archivo['nombre'])->deleteFileAfterSend();
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function importar(): void
    {
        $this->soloGestion();
        $this->validate(['archivoImportar' => 'required|file|extensions:xlsx,xlsm|max:102400'], [
            'archivoImportar.required' => 'Selecciona el Excel de caja.',
            'archivoImportar.extensions' => 'El archivo debe ser .xlsx o .xlsm.',
        ]);
        try {
            $r = app(CajaImportacionExcel::class)->importar($this->archivoImportar->getRealPath(), $this->modoImportacion);
            $this->reset(['modalImportar', 'archivoImportar', 'modoImportacion']);
            $mensaje = "Excel del {$r['desde']} al {$r['hasta']}: se agregaron {$r['movimientos']} movimiento(s) y {$r['arqueos']} arqueo(s).";
            if ($r['modo'] === CajaImportacionExcel::MODO_DIFERENCIA) {
                $mensaje .= " {$r['existentes']} ya estaban en el sistema y no se tocaron.";
                if ($r['solo_sistema']) {
                    $mensaje .= " {$r['solo_sistema']} están en el sistema pero no en el Excel (registradas a mano o corregidas en uno de los dos): revísalas.";
                }
            }
            if ($r['meses_omitidos']) {
                $mensaje .= ' Meses cerrados, omitidos: ' . implode(', ', $r['meses_omitidos']) . '.';
            }
            $mensaje .= ' Disponible final: S/ ' . number_format($r['saldo_final'], 2) . '.';
            if ($r['saldo_final_excel'] !== null && abs($r['saldo_final'] - $r['saldo_final_excel']) >= 0.01) {
                $mensaje .= ' El Excel muestra S/ ' . number_format($r['saldo_final_excel'], 2)
                    . ': revisa su columna DISPONIBLE (alguna fórmula se salta filas).';
            }
            $this->successAlert($mensaje . ($r['avisos'] ? ' ' . implode(' ', $r['avisos']) : ''));
            $this->refrescarTabla();
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function render()
    {
        $consulta = app(CajaMovimientoConsulta::class);
        $datos = $this->datos();
        $cierres = app(CajaCierreConsulta::class);

        return view('livewire.caja.caja-movimientos-component', [
            'datos' => $datos,
            'opciones' => $consulta->opciones(),
            'arqueos' => $consulta->arqueos($datos['desde'], $datos['hasta']),
            'cierre' => $this->mes ? $cierres->cierre((int) $this->anio, (int) $this->mes) : null,
            'mesEditable' => $this->mesEditable(),
            'mesesSinCerrar' => $cierres->mesesSinCerrar(),
            'fuentes' => CajaFuente::where('activo', true)->orderBy('orden')->get(),
            'nombreMes' => $this->mes ? Carbon::create((int) $this->anio, (int) $this->mes, 1)->locale('es')->translatedFormat('F Y') : (string) $this->anio,
        ]);
    }
}
