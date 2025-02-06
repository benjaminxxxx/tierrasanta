<?php

namespace App\Models;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    protected $fillable = ['nombre_comercial', 'ingrediente_activo', 'categoria_id', 'codigo_tipo_existencia', 'codigo_unidad_medida'];

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
    /*
    public function totalStockInicialUsado()
    {
        $stock = 0;
        $fecha = Carbon::now();
        $kardexes = $this->kardexesDisponibles($fecha);
        foreach ($kardexes as $kardex) {
            $kardexProducto = $kardex->productos()->where('producto_id', $this->id)->first();
            if ($kardexProducto) {
                $stock += (float) $kardexProducto->stock_inicial - (float) $kardexProducto->salidasStockUsado()->sum("cantidad_stock_inicial");
            }
        }
        return $stock;
    }*/
    /*
    public function getDatosUsoAttribute()
    {
        $totalStockInicialUsado = $this->totalStockInicialUsado();
        $comprasActivas = $this->compras()->whereNull('fecha_termino')->get();
        $stockUsado = 0;
        $response = [];
        $response['fecha'] = '';
        $response['agotado'] = false;
        foreach ($comprasActivas as $compraActiva) {
            $stockUsado += $compraActiva->almacenSalida()->sum('stock');
        }
        $capacidad = $totalStockInicialUsado + $comprasActivas->sum('stock');

        if ($comprasActivas->count() == 0) {
            $response['agotado'] = true;
            $response['fecha'] = $this->compras()->orderBy('fecha_termino', 'desc')->first()->fecha_termino;
        }
        $restante = $capacidad - $stockUsado;
        $response['capacidad'] = $capacidad;
        $response['stockUsado'] = $stockUsado;
        $response['restante'] = $restante;
        return $response;
    }*/
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
        $kardexProductos = $kardex->productos()->where('producto_id',$productoId)->get();
        foreach ($kardexProductos as $kardexProducto) {
            $totalStock+=$kardexProducto->stockDisponible['total_stock'];
            $cantidadUsada+=$kardexProducto->stockDisponible['cantidad_usada'];
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
    public function categoria()
    {
        return $this->belongsTo(CategoriaProducto::class, 'categoria_id');
    }
    public function esCombustibleProducto()
    {
        $descripcionCombustible = env('DESCRIPCION_COMBUSTIBLE', 'Combustible');
        $categoria = $this->categoria;
        return $categoria && mb_strtolower($categoria->nombre) === mb_strtolower($descripcionCombustible);
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
        // Verificar si la categoría "Combustible" existe
        $descripcionCombustible = strtolower(env('DESCRIPCION_COMBUSTIBLE', 'Combustible'));
        $categoriaCombustible = CategoriaProducto::where('nombre', $descripcionCombustible)->first();

        if (!$categoriaCombustible) {
            throw new Exception('La categoría "Combustible" no existe.');
        }

        // Buscar productos de la categoría "Combustible" que coincidan con el nombre proporcionado
        return self::where('categoria_id', $categoriaCombustible->id)
            ->where('nombre_comercial', 'like', '%' . $nombre . '%')
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
        // Obtener el producto
        $producto = self::find($productoId);

        // Retornar false si no existe el producto o no tiene categoría asignada
        if (!$producto || !$producto->categoria_id) {
            return false;
        }

        // Obtener la descripción de la categoría "Combustible" desde el archivo .env
        $descripcionCombustible = env('DESCRIPCION_COMBUSTIBLE', 'Combustible');

        // Verificar si la categoría del producto coincide con "Combustible"
        $categoria = CategoriaProducto::find($producto->categoria_id);

        return $categoria && mb_strtolower($categoria->nombre) == mb_strtolower($descripcionCombustible);
    }
    public static function deTipo($tipo)
    {
        $descripcionCombustible = env('DESCRIPCION_COMBUSTIBLE', 'Combustible');
        $categoria = CategoriaProducto::where('nombre', $descripcionCombustible)->first();
        if (!$categoria) {
            throw new Exception('No existe la categoria para combustible');
        }
        if ($tipo == 'combustible') {
            return self::where('categoria_id', $categoria->id)->with('compras');
        } else {
            return self::whereNot('categoria_id', $categoria->id)->with('compras');
        }
    }
}
