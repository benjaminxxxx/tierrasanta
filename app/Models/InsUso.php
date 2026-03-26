<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsUso extends Model
{
    protected $table = 'ins_usos';

    protected $fillable = [
        'nombre',
        'categoria_codigo',
        'descripcion',
        'creador_por',
        'editado_por'
    ];

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'ins_producto_usos', 'uso_id', 'producto_id');
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function editadoPor()
    {
        return $this->belongsTo(User::class, 'editado_por');
    }
}
