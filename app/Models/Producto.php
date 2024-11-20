<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    protected $fillable = ['codigo_existencia', 'nombre_comercial', 'ingrediente_activo', 'unidad_medida', 'categoria_id', 'codigo_tipo_existencia', 'codigo_unidad_medida'];

    public function getCompraActivaAttribute()
    {
        return $this->compras()->where('estado', 1)->exists();
    }
    public function getNombreCompletoAttribute()
    {
        $nombreComercial = trim($this->nombre_comercial);
        $ingredienteActivo = trim($this->ingrediente_activo);

        return $ingredienteActivo
            ? "{$nombreComercial} - {$ingredienteActivo}"
            : $nombreComercial;
    }
    public function getDatosUsoAttribute()
    {
        $comprasActivas = $this->compras()->whereNull('fecha_termino')->get();
        $stockUsado = 0;
        $response = [];
        $response['fecha'] = '';
        $response['agotado'] = false;
        foreach ($comprasActivas as $compraActiva) {
            $stockUsado += $compraActiva->almacenSalida()->sum('stock');
        }
        $capacidad = $comprasActivas->sum('stock');

        if ($comprasActivas->count() == 0) {
            $response['agotado'] = true;
            $response['fecha'] = $this->compras()->orderBy('fecha_termino', 'desc')->first()->fecha_termino;
        }
        $restante = $capacidad - $stockUsado;
        $response['capacidad'] = $capacidad;
        $response['stockUsado'] = $stockUsado;
        $response['restante'] = $restante;
        return $response;
    }
    public function kardexesDisponibles($fechaSalida)
    {
        //este productos whereHas es porque en Kardex en vez de KardexProducto puse solo productos
        return Kardex::whereHas('productos', function ($query) {
            $query->where('producto_id', $this->id);
        })
            ->where('fecha_inicial', '<=', $fechaSalida)
            ->where('fecha_final', '>=', $fechaSalida)
            ->where('eliminado',false)
            ->get();
    }
    public function kardexProductos()
    {
        return $this->hasMany(KardexProducto::class, 'producto_id');
    }
    public function categoria()
    {
        return $this->belongsTo(CategoriaProducto::class, 'categoria_id');
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

}
