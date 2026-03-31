<?php

namespace App\Livewire;

use App\Models\AlmacenProductoSalida;
use App\Models\Campo;
use App\Models\CompraProducto;
use App\Models\InsKardex;
use App\Models\Maquinaria;
use App\Models\Producto;
use App\Services\AlmacenServicio;
use App\Services\AuditoriaServicio;
use Carbon\Carbon;
use DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class AlmacenSalidaDetalleComponent extends Component
{
    use LivewireAlert;
    public $anio;
    public $mes;
    public $registros;
    public $mostrarGenerarItem = false;
    public $inicioItem;
    public $cantidad = [];
    public $tipo;
    public $filasModificadas = [];
    public array $listaProductos = [];
    public array $listaMaquinarias = [];
    public array $listaCampos = [];
    public array $stocksProductos = [];
    // ─── Auditoría ─────────────────────────────────────────────
    public bool $modalAuditoriaSalida = false;
    public array $auditoriaHistorialSalida = [];
    protected $listeners = ['actualizarAlmacen' => '$refresh', 'ActualizarProductos' => '$refresh', 'eliminacionConfirmar'];
    public function verHistorialSalida(int $id): void
    {
        $this->auditoriaHistorialSalida = AuditoriaServicio::getAuditoria(
            AlmacenProductoSalida::class,
            $id
        );
        $this->modalAuditoriaSalida = true;
    }

    public function eliminarSalida(int $id): void
    {
        try {
            $salida = AlmacenProductoSalida::findOrFail($id);

            AuditoriaServicio::registrar(
                modelo: AlmacenProductoSalida::class,
                modeloId: $salida->id,
                accion: 'eliminar',
                antes: $salida->toArray(),
                camposIgnorados: ['creado_por', 'editado_por', 'created_at', 'updated_at'],
            );

            $salida->delete();

            $this->cargarSalidaInsumos();
            $this->alert('success', 'Salida eliminada.');
        } catch (\Exception $e) {
            $this->alert('error', $e->getMessage());
        }
    }
    public function mount($mes = null, $anio = null, string $tipo)
    {
        $this->mes = $mes;
        $this->anio = $anio;
        $this->cargarSalidaInsumos(false);
        $this->tipo = $tipo;
        $this->cargarListas();
    }
    public function preguntarStock(int $productoId): void
    {
        // Si ya está cargado, no repetir
        if (isset($this->stocksProductos[$productoId]))
            return;

        $anio = $this->anio;

        $kardexBlanco = InsKardex::where('producto_id', $productoId)
            ->where('anio', $anio)
            ->where('tipo', 'blanco')
            ->first(['stock_actual', 'producto_id']);

        $kardexNegro = InsKardex::where('producto_id', $productoId)
            ->where('anio', $anio)
            ->where('tipo', 'negro')
            ->first(['stock_actual', 'producto_id']);

        $producto = Producto::find($productoId, ['id', 'nombre_comercial', 'codigo_unidad_medida']);

        $this->stocksProductos[$productoId] = [
            'producto_id' => $productoId,
            'nombre' => $producto?->nombre_comercial ?? "Producto {$productoId}",
            'unidad' => $producto?->unidad_medida ?? '',
            'blanco' => $kardexBlanco?->stock_actual ?? null,
            'negro' => $kardexNegro?->stock_actual ?? null,
        ];
    }
    public function actualizarStockInsumo(int $productoId): void
    {
        $anio = $this->anio;

        foreach (['blanco', 'negro'] as $tipo) {
            $kardex = InsKardex::where('producto_id', $productoId)
                ->where('anio', $anio)
                ->where('tipo', $tipo)
                ->first();

            if (!$kardex)
                continue;

            $compras = CompraProducto::where('producto_id', $productoId)
                ->where('tipo_kardex', $tipo)
                ->whereYear('fecha_compra', $anio)
                ->sum('stock');

            $salidas = AlmacenProductoSalida::where('producto_id', $productoId)
                ->where('tipo_kardex', $tipo)
                ->whereYear('fecha_reporte', $anio)
                ->sum('cantidad');

            $stockReal = max(0, $kardex->stock_inicial + $compras - $salidas);

            // DB::table para no disparar eventos Eloquent
            DB::table('ins_kardexes')
                ->where('id', $kardex->id)
                ->update(['stock_actual' => $stockReal]);

            // Actualizar el array en memoria para refrescar la vista
            if (isset($this->stocksProductos[$productoId])) {
                $this->stocksProductos[$productoId][$tipo] = $stockReal;
            }
        }
    }
    public function limpiarStocksHuerfanos(array $productoIdsActivos): void
    {
        $this->stocksProductos = collect($this->stocksProductos)
            ->filter(fn($s) => in_array($s['producto_id'], $productoIdsActivos))
            ->toArray();
    }
    public function cargarListas(): void
    {
        $this->listaCampos = Campo::get()
            ->map(fn($p) => ['id' => $p->nombre, 'label' => $p->nombre])
            ->toArray();
        $this->listaProductos = Producto::deTipo($this->tipo)
            ->get()
            ->map(fn($p) => ['id' => $p->id, 'label' => $p->nombre_comercial])
            ->toArray();

        if ($this->tipo === 'combustible') {
            $this->listaMaquinarias = Maquinaria::orderBy('nombre')
                ->get()
                ->map(fn($m) => ['id' => $m->id, 'label' => $m->nombre])
                ->toArray();
        }
    }
    /*
    public function confirmarEliminacionSalida($id)
    {
        $this->confirm('¿Está seguro que desea eliminar el registro?', [
            'onConfirmed' => 'eliminacionConfirmar',
            'data' => ['id' => $id],
        ]);
    }
    public function eliminacionConfirmar($data)
    {
        try {
            AlmacenServicio::eliminarRegistroSalida($data['id']);
            $this->alert('success', "Registro eliminado");
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function generarItemCodigoForm()
    {

        if ($this->registros) {
            $primerRegistro = $this->registros->first();
            if (!$primerRegistro) {
                return $this->alert("error", "Para generar la numeración, debe agregar registros");
            }

            $this->mostrarGenerarItem = true;

            $maximoItemAnterior = AlmacenProductoSalida::whereMonth('fecha_reporte', $this->mes - 1)
                ->whereYear('fecha_reporte', $this->anio)->max('item');

            if ($maximoItemAnterior) {
                $this->inicioItem = $maximoItemAnterior + 1;
            } else {
                $this->inicioItem = 1;
            }
        } else {
            return $this->alert("error", "Para generar la numeración, debe agregar registros");
        }

    }
    public function generarItemCodigo()
    {
        $this->procesarRegistros();
        $this->cerrarMostrarGenerarItem();
    }*/
    public function procesarRegistros()
    {

        if ($this->registros && $this->inicioItem) {
            $correlativo = $this->inicioItem;

            foreach ($this->registros as $registro) {
                if ($registro->cantidad) {
                    $registro->item = $correlativo;
                    $correlativo++;
                    $registro->save();


                } else {
                    $registro->item = null;
                    $registro->save();
                }
            }
        }
    }

    public function cerrarMostrarGenerarItem()
    {
        $this->mostrarGenerarItem = false;
        $this->inicioItem = null;
    }
    public function cargarSalidaInsumos($dispatch = true)
    {
        $this->registros = AlmacenServicio::obtenerRegistrosPorFecha($this->mes, $this->anio, $this->tipo)
            ->map(function ($salida) {
                $distribuciones = $salida->distribuciones ?? [];
                return array_merge($salida->toArray(), [
                    'campo_nombre' => $this->tipo == 'combustible' ? $salida->maquina_nombre : $salida->campo_nombre,
                    'nombre_producto' => $salida->producto?->nombre_comercial,
                    'unidad_medida' => $salida->producto?->unidad_medida,
                    'categoria' => $salida->producto?->categoria?->descripcion,
                    'distribuciones_count' => count($distribuciones),
                ]);
            })
            ->toArray();
        if ($dispatch) {
            $this->dispatch('cargarDataSlidaAlmacen', data: $this->registros);
        }

    }
    public function guardarSalidaAlmacen(array $data)
    {
        try {
            $resultados = AlmacenServicio::guardarSalidaMasiva($data, $this->tipo);

            $partes = [];
            if ($resultados['creados'] > 0)
                $partes[] = "{$resultados['creados']} creados";
            if ($resultados['actualizados'] > 0)
                $partes[] = "{$resultados['actualizados']} actualizados";
            if ($resultados['eliminados'] > 0)
                $partes[] = "{$resultados['eliminados']} eliminados";

            $this->alert('success', count($partes) ? implode(', ', $partes) : 'Sin cambios');
            $this->filasModificadas = [];
            $this->cargarSalidaInsumos(); // refresca la tabla
        } catch (\Exception $e) {
            $this->alert('error', $e->getMessage());
        }
    }
    public function render()
    {
        return view('livewire.almacen-salida-detalle-component');
    }
}
