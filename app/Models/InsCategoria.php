<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsCategoria extends Model
{
    protected $table = 'ins_categorias';

    // Primary key personalizado
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'codigo',
        'descripcion',
    ];

    // Relación: Una categoría tiene muchos insumos
    public function insumos()
    {
        return $this->hasMany(Producto::class, 'categoria_codigo', 'codigo');
    }
}
