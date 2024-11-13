<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    protected $fillable = ['nombre_comercial', 'ingrediente_activo', 'unidad_medida', 'categoria_id'];

    public function getCompraActivaAttribute()
    {
        return $this->compras()->where('estado', 1)->exists();
    }
    public function getDatosUsoAttribute()
    {
        $comprasActivas = $this->compras()->whereNull('fecha_termino')->get();
        $stockUsado = 0;
        $response = [];
        $response['fecha']='';
        $response['agotado']=false;
        foreach ($comprasActivas as $compraActiva) {
            $stockUsado+=AlmacenProductoSalida::where('compra_producto_id',$compraActiva->id)->sum('cantidad');
        }
        $capacidad = $comprasActivas->sum('stock');

        if($comprasActivas->count()==0){
            $response['agotado']=true;
            $response['fecha']=$this->compras()->orderBy('fecha_termino','desc')->first()->fecha_termino;
        }
        $restante = $capacidad - $stockUsado;
        $response['capacidad']=$capacidad;
        $response['stockUsado']=$stockUsado;
        $response['restante']=$restante;
        return $response;
    }
    public function categoria()
    {
        return $this->belongsTo(CategoriaProducto::class, 'categoria_id');
    }
    public function compras()
    {
        return $this->hasMany(CompraProducto::class);
    }
}
