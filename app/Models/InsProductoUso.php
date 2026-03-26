<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsProductoUso extends Model
{
    protected $table = 'ins_producto_usos';

    protected $fillable = ['producto_id', 'uso_id'];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function uso()
    {
        return $this->belongsTo(InsUso::class, 'uso_id');
    }
}
