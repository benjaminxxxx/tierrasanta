<?php

namespace App\Livewire\GestionInsumos;

use App\Models\AlmacenProductoSalida;
use App\Models\CompraProducto;
use App\Models\InsKardex;
use App\Models\Producto;
use DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class InsumoKardexAsignacionComponent extends Component
{
    use LivewireAlert;
    public $productoNombre;
    public $kardexBlanco;
    public $kardexNegro;
    public $salidas = [];
    public $compras = [];
    public $stockInicialBlanco = 0;
    public $stockInicialNegro = 0;
    public function mount($productoId, $anio)
    {
        $producto = Producto::find($productoId);
        if ($producto) {
            $this->compras = CompraProducto::where('producto_id', $productoId)
                ->whereYear('fecha_compra', $anio)
                ->get()
                ->map(function ($compra) {
                    return [
                        'id' => $compra->id,
                        'producto_id' => $compra->producto_id,
                        'fecha' => $compra->fecha_compra,
                        'cantidad' => (float) $compra->stock,
                        'serie' => $compra->serie,
                        'numero' => $compra->numero,
                        'unidad_medida' => $compra->producto->unidad_medida,
                        'tipo_kardex' => $compra->tipo_kardex,
                        'costo_unitario' => (float) $compra->costo_por_kg,
                        'tipo' => 'entrada'
                    ];
                })->toArray();
            $this->salidas = AlmacenProductoSalida::with(['producto'])
                ->where('producto_id', $productoId)
                ->whereYear('fecha_reporte', $anio)
                ->orderBy('fecha_reporte', 'asc')
                ->get()
                ->map(function ($salida) {
                    return [
                        'id' => $salida->id,
                        'producto_id' => $salida->producto_id,
                        'campo' => $salida->campo_nombre,
                        'fecha' => $salida->fecha_reporte,
                        'cantidad' => (float) $salida->cantidad,
                        'unidad_medida' => $salida->producto->unidad_medida,
                        'tipo_kardex' => $salida->tipo_kardex,
                        'tipo' => 'salida'
                    ];
                })->toArray();

            $this->productoNombre = $producto->nombre_comercial;
            $this->kardexBlanco = InsKardex::where('producto_id', $productoId)
                ->where('anio', $anio)
                ->where('tipo', 'blanco')
                ->first();
            $this->kardexNegro = InsKardex::where('producto_id', $productoId)
                ->where('anio', $anio)
                ->where('tipo', 'negro')
                ->first();

            if ($this->kardexBlanco) {
                $this->stockInicialBlanco = (float) $this->kardexBlanco->stock_inicial;
            }
            if ($this->kardexNegro) {
                $this->stockInicialNegro = (float) $this->kardexNegro->stock_inicial;
            }
        }

    }
    public function confirmarAsignaciones(array $payload)
    {
        DB::beginTransaction();

        try {
            $productoId = null;
            $aniosAfectados = [];

            /** ============================
             * 1. SALIDAS (solo changes)
             * ============================ */
            foreach ($payload['salidas'] ?? [] as $cambio) {

                if (!isset($cambio['id'])) {
                    continue;
                }

                $salida = AlmacenProductoSalida::find($cambio['id']);
                if (!$salida) {
                    continue;
                }

                // 'blanco' | 'negro' | null
                $salida->tipo_kardex = $cambio['tipo_kardex'];
                $salida->save();

                // Capturar producto y años afectados
                $productoId = $salida->producto_id;
                $aniosAfectados[] = (int) date('Y', strtotime($salida->fecha_reporte));
            }

            /** ============================
             * 2. COMPRAS (comparación real)
             * ============================ */
            foreach ($payload['compras'] ?? [] as $data) {

                if (!isset($data['id'], $data['tipo_kardex'])) {
                    continue;
                }

                $compra = CompraProducto::find($data['id']);
                if (!$compra) {
                    continue;
                }

                // SOLO guardar si realmente cambió
                if ($compra->tipo_kardex !== $data['tipo_kardex']) {
                    $compra->tipo_kardex = $data['tipo_kardex'];
                    $compra->save();

                    $productoId = $compra->producto_id;
                    $aniosAfectados[] = (int) date('Y', strtotime($compra->fecha_compra));
                }
            }

            /** ============================
             * 3. Limpiar kardex afectados
             * ============================ */
            if ($productoId && !empty($aniosAfectados)) {
                $aniosAfectados = array_unique($aniosAfectados);

                $kardexAfectados = InsKardex::where('producto_id', $productoId)
                    ->whereIn('anio', $aniosAfectados)
                    ->get();

                foreach ($kardexAfectados as $kardex) {
                    // Nullear movimiento_id en salidas vinculadas a este kardex
                    // (las que aún no se tocaron en el paso 1)
                    AlmacenProductoSalida::whereHas(
                        'kardexMovimiento',
                        fn($q) =>
                        $q->where('kardex_id', $kardex->id)
                    )->update(['movimiento_id' => null]);

                    // Eliminar todos los movimientos del kardex
                    $kardex->movimientos()->delete();
                }
            }

            DB::commit();

            $this->alert(
                'success',
                'Asignaciones confirmadas exitosamente.'
            );

            $this->resetEstadoKardex();

        } catch (\Throwable $th) {

            DB::rollBack();
            report($th);

            $this->alert(
                'error',
                $th->getMessage()
            );
        }
    }


    protected function resetEstadoKardex()
    {
    }

    public function render()
    {
        return view('livewire.gestion-insumos.insumo-kardex-asignacion-component');
    }
}
