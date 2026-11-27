<?php

namespace App\Livewire\Almacen;

use App\Models\AlmacenProductoSalida;
use App\Models\Campo;
use App\Models\InsCategoria;
use App\Models\Maquinaria;
use App\Models\Producto;
use App\Services\Almacen\AlmacenServicio;
use App\Services\Reporte\AuditoriaServicio;
use App\Traits\HandlesAlerts;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class AlmacenSalidaListaComponent extends Component
{
    use LivewireAlert, WithPagination, WithoutUrlPagination, HandlesAlerts, \App\Livewire\Traits\ConPreguntaRegenerarKardex;

    public $anio;
    public $mes;
    public string $tipo;

    public array $filtros = [
        'dia' => '',
        'productoId' => '',
        'destinoId' => '',
        'categoria' => '',
        'tipoKardex' => '', // blanco | negro | '' (todos)
    ];

    public bool $modalAuditoriaSalida = false;
    public array $auditoriaHistorialSalida = [];

    public array $listaProductos = [];
    public array $listaMaquinarias = [];
    public array $listaCampos = [];
    public array $registros = [];
    public array $listaGruposOperativos = [];

    protected $listeners = [
        'salidaGuardada' => 'alSalidaGuardada',
    ];

    public function mount($mes = null, $anio = null, string $tipo)
    {
        $this->mes = $mes;
        $this->anio = $anio;
        $this->tipo = $tipo;
        $this->cargarListas();
        $this->obtenerSalidasPaginadas();
        $this->notificarTabla();
        $this->listaGruposOperativos = InsCategoria::query()
            ->whereNotNull('grupo_operativo')
            ->where('grupo_operativo', '!=', '')
            ->select('grupo_operativo')
            ->distinct()
            ->orderBy('grupo_operativo')
            ->pluck('grupo_operativo')
            ->toArray();
    }

    public function updatingFiltros(): void
    {
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->filtros = ['dia' => '', 'productoId' => '', 'destinoId' => '', 'categoria' => '', 'tipoKardex' => ''];
        $this->resetPage();
    }

    // ────────────────────────────────────────────────────────────
    // Abrir el modal de formulario (crear o editar según haya selección)
    // ────────────────────────────────────────────────────────────
    public function abrirCrear(): void
    {
        $this->dispatch('abrirFormularioSalida', tipo: $this->tipo, mes: $this->mes, anio: $this->anio, ids: []);
    }

    /**
     * Abre el formulario de edición pasando una lista de IDs explícita (desde JS / Context Menu).
     */
    public function editarSalidas(array $ids): void
    {
        if (empty($ids)) {
            $this->alert('warning', 'No se enviaron registros válidos para editar.');
            return;
        }

        $this->dispatch(
            'abrirFormularioSalida',
            tipo: $this->tipo,
            mes: $this->mes,
            anio: $this->anio,
            ids: $ids
        );
    }

    /**
     * El formulario avisa con qué mes/año quedó el registro guardado. Si es otro periodo se salta a él (página 1);
     * si es el mismo, se queda en la página en que estaba (para seguir editando la misma fecha), salvo que ya no exista.
     */
    public function alSalidaGuardada($mes, $anio): void
    {
        if ((int) $mes !== (int) $this->mes || (int) $anio !== (int) $this->anio) {
            $this->mes = $mes;
            $this->anio = $anio;
            $this->resetPage();
            return;
        }
        $ultima = max(1, $this->obtenerSalidasPaginadas()->lastPage());
        if ($this->getPage() > $ultima) {
            $this->setPage($ultima);
            return;
        }
        $this->notificarTabla();
    }

    public function verHistorialSalida(int $id): void
    {
        $this->auditoriaHistorialSalida = AuditoriaServicio::getAuditoria(AlmacenProductoSalida::class, $id);
        $this->modalAuditoriaSalida = true;
    }
    /** Elimina las salidas con motivo obligatorio (queda en la auditoría con quién y cuándo). */
    public function eliminarSalidas(array $ids, ?string $motivo = null): void
    {
        try {
            if (mb_strlen(trim((string) $motivo)) < 5) {
                $this->errorAlert('Escribe el motivo de la eliminación (mínimo 5 letras): queda en la auditoría.');
                return;
            }
            $claves = \App\Models\AlmacenProductoSalida::whereIn('id', $ids)->get(['producto_id', 'fecha_reporte', 'tipo_kardex'])
                ->map(fn($s) => ['producto_id' => $s->producto_id, 'anio' => $s->fecha_reporte?->year, 'tipo' => $s->tipo_kardex])->all();
            AlmacenServicio::eliminarSalidasAgrupadas($ids, $motivo);
            $this->alert('success', count($ids) === 1 ? 'Salida eliminada.' : 'Salidas eliminadas correctamente.');
            $this->notificarTabla();
            $this->preguntarRegenerarKardex($claves);
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function cargarListas(): void
    {
        $this->listaCampos = Campo::get()->map(fn($p) => ['id' => $p->nombre, 'label' => $p->nombre])->toArray();
        $this->listaProductos = Producto::deTipo($this->tipo)->get()
            ->map(fn($p) => ['id' => $p->id, 'label' => $p->nombre_comercial])->toArray();

        if ($this->tipo === 'combustible') {
            $this->listaMaquinarias = Maquinaria::orderBy('nombre')->get()
                ->map(fn($m) => ['id' => $m->id, 'label' => $m->nombre])->toArray();
        }
    }
    public function updatedPage()
    {
        $this->notificarTabla();
    }
    /**
     * Obtiene los registros paginados y despacha los datos formateados a Handsontable
     */
    public function notificarTabla()
    {
        $salidas = $this->obtenerSalidasPaginadas();
        $dataFormatted = $this->formatearParaHandsontable($salidas);
        $this->registros = $dataFormatted;
        $this->dispatch('actualizarTabla', data: $dataFormatted);
    }
    private function obtenerSalidasPaginadas()
    {
        return AlmacenServicio::obtenerRegistrosPorFecha(
            $this->mes,
            $this->anio,
            $this->tipo,
            in_array($this->filtros['tipoKardex'] ?? '', ['blanco', 'negro'], true) ? $this->filtros['tipoKardex'] : null,
            $this->filtros      // Pass los filtros dinámicos
        );
    }
    public function updatedFiltros()
    {
        $this->resetPage();
    }
    private function formatearParaHandsontable($salidas): array
    {
        // Transforma la colección Eloquent/Paginador a una estructura plana de Array
        $items = method_exists($salidas, 'items') ? $salidas->items() : $salidas;

        return collect($items)->map(function ($item) {
            return [
                'id' => $item->id,
                'fecha_reporte' => $item->fecha_reporte ? \Carbon\Carbon::parse($item->fecha_reporte)->format('Y-m-d') : null,
                'producto' => $item->producto?->nombre_comercial ?? '',
                'unidad_medida' => $item->producto?->unidadMedida?->codigo ?? 'UND',
                'cantidad' => (float) $item->cantidad,
                'maquinaria_id' => $item->maquinaria?->nombre ?? '',
                'campo_nombre' => $item->campo_nombre ?? '',
                'tipo_kardex' => $item->tipo_kardex ?? '',
                // En el método formatearParaHandsontable:
                'categoria' => $item->producto?->categoria?->grupo_operativo ?? '',
                // Para qué se usó o qué daño se trató (queresa, hongo de penca, desalitrante…): se elige en el formulario
                'uso' => $item->uso?->nombre ?? '',
                'costo_por_kg' => (float) ($item->costo_por_kg ?? 0),
                'total_costo' => (float) ($item->total_costo ?? 0),
                'distribuciones_count' => (int) ($item->distribuciones_count ?? 0),
            ];
        })->toArray();
    }
    public function render()
    {
        return view('livewire.almacen.almacen-salida-lista-component', [
            'salidas' => $this->obtenerSalidasPaginadas(),
        ]);
    }
}