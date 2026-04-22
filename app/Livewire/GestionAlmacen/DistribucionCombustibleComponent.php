<?php

namespace App\Livewire\GestionAlmacen;

use App\Models\AlmacenProductoSalida;
use App\Models\DistribucionCombustible;
use App\Models\Maquinaria;
use App\Services\Almacen\DistribucionCombustibleServicio;
use App\Traits\ListasComunes\HstListas;
use App\Traits\Selectores\ConSelectorMes;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class DistribucionCombustibleComponent extends Component
{
    use HstListas, ConSelectorMes, LivewireAlert;

    public array  $listaCampos       = [];
    public array  $listaMaquinarias  = [];
    public array  $filas             = [];   // aplanado: salidas + distribuciones

    // Modal
    public bool   $modalDistribucion     = false;
    public ?int   $salidaActivaId        = null;
    public array  $distribucionesActivas = [];
    public array  $filasModificadasModal      = [];

    // Filtros
    public ?string $filtroMaquinariaId = null;
    public ?string $filtroCampo        = null;
    protected $listeners = ['confirmarEliminarDistribucion'];

    public function mount(): void
    {
        $this->inicializarMesAnio();
        $this->listaCampos      = $this->cargarListaHstCampos();
        $this->listaMaquinarias = $this->cargarListaHstMaquinarias();
        $this->generarDistribucion();
    }

    public function getMaquinarias($search)
    {
        $query = Maquinaria::orderBy('nombre');
        if ($search) {
            $query->where('nombre', 'like', "%{$search}%");
        }
        return $query->limit(10)->get(['id', 'nombre'])
            ->map(fn($c) => ['id' => $c->id, 'name' => $c->nombre])
            ->toArray();
    }

    protected function despuesMesAnioModificado($anio, $mes): void
    {
        $this->generarDistribucion();
    }

    public function updatedFiltroMaquinariaId(): void { $this->generarDistribucion(); }
    public function updatedFiltroCampo(): void        { $this->generarDistribucion(); }
    public function eliminarDistribucion(int $id): void
    {
        $this->confirm('¿Confirma que desea eliminar esta distribución?', [
            'onConfirmed' => 'confirmarEliminarDistribucion',
            'data' => ['id' => $id],
        ]);
    }
    public function confirmarEliminarDistribucion($data): void
    {
        try {
            $id = $data['id'] ?? null;
            DistribucionCombustible::findOrFail($id)->delete();
            $this->alert('success', 'Distribución eliminada');
            $this->generarDistribucion();
        } catch (\Exception $e) {
            $this->alert('error', 'Error al eliminar: ' . $e->getMessage());
        }
    }

    // ─── LISTA PRINCIPAL (salidas + distribuciones aplanadas) ───────────────

    public function generarDistribucion(): void
    {
        $query = AlmacenProductoSalida::with(['distribuciones.maquinaria', 'maquinaria', 'producto'])
            ->whereMonth('fecha_reporte', $this->mes)
            ->whereYear('fecha_reporte', $this->anio)
            ->whereHas('producto', fn($q) => $q->where('categoria_codigo', 'combustible'))
            ->where(fn($q) => $q->whereNull('campo_nombre')->orWhere('campo_nombre', ''));

        if ($this->filtroMaquinariaId) {
            $query->where('maquinaria_id', $this->filtroMaquinariaId);
        }

        $salidas = $query->orderBy('fecha_reporte')->get();

        $todasDistribuciones = DistribucionCombustible::whereIn(
            'almacen_producto_salida_id',
            $salidas->pluck('id')
        )->get()->groupBy('almacen_producto_salida_id');

        $filas = [];

        foreach ($salidas as $salida) {
            $distribuciones   = $todasDistribuciones[$salida->id] ?? collect();
            $totalHorasSalida = $distribuciones->sum(fn($d) => $d->horas);

            // ── Fila cabecera (salida) ──
            $filas[] = [
                'es_salida'         => true,
                'salida_id'         => $salida->id,
                'fecha'             => $salida->fecha_reporte,
                'maquinaria_id'     => $salida->maquinaria_id,
                'maquinaria_nombre' => $salida->maquinaria?->nombre ?? '—',
                'ingreso_salida'    => $salida->cantidad,
                'precio'            => $salida->costo_por_kg,
                'costo'             => $salida->total_costo,
                'n_distribuciones'  => $distribuciones->count(),
                'horas_total'       => $totalHorasSalida,
                // columnas de distribución vacías en cabecera
                'id'                => null,
                'hora_inicio'       => null,
                'hora_fin'          => null,
                'n_horas'           => null,
                'campo_nombre'      => null,
                'cant_combustible'  => null,
                'costo_combustible' => null,
                'labor_diaria'      => null,
                'ratio'             => null,
            ];

            // ── Filas hijas (distribuciones) ──
            foreach ($distribuciones as $dist) {
                if ($this->filtroCampo && $dist->campo !== $this->filtroCampo) continue;

                $ratio           = $totalHorasSalida > 0 ? $dist->horas / $totalHorasSalida : 0;
                $cantCombustible = $salida->cantidad * $ratio;
                $costoMaquinaria = $cantCombustible * ($salida->costo_por_kg ?? 0);
                $valorCosto      = $dist->horas > 0 ? $costoMaquinaria / $dist->horas : 0;

                $filas[] = [
                    'es_salida'         => false,
                    'salida_id'         => $salida->id,
                    'id'                => $dist->id,
                    'fecha'             => $dist->fecha,
                    'hora_inicio'       => $dist->hora_inicio,
                    'hora_fin'          => $dist->hora_salida,
                    'n_horas'           => $dist->horas,
                    'campo_nombre'      => $dist->campo,
                    'cant_combustible'  => $cantCombustible,
                    'costo_combustible' => $costoMaquinaria,
                    'ingreso_salida'    => null,
                    'labor_diaria'      => $dist->actividad,
                    'maquinaria_id'     => $dist->maquinaria_id,
                    'maquinaria_nombre' => $dist->maquinaria?->nombre ?? '—',
                    'precio'            => $salida->costo_por_kg,
                    'ratio'             => $ratio,
                    'costo'             => $valorCosto,
                ];
            }
        }

        $this->filas = $filas;
    }

    // ─── MODAL ──────────────────────────────────────────────────────────────

    public function abrirModalDistribucion(int $salidaId): void
    {
        $this->salidaActivaId   = $salidaId;
        $this->filasModificadasModal = [];

        $salida = AlmacenProductoSalida::with('distribuciones')->findOrFail($salidaId);
           
        $this->distribucionesActivas = $salida->distribuciones
            ->map(fn($d) => [
                'id'            => $d->id,
                'salida_id'     => $salidaId,
                'fecha'         => $d->fecha,
                'hora_inicio'   => $d->hora_inicio,
                'hora_fin'      => $d->hora_salida,
                'n_horas'       => $d->horas,
                'campo_nombre'  => $d->campo,
                'labor_diaria'  => $d->actividad,
                'maquinaria_id' => $d->maquinaria_id,
            ])
            ->toArray();
        $this->dispatch('cargarDistribuciones', distribuciones: $this->distribucionesActivas);
        $this->modalDistribucion = true;
    }

    public function guardarDistribuciones(array $data): void
    {
        try {
            $resultados = DistribucionCombustibleServicio::guardarDistribuciones(
                $data,
                $this->salidaActivaId
            );

            $partes = [];
            if ($resultados['creados'] > 0)      $partes[] = "{$resultados['creados']} creados";
            if ($resultados['actualizados'] > 0)  $partes[] = "{$resultados['actualizados']} actualizados";
            if ($resultados['eliminados'] > 0)    $partes[] = "{$resultados['eliminados']} eliminados";

            $this->alert('success', count($partes) ? implode(', ', $partes) : 'Sin cambios');
            $this->filasModificadasModal     = [];
            $this->modalDistribucion    = false;
            $this->salidaActivaId       = null;
            $this->generarDistribucion();
        } catch (\Exception $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.gestion-almacen.distribucion-combustible-component');
    }
}