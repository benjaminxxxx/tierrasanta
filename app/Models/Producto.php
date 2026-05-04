<?php

namespace App\Models;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nombre_comercial',
        'ingrediente_activo',
        'categoria_codigo',
        'subcategoria_id',
        'codigo_tipo_existencia',
        'codigo_unidad_medida',
        'creado_por',
        'editado_por',
        'eliminado_por',
    ];
    //deprecado kardexProductos
    // -----------------------
    // RELACIONES AUDITORÍA
    // -----------------------

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
    public function usos()
    {
        return $this->belongsToMany(InsUso::class, 'ins_producto_usos', 'producto_id', 'uso_id');
    }
    public function categoria()
    {
        return $this->belongsTo(InsCategoria::class, 'categoria_codigo', 'codigo');
    }
    public function subcategoria()
    {
        return $this->belongsTo(InsSubcategoria::class, 'subcategoria_id', 'id');
    }
    public function kardexActual()
    {
        return $this->hasOne(InsKardex::class, 'producto_id', 'id')
            ->where('anio', now()->year);
    }
    public function nutrientes()
    {
        return $this->belongsToMany(Nutriente::class, 'producto_nutrientes', 'producto_id', 'nutriente_codigo')
            ->withPivot('porcentaje');
    }
    public function getCompraActivaAttribute()
    {
        return $this->compras()->where('estado', 1)->exists();
    }
    public function getUnidadMedidaAttribute()
    {
        return $this->tabla6 ? $this->tabla6->alias : '-';
    }
    public function getTipoExistenciaAttribute()
    {
        return $this->tabla5 ? $this->tabla5->descripcion : '-';
    }
    public function getTabla6DetalleAttribute()
    {
        $tabla6 = $this->tabla6;

        return $tabla6
            ? "{$tabla6->codigo} - {$tabla6->descripcion}"
            : "-";
    }
    public function getNombreCompletoAttribute()
    {
        $nombreComercial = trim($this->nombre_comercial);
        $ingredienteActivo = trim($this->ingrediente_activo);

        return $ingredienteActivo
            ? "{$nombreComercial} - {$ingredienteActivo}"
            : $nombreComercial;
    }
    // Reemplazar o modificar nombre_completo
    public function getNombreCompletoKgAttribute(): string
    {
        $sub = $this->ingrediente_activo
            ? '<br><span class="text-xs text-muted-foreground font-normal">' . $this->ingrediente_activo . '</span>'
            : '';

        return $this->nombre_comercial . " (" . $this->unidad_medida . ") " . $sub;
    }

    // Agregar accessor de usos
    public function getListaUsosAttribute(): string
    {
        return $this->usos->pluck('nombre')->implode(', ') ?: '—';
    }

    // Agregar accessor kardex año actual
    public function getTieneKardexActualAttribute(): bool
    {
        return $this->kardexes()
            ->whereYear('created_at', now()->year)
            ->exists();
    }
    public function getStockDisponibleAttribute()
    {
        //Verificar si hay kardex con el producto
        $fecha = Carbon::now();
        $productoId = $this->id;
        $kardex = Kardex::whereHas('productos', function ($query) use ($productoId) {
            $query->where('producto_id', $productoId);
        })
            ->where('fecha_inicial', '<=', $fecha)
            ->where('fecha_final', '>=', $fecha)
            ->where('estado', 'activo')
            ->where('eliminado', false)
            ->first();

        if (!$kardex) {
            return [
                'stock_disponible_porcentaje' => 0, // Redondeado y convertido a entero
                'stock_disponible' => 0
            ];
        }

        $totalStock = 0;
        $cantidadUsada = 0;
        $kardexProductos = $kardex->productos()->where('producto_id', $productoId)->get();
        foreach ($kardexProductos as $kardexProducto) {
            $totalStock += $kardexProducto->stockDisponible['total_stock'];
            $cantidadUsada += $kardexProducto->stockDisponible['cantidad_usada'];
        }

        $stockDisponible = $totalStock - $cantidadUsada;
        $porcentaje = $totalStock > 0 ? ($stockDisponible / $totalStock) * 100 : 0;

        return [
            'stock_disponible_porcentaje' => (int) round($porcentaje), // Redondeado y convertido a entero
            'stock_disponible' => round($stockDisponible, 3) // Redondeado a 2 decimales
        ];
    }




    public function tabla5()
    {
        return $this->belongsTo(SunatTabla5TipoExistencia::class, 'codigo_tipo_existencia');
    }
    public function tabla6()
    {
        return $this->belongsTo(SunatTabla6CodigoUnidadMedida::class, 'codigo_unidad_medida');
    }
    public function compras()
    {
        return $this->hasMany(CompraProducto::class);
    }

    public static function buscarCombustible(string $nombre)
    {
        return self::where('categoria_codigo', 'combustible')
            ->where('nombre_comercial', 'like', "%$nombre%")
            ->get();
    }
    /**
     * Verifica si un producto pertenece a la categoría "Combustible".
     *
     * @param int $productoId
     * @return bool
     */


    public static function esCombustible(int $productoId): bool
    {
        // Obtener el producto y verificar si su categoría es "Combustible"
        return self::where('id', $productoId)
            ->where('categoria_codigo', 'combustible')
            ->exists();
    }

    public static function deTipo($tipo)
    {
        if ($tipo === 'combustible') {
            return self::where('categoria_codigo', 'combustible')->with('compras');
        } else {
            return self::where('categoria_codigo', '!=', 'combustible')->with('compras');
        }
    }
    public function getCategoriaConDescripcionAttribute()
    {
        $categoria = $this->categoria?->descripcion;
        $subcategoria = $this->subcategoria?->nombre;

        if ($subcategoria) {
            return mb_strtoupper($categoria . ' - ' . $subcategoria);
        }

        return mb_strtoupper($categoria);
    }
    public function getListaNutrientesAttribute()
    {
        if ($this->nutrientes->isEmpty()) {
            return '-';
        }

        $html = '<ul class="list-disc list-inside">';
        foreach ($this->nutrientes as $nutriente) {
            if ($nutriente->pivot->porcentaje !== null && $nutriente->pivot->porcentaje != 0) {
                $html .= '<li>' . e($nutriente->codigo) . ': ' . e($nutriente->pivot->porcentaje) . '%</li>';
            }
        }
        $html .= '</ul>';

        return $html;
    }


}
