<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KardexProducto extends Model
{
    protected $fillable = [
        'kardex_id',         // Relación con el kardex principal
        'producto_id',       // Producto asociado
        'stock_inicial',     // Stock inicial al abrir el kardex
        'costo_unitario',    // Costo promedio inicial
        'costo_total',       // Costo total calculado
        'stock_final',       // Stock final al cerrar (nullable)
        'costo_final',       // Costo promedio final al cerrar (nullable)
        'estado',            // Estado del kardex del producto (activo o cerrado)
        'metodo_valuacion',  // Método de valuación (promedio o peps)
        'file',
        'codigo_existencia',
        'tipo_kardex'
    ];
    /**
     * Obtiene el Stock en Base al rango de fecha del Kardex sumando el stock inicial mas el stock comprado menos las salidas
     * @return array{stock_disponible: float, stock_disponible_porcentaje: int}
     * Fecha uso 20250203
     */
    public function getStockDisponibleAttribute()
    {
        $stockInicial = $this->stock_inicial;
        
        /*$compraStock = (float) $this->kardex->compras($this->producto_id)->where('tipo_kardex',$this->tipo_kardex)->sum('stock');
        $salidaCantidadUsada = (float) $this->kardex->salidas($this->producto_id)->where('tipo_kardex',$this->tipo_kardex)->sum('cantidad');
*/
        $compraStock = (float) $this->compras()->sum('stock');
        $salidaCantidadUsada = (float) $this->salidas()->sum('cantidad');

        $totalStock = $stockInicial + $compraStock;

        // Stock total = stock inicial + compras - salidas
        $stockDisponible = $totalStock - $salidaCantidadUsada;

        // Evitar división por cero
        $porcentaje = $totalStock > 0 ? ($stockDisponible / $totalStock) * 100 : 0;

        return [
            'stock_disponible_porcentaje' => (int) round($porcentaje), // Redondeado y convertido a entero
            'stock_disponible' => round($stockDisponible, 3), // Redondeado a 2 decimales
            'total_stock'=>$totalStock,
            'cantidad_usada'=>$salidaCantidadUsada,
        ];
    }
    public function compraSalidaStock()
    {
        return $this->hasMany(CompraSalidaStock::class, 'kardex_producto_id');
    }
    public function kardex()
    {
        return $this->belongsTo(Kardex::class, 'kardex_id');
    }
    public function salidasStockUsado()
    {
        return $this->hasMany(AlmacenProductoSalida::class, 'cantidad_kardex_producto_id');
    }
    public function compras()
    {
        $query = CompraProducto::where('producto_id', $this->producto_id)
            ->where('tipo_kardex',$this->tipo_kardex)
            ->whereDate('fecha_compra', '>=', $this->kardex->fecha_inicial)
            ->orderBy('fecha_compra');

        if ($this->kardex->fecha_final) {
            $query->whereDate('fecha_compra', '<=', $this->kardex->fecha_final);
        }
        return $query;
    }
    public function salidas()
    {
        $query = AlmacenProductoSalida::where('producto_id', $this->producto_id)
        ->where('tipo_kardex',$this->tipo_kardex)
            ->whereDate('fecha_reporte', '>=', $this->kardex->fecha_inicial)
            ->orderBy('fecha_reporte')
            ->orderBy('created_at', 'asc')
            ->orderByRaw('COALESCE(indice, 0) ASC');

        if ($this->kardex->fecha_final) {
            $query->whereDate('fecha_reporte', '<=', $this->kardex->fecha_final);
        }
        return $query;
    }
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
