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
    /*  
    public function kardexProductos()
    {
        return $this->hasMany(KardexProducto::class, 'kardex_id');
    }*/
}
