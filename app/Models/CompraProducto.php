<?php

namespace App\Models;

use Auth;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompraProducto extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'compra_productos';

    protected $fillable = [
        'producto_id',
        'tienda_comercial_id',
        'fecha_compra',
        'orden_compra',
        //'costo_por_kg',
        'total',
        'stock',
        'fecha_termino',
        //'estado',
        'tipo_compra_codigo',
        'serie',
        'numero',
        'tabla12_tipo_operacion',
        'tipo_kardex',
        'creado_por',
        'editado_por',
        'eliminado_por',
    ];
    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'editado_por');
    }

    public function eliminador()
    {
        return $this->belongsTo(User::class, 'eliminado_por');
    }
    protected $appends = [
        'costo_por_unidad',
    ];
    public function proveedor()
    {
        return $this->belongsTo(TiendaComercial::class, 'tienda_comercial_id');
    }
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->creado_por = Auth::id();
        });

        static::updating(function ($model) {
            $model->editado_por = Auth::id();
        });

        static::deleting(function ($model) {
            // Solo si es soft delete
            if (!$model->isForceDeleting()) {
                $model->eliminado_por = Auth::id();
                $model->save();
            }
        });
    }
    public function almacenSalida()
    {
        return $this->hasMany(CompraSalidaStock::class, 'compra_producto_id');
    }
    // Relación con Producto

    public function getCantidadDisponibleAttribute()
    {
        return (float) $this->stock - (float) $this->almacenSalida()->sum('stock');
    }
    public function getCostoPorUnidadAttribute()
    {
        return (float) $this->total / (float) $this->stock;
    }
    public function getCodigoComprobanteAttribute()
    {
        return "{$this->serie} - {$this->numero}";
    }


    // Relación con TiendaComercial


    public static function calcularCompras($mes, $anio, $tipoKardex, $esCombustible): float
    {
        // Calcular el rango de fechas para el mes y año dados
        $inicioMes = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
        $finMes = Carbon::createFromDate($anio, $mes, 1)->endOfMonth();

        // Construir la consulta
        $query = self::where('tipo_kardex', $tipoKardex)
            ->whereBetween('fecha_compra', [$inicioMes, $finMes])
            ->get();

        if ($esCombustible) {
            $query->filter(function ($compra) {
                return $compra->producto && $compra->producto->esCombustibleProducto();
            });
        }

        // Retornar la suma de los totales
        return $query->sum('total');
    }

}
