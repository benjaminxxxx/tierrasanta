<?php

namespace App\Models;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre_comercial',
        'ingrediente_activo',
        'categoria_codigo',
        'codigo_tipo_existencia',
        'codigo_unidad_medida',
        'categoria_pesticida'
    ];
    public function nutrientes()
    {
        return $this->belongsToMany(Nutriente::class, 'producto_nutrientes', 'producto_id', 'nutriente_codigo')
            ->withPivot('porcentaje');
    }
    public function categoriaPesticida()
    {
        return $this->belongsTo(CategoriaPesticida::class, 'categoria_pesticida');
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
    /**
     * Kardex Disponible se basa en la existencia de un kardex cuyo rango pertenece a la fecha de salida, para saberel stock disponible
     * @param mixed $fechaSalida
     * @throws \Exception
     */
    public function kardexesDisponibles($fechaSalida)
    {
        $productoId = $this->id;

        //este productos whereHas es porque en Kardex en vez de KardexProducto puse solo productos
        $kardex = Kardex::whereHas('productos', function ($query) use ($productoId) {
            $query->where('producto_id', $productoId);
        })
            ->where('fecha_inicial', '<=', $fechaSalida)
            ->where('fecha_final', '>=', $fechaSalida)
            ->where('estado', 'activo')
            ->where('eliminado', false)
            ->first();

        if ($kardex) {
            return $kardex->productos()->where('producto_id', $productoId)->get();
        } else {
            throw new Exception("No hay Kardex disponible.");
        }
    }
    public function kardexProductos()
    {
        return $this->hasMany(KardexProducto::class, 'producto_id');
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
        return self::where('categoria', 'combustible')
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
        return mb_strtoupper($this->categoria . ($this->categoriaPesticida?->descripcion ? ' - ' . $this->categoriaPesticida->descripcion : ''));
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
