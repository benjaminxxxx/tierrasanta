<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kardex extends Model
{
    protected $table = "kardex";
    protected $fillable = [
        'nombre',
        'tipo_kardex',
        'fecha_inicial',
        'fecha_final',
        'estado',
        'eliminado'
    ];
    public function productos()
    {
        return $this->hasMany(KardexProducto::class);
    }

    public function compras($productoId)
    {
        $query = CompraProducto::where('producto_id', $productoId)
            ->whereDate('fecha_compra', '>=', $this->fecha_inicial)
            ->orderBy('fecha_compra');

        if ($this->fecha_final) {
            $query->whereDate('fecha_compra', '<=', $this->fecha_final);
        }
        return $query;
    }
    public function salidas($productoId)
    {
        $query = AlmacenProductoSalida::where('producto_id', $productoId)
            ->whereDate('fecha_reporte', '>=', $this->fecha_inicial)
            ->orderBy('fecha_reporte')
            ->orderBy('created_at', 'asc')
            ->orderByRaw('COALESCE(indice, 0) ASC');

        if ($this->fecha_final) {
            $query->whereDate('fecha_reporte', '<=', $this->fecha_final);
        }
        return $query;
    }
}
