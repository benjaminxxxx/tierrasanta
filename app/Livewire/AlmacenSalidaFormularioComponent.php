<?php

namespace App\Livewire;

use App\Models\Almacen;
use App\Models\AlmacenProductoSalida;
use App\Models\Campo;
use App\Models\InsKardex;
use App\Models\InsUso;
use App\Models\Maquinaria;
use App\Models\Producto;
use App\Services\Almacen\StockService;
use App\Services\AlmacenServicio;
use DB;
use Exception;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class AlmacenSalidaFormularioComponent extends Component
{
    use LivewireAlert;

    public bool $mostrar = false;
    public string $tipo = 'productos';
    public $mes;
    public $anio;

    /** IDs recibidos al abrir. Vacío = modo creación. */
    public array $ids = [];

    public array $registros = [];
    public array $listaProductos = [];
    public array $listaMaquinarias = [];
    public array $listaCampos = [];

    /** producto_id => [{id, label}, ...] — opciones de USO permitidas por producto */
    public array $listaUsos = [];

    public array $stocksProductos = [];

    protected $listeners = [
        'abrirFormularioSalida' => 'abrir',
    ];
    public function mount()
    {

    }
    public function abrir(string $tipo, $mes, $anio, array $ids = []): void
    {
        $this->tipo = $tipo;
        $this->mes = $mes;
        $this->anio = $anio;
        $this->ids = $ids;
        $this->stocksProductos = [];

        $this->cargarListas();
        $this->cargarRegistros();

        $this->mostrar = true;
        $this->dispatch(
            'formularioSalidaAbierto',
            data: $this->registros,
            esEdicion: !empty($ids),
            listaProductos: $this->listaProductos,
            listaMaquinarias: $this->listaMaquinarias,
            listaCampos: $this->listaCampos,
            listaUsos: $this->listaUsos,
            tipo: $this->tipo
        );
    }

    public function cerrar(): void
    {
        $this->mostrar = false;
        $this->ids = [];
        $this->registros = [];
    }

    private function cargarRegistros(): void
    {
        if (empty($this->ids)) {
            // Modo creación: el grid arranca vacío, minSpareRows se encarga en el frontend.
            $this->registros = [];
            return;
        }

        $this->registros = AlmacenProductoSalida::with(['producto.categoria', 'distribuciones'])
            ->whereIn('id', $this->ids)
            // Mantiene el mismo orden en que fueron capturados originalmente.
            ->orderBy('fecha_reporte')
            ->orderBy('created_at')
            ->get()
            ->map(function ($salida) {
                $distribuciones = $salida->distribuciones ?? [];
                return array_merge($salida->toArray(), [
                    'campo_nombre' => $this->tipo === 'combustible' ? $salida->maquina_nombre : $salida->campo_nombre,
                    'unidad_medida' => $salida->producto?->codigo_unidad_medida,
                    'categoria' => $salida->producto?->categoria?->descripcion,
                    'distribuciones_count' => count($distribuciones),
                ]);
            })
            ->toArray();
    }

    private function cargarListas(): void
    {
        $this->listaCampos = Campo::get()
            ->map(fn($p) => ['id' => $p->nombre, 'label' => $p->nombre])
            ->toArray();

        $this->listaProductos = Producto::deTipo($this->tipo)->get()
            ->map(fn($p) => ['id' => $p->id, 'label' => $p->nombre_comercial])
            ->toArray();

        if ($this->tipo === 'combustible') {
            $this->listaMaquinarias = Maquinaria::orderBy('nombre')->get()
                ->map(fn($m) => ['id' => $m->id, 'label' => $m->nombre])
                ->toArray();
            $this->listaUsos = [];
            return;
        }


        // Un solo query para TODOS los productos de este tipo, agrupado por producto_id.
        // Evita el N+1 de preguntar usos fila por fila en el frontend.
        // La etiqueta combina nombre + descripción para que el autocomplete
        // permita buscar escribiendo cualquiera de los dos.
        $this->listaUsos = InsUso::get()
            ->map(fn($u) => [
                'id' => $u->id,
                'label' => $u->descripcion
                    ? "{$u->nombre} — {$u->descripcion}"
                    : $u->nombre,
            ])
            ->toArray();
    }
    /*
        public function preguntarStock(int $productoId): void
        {
            if (isset($this->stocksProductos[$productoId]))
                return;

            $kardexBlanco = InsKardex::where('producto_id', $productoId)
                ->where('anio', $this->anio)->where('tipo', 'blanco')
                ->first(['stock_actual']);

            $kardexNegro = InsKardex::where('producto_id', $productoId)
                ->where('anio', $this->anio)->where('tipo', 'negro')
                ->first(['stock_actual']);

            $producto = Producto::find($productoId, ['id', 'nombre_comercial', 'codigo_unidad_medida']);

            $this->stocksProductos[$productoId] = [
                'producto_id' => $productoId,
                'nombre' => $producto?->nombre_comercial ?? "Producto {$productoId}",
                'unidad' => $producto?->codigo_unidad_medida ?? '',
                'blanco' => $kardexBlanco?->stock_actual ?? null,
                'negro' => $kardexNegro?->stock_actual ?? null,
            ];
        }*/
    public function preguntarStock(int $productoId): void
    {
        if (isset($this->stocksProductos[$productoId]))
            return;

        $producto = Producto::find($productoId, ['id', 'nombre_comercial', 'codigo_unidad_medida']);
        $stock = StockService::obtenerStockPorTipo($productoId);

        $this->stocksProductos[$productoId] = [
            'producto_id' => $productoId,
            'nombre' => $producto?->nombre_comercial ?? "Producto {$productoId}",
            'unidad' => $producto?->unidad_medida ?? '',
            'blanco' => $stock['blanco'], // ya no es null-si-no-existe: 0 real es una respuesta válida
            'negro' => $stock['negro'],
        ];
    }

    public function limpiarStocksHuerfanos(array $productoIdsActivos): void
    {
        $this->stocksProductos = collect($this->stocksProductos)
            ->filter(fn($s) => in_array($s['producto_id'], $productoIdsActivos))
            ->toArray();
    }

    /**
     * Guarda todo el grid completo (crear + editar + eliminar-por-vaciado, tal
     * como ya lo resuelve guardarSalidaMasiva leyendo el campo 'id' de cada fila).
     */
    public function guardarSalida(array $data): void
    {
        try {
            $almacen = Almacen::first();
            if (!$almacen) {
                throw new Exception('No hay almacén configurado');
            }
            $resultados = app(AlmacenServicio::class)->guardarSalidaMasiva($data, $this->tipo, $almacen->id);

            $partes = [];
            if ($resultados['creados'] > 0)
                $partes[] = "{$resultados['creados']} creados";
            if ($resultados['actualizados'] > 0)
                $partes[] = "{$resultados['actualizados']} actualizados";
            if ($resultados['eliminados'] > 0)
                $partes[] = "{$resultados['eliminados']} eliminados";

            $this->alert('success', count($partes) ? implode(', ', $partes) : 'Sin cambios');

            // Toma la fecha de la primera fila con datos para saber a qué mes/año
            // saltar en la Lista — puede diferir del mes que se estaba viendo
            // (ej. edito en agosto un registro de enero).
            $primeraFecha = collect($data)->pluck('fecha_reporte')->filter()->first();
            [$mesDestino, $anioDestino] = $primeraFecha
                ? [(int) date('n', strtotime($primeraFecha)), (int) date('Y', strtotime($primeraFecha))]
                : [$this->mes, $this->anio];

            $this->dispatch('salidaGuardada', mes: $mesDestino, anio: $anioDestino);
            $this->cerrar();
        } catch (\Exception $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.almacen-salida-formulario-component');
    }
}