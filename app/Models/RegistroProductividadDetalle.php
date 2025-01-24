<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistroProductividadDetalle extends Model
{
    use HasFactory;

    protected $fillable = [
        'registro_productividad_id',
        'indice',
        'horas_trabajadas',
        'kg',
    ];
    public function cantidades()
    {
        return $this->hasMany(RegistroProductividadCantidad::class, 'registro_productividad_detalles_id');
    }
    
    public function registroProductividad()
    {
        return $this->belongsTo(RegistroProductividad::class, 'registro_productividad_id');
    }
}
